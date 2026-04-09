<?php
if (!defined('ABSPATH')) exit;

/**
 * MDD_License — Sistema de Licenciamento
 *
 * Gerencia ativação, validação e verificação periódica da licença.
 * Comunicação com servidor central: a3tecnologias.com
 *
 * Comportamento:
 * - Licença válida: tudo funciona normalmente
 * - Licença expirada/inválida: admin funciona, displays ficam offline
 * - Sem licença: admin funciona com aviso, displays ficam offline
 * - Sem conexão: usa cache local (transient de 24h)
 */
class MDD_License {

    /** URL da API de licenciamento */
    const API_URL = 'https://a3tecnologias.com/wp-json/mdd-license/v1';

    /** Nome do cron hook */
    const CRON_HOOK = 'mdd_license_check';

    /** Tempo de cache em segundos (24h) */
    const CACHE_TTL = 86400;

    /** Options keys */
    const OPT_KEY       = 'mdd_license_key';
    const OPT_STATUS    = 'mdd_license_status';
    const OPT_DATA      = 'mdd_license_data';
    const OPT_LAST_CHECK = 'mdd_license_last_check';

    /**
     * Inicializa hooks
     */
    public static function init() {
        // Cron de verificação periódica
        add_action(self::CRON_HOOK, [__CLASS__, 'cron_verify']);

        // Agendar cron na ativação do plugin
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }

        // AJAX handlers
        add_action('wp_ajax_mdd_license_activate', [__CLASS__, 'ajax_activate']);
        add_action('wp_ajax_mdd_license_deactivate', [__CLASS__, 'ajax_deactivate']);
        add_action('wp_ajax_mdd_license_check', [__CLASS__, 'ajax_check']);

        // Aviso no admin se sem licença
        add_action('admin_notices', [__CLASS__, 'admin_notice']);
    }

    /**
     * Remove cron ao desativar o plugin
     */
    public static function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    // ─────────────────────────────────────────────
    // STATUS
    // ─────────────────────────────────────────────

    /**
     * Retorna se a licença está válida
     * Usa cache local (transient) para evitar requisições a cada pageload
     */
    public static function is_valid() {
        // Dev bypass — remover antes de distribuir para clientes
        if (defined('MDD_DEV_MODE') && MDD_DEV_MODE === true) {
            return true;
        }

        $status = get_option(self::OPT_STATUS, '');
        return $status === 'active';
    }

    /**
     * Retorna o status atual da licença
     * Possíveis: active, expired, invalid, inactive, error, unchecked
     */
    public static function get_status() {
        if (defined('MDD_DEV_MODE') && MDD_DEV_MODE === true) {
            return 'active';
        }
        return get_option(self::OPT_STATUS, 'unchecked');
    }

    /**
     * Retorna dados completos da licença (plano, validade, etc.)
     */
    public static function get_data() {
        return get_option(self::OPT_DATA, []);
    }

    /**
     * Retorna a chave de licença armazenada
     */
    public static function get_key() {
        return get_option(self::OPT_KEY, '');
    }

    /**
     * Retorna o domínio atual (sem protocolo)
     */
    public static function get_domain() {
        return wp_parse_url(home_url(), PHP_URL_HOST);
    }

    /**
     * Retorna datetime da última verificação
     */
    public static function get_last_check() {
        $ts = get_option(self::OPT_LAST_CHECK, 0);
        if (!$ts) return null;
        return wp_date('d/m/Y H:i', $ts);
    }

    /**
     * Retorna quanto tempo falta para a próxima verificação automática
     */
    public static function get_next_check() {
        $next = wp_next_scheduled(self::CRON_HOOK);
        if (!$next) return null;
        return wp_date('d/m/Y H:i', $next);
    }

    // ─────────────────────────────────────────────
    // ATIVAÇÃO / DESATIVAÇÃO
    // ─────────────────────────────────────────────

    /**
     * Ativa uma licença com o servidor central
     */
    public static function activate($license_key) {
        $license_key = sanitize_text_field(trim($license_key));

        if (empty($license_key)) {
            return ['success' => false, 'message' => 'Chave de licença não informada.'];
        }

        // Validar formato básico (32 chars hex ou UUID)
        if (!preg_match('/^[A-Za-z0-9\-]{8,64}$/', $license_key)) {
            return ['success' => false, 'message' => 'Formato de chave inválido.'];
        }

        $response = self::api_request('activate', [
            'license_key' => $license_key,
            'domain'      => self::get_domain(),
            'plugin'      => 'drink-display',
            'version'     => MDD_VERSION,
            'site_name'   => get_bloginfo('name'),
        ]);

        if ($response['success']) {
            update_option(self::OPT_KEY, $license_key);
            update_option(self::OPT_STATUS, 'active');
            update_option(self::OPT_DATA, $response['data'] ?? []);
            update_option(self::OPT_LAST_CHECK, time());

            return [
                'success' => true,
                'message' => 'Licença ativada com sucesso!',
                'data'    => $response['data'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Erro ao ativar a licença.',
        ];
    }

    /**
     * Desativa a licença no servidor (libera o slot do domínio)
     */
    public static function deactivate_license() {
        $key = self::get_key();
        if (empty($key)) {
            return ['success' => false, 'message' => 'Nenhuma licença ativa.'];
        }

        $response = self::api_request('deactivate', [
            'license_key' => $key,
            'domain'      => self::get_domain(),
        ]);

        // Limpa local independente da resposta do servidor
        delete_option(self::OPT_KEY);
        update_option(self::OPT_STATUS, 'inactive');
        update_option(self::OPT_DATA, []);

        return [
            'success' => true,
            'message' => 'Licença desativada.',
        ];
    }

    // ─────────────────────────────────────────────
    // VERIFICAÇÃO
    // ─────────────────────────────────────────────

    /**
     * Verifica a licença com o servidor central
     * Chamada pelo cron diário e pelo botão manual
     */
    public static function verify() {
        $key = self::get_key();

        if (empty($key)) {
            update_option(self::OPT_STATUS, 'inactive');
            return ['success' => false, 'status' => 'inactive'];
        }

        $response = self::api_request('verify', [
            'license_key' => $key,
            'domain'      => self::get_domain(),
            'plugin'      => 'drink-display',
            'version'     => MDD_VERSION,
        ]);

        update_option(self::OPT_LAST_CHECK, time());

        if ($response['success']) {
            $status = $response['data']['status'] ?? 'active';
            update_option(self::OPT_STATUS, $status);
            update_option(self::OPT_DATA, $response['data'] ?? []);

            return [
                'success' => true,
                'status'  => $status,
                'data'    => $response['data'] ?? [],
            ];
        }

        // Se o servidor respondeu com erro explícito (licença expirou, etc.)
        if (isset($response['data']['status'])) {
            update_option(self::OPT_STATUS, $response['data']['status']);
            update_option(self::OPT_DATA, $response['data'] ?? []);
        }

        // Se houve falha de conexão, mantém o status anterior (graceful degradation)
        if (isset($response['connection_error']) && $response['connection_error']) {
            // Não altera o status — usa o cache local
            return [
                'success' => false,
                'status'  => self::get_status(),
                'message' => 'Falha de conexão com o servidor. Status mantido do cache.',
            ];
        }

        return [
            'success' => false,
            'status'  => self::get_status(),
            'message' => $response['message'] ?? 'Erro na verificação.',
        ];
    }

    /**
     * Cron job — executa a cada 24h
     */
    public static function cron_verify() {
        self::verify();
    }

    // ─────────────────────────────────────────────
    // API
    // ─────────────────────────────────────────────

    /**
     * Faz requisição HTTP para o servidor de licenciamento
     */
    private static function api_request($endpoint, $body = []) {
        $url = trailingslashit(self::API_URL) . $endpoint;

        $args = [
            'method'    => 'POST',
            'timeout'   => 15,
            'headers'   => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'      => wp_json_encode($body),
            'sslverify' => true,
        ];

        $response = wp_remote_post($url, $args);

        // Erro de conexão (servidor offline, DNS falhou, timeout)
        if (is_wp_error($response)) {
            return [
                'success'          => false,
                'connection_error' => true,
                'message'          => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code === 200 && isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'data'    => $data['data'] ?? [],
                'message' => $data['message'] ?? '',
            ];
        }

        return [
            'success'          => false,
            'connection_error' => false,
            'data'             => $data['data'] ?? [],
            'message'          => $data['message'] ?? "Erro HTTP {$code}",
        ];
    }

    // ─────────────────────────────────────────────
    // AJAX HANDLERS
    // ─────────────────────────────────────────────

    public static function ajax_activate() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sem permissão.');

        $key = sanitize_text_field($_POST['license_key'] ?? '');
        $result = self::activate($key);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public static function ajax_deactivate() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sem permissão.');

        $result = self::deactivate_license();
        wp_send_json_success($result);
    }

    public static function ajax_check() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Sem permissão.');

        $result = self::verify();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message'] ?? 'Erro na verificação.');
        }
    }

    // ─────────────────────────────────────────────
    // ADMIN NOTICES
    // ─────────────────────────────────────────────

    /**
     * Exibe aviso no admin se licença não está ativa
     * Só mostra na página do plugin
     */
    public static function admin_notice() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'mdd-settings') === false) return;

        $status = self::get_status();
        if ($status === 'active') return;

        $tab = isset($_GET['tab']) ? $_GET['tab'] : '';
        if ($tab === 'license') return; // Não mostra na própria aba de licença

        $messages = [
            'expired'   => '⚠️ Sua licença do Drink Display expirou. Os displays (TV, Tablet, Quiz) estão offline.',
            'invalid'   => '⚠️ Chave de licença inválida. Os displays estão offline.',
            'inactive'  => '🔑 Ative sua licença do Drink Display para habilitar os displays (TV, Tablet, Quiz).',
            'unchecked' => '🔑 Ative sua licença do Drink Display para começar a usar.',
        ];

        $msg = $messages[$status] ?? $messages['inactive'];
        $url = admin_url('admin.php?page=mdd-settings&tab=license');
        ?>
        <div class="notice notice-warning" style="border-left-color:var(--mdd-accent,#f97316)">
            <p>
                <?php echo esc_html($msg); ?>
                <a href="<?php echo esc_url($url); ?>" style="margin-left:8px;font-weight:600"><?php _e('Ativar Licença →', 'madame-drink-display'); ?></a>
            </p>
        </div>
        <?php
    }

    // ─────────────────────────────────────────────
    // DISPLAY GATE
    // ─────────────────────────────────────────────

    /**
     * Verifica se os displays podem ser exibidos
     * Chamada pelo Display Router antes de carregar templates
     */
    public static function can_show_display() {
        return self::is_valid();
    }

    /**
     * Renderiza página de licença expirada (para displays bloqueados)
     */
    public static function render_expired_page() {
        $status = self::get_status();
        $title  = ($status === 'expired') ? 'Licença Expirada' : 'Licença Necessária';
        $msg    = ($status === 'expired')
            ? 'A licença do Drink Display expirou. Contate o administrador para renovar.'
            : 'O Drink Display requer uma licença ativa. Contate o administrador.';

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?php echo esc_html($title); ?></title>
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{background:#0f0f0f;color:#f0ece4;font-family:system-ui,sans-serif;
                 display:flex;align-items:center;justify-content:center;min-height:100vh;
                 text-align:center;padding:40px}
            .box{max-width:480px}
            .icon{font-size:64px;margin-bottom:24px;opacity:.6}
            h1{font-size:24px;font-weight:700;margin-bottom:12px;color:#f97316}
            p{font-size:15px;line-height:1.6;color:#888;margin-bottom:24px}
            .badge{display:inline-block;padding:6px 16px;border-radius:8px;font-size:12px;
                   font-weight:600;letter-spacing:.5px;text-transform:uppercase;
                   background:rgba(224,60,60,.12);color:#e03c3c;border:1px solid rgba(224,60,60,.2)}
            a{color:#f97316;text-decoration:none}
        </style>
        </head>
        <body>
        <div class="box">
            <div class="icon">🔒</div>
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html($msg); ?></p>
            <span class="badge"><?php echo esc_html($status); ?></span>
            <p style="margin-top:32px;font-size:12px">
                Powered by <a href="https://a3tecnologias.com" target="_blank">A3 Tecnologias</a>
            </p>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}
