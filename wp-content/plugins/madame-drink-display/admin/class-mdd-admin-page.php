<?php
if (!defined('ABSPATH')) exit;

class MDD_Admin_Page {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('wp_ajax_mdd_create_token', [__CLASS__, 'ajax_create_token']);
        add_action('wp_ajax_mdd_revoke_token', [__CLASS__, 'ajax_revoke_token']);
        add_action('wp_ajax_mdd_delete_token', [__CLASS__, 'ajax_delete_token']);
        add_action('wp_ajax_mdd_edit_token', [__CLASS__, 'ajax_edit_token']);
        add_action('wp_ajax_mdd_reset_stats', [__CLASS__, 'ajax_reset_stats']);
        add_action('wp_ajax_mdd_reactivate_token', [__CLASS__, 'ajax_reactivate_token']);
        add_action('wp_ajax_mdd_save_quiz_questions', [__CLASS__, 'ajax_save_quiz_questions']);
    }

    public static function add_menu() {
        add_menu_page(
            __('Drink Display', 'madame-drink-display'),
            __('Drink Display', 'madame-drink-display'),
            'manage_options',
            'mdd-settings',
            [__CLASS__, 'render_page'],
            'dashicons-drinks',
            30
        );
    }

    public static function register_settings() {
        MDD_Settings::register();
    }

    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'mdd-settings') === false) return;

        wp_enqueue_media();
        wp_enqueue_style('mdd-admin', MDD_PLUGIN_URL . 'assets/css/admin.css', [], MDD_VERSION);
        wp_enqueue_script('mdd-admin', MDD_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], MDD_VERSION, true);
        wp_enqueue_style('wp-color-picker');

        // Guide-specific CSS
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
        if ($tab === 'guide') {
            wp_enqueue_style('mdd-guia', MDD_PLUGIN_URL . 'assets/css/guia.css', ['mdd-admin'], MDD_VERSION);
        }

        wp_localize_script('mdd-admin', 'mddAdmin', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mdd_admin_nonce'),
            'strings'  => [
                'confirmRevoke'  => __('Revogar este token? O dispositivo perderá acesso.', 'madame-drink-display'),
                'confirmDelete'  => __('Excluir permanentemente este token?', 'madame-drink-display'),
                'tokenCreated'   => __('Token criado com sucesso!', 'madame-drink-display'),
                'selectImage'    => __('Selecionar Logo', 'madame-drink-display'),
                'useImage'       => __('Usar esta imagem', 'madame-drink-display'),
            ],
        ]);
    }

    public static function render_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        $tabs = [
            'dashboard' => ['label' => __('Dashboard', 'madame-drink-display'), 'icon' => 'dashicons-dashboard'],
            'license'  => ['label' => __('Licença', 'madame-drink-display'),   'icon' => 'dashicons-admin-network'],
            'general' => ['label' => __('Geral', 'madame-drink-display'),    'icon' => 'dashicons-admin-settings'],
            'logos'   => ['label' => __('Logos', 'madame-drink-display'),     'icon' => 'dashicons-format-image'],
            'tv'      => ['label' => __('Modo TV', 'madame-drink-display'),  'icon' => 'dashicons-desktop'],
            // 'tablet' => STANDBY — reativar quando necessário
            'quiz'    => ['label' => __('Quiz', 'madame-drink-display'),     'icon' => 'dashicons-forms'],
            'qrcode'  => ['label' => __('QR Code', 'madame-drink-display'),  'icon' => 'dashicons-smartphone'],
            'tagger'  => ['label' => __('Auto-Tagger', 'madame-drink-display'), 'icon' => 'dashicons-tag'],
            'import'  => ['label' => __('Importar IA', 'madame-drink-display'), 'icon' => 'dashicons-upload'],
            'tokens'  => ['label' => __('Tokens', 'madame-drink-display'),   'icon' => 'dashicons-admin-network'],
            'stats'   => ['label' => __('Estatísticas', 'madame-drink-display'), 'icon' => 'dashicons-chart-bar'],
            'guide'   => ['label' => __('Guia', 'madame-drink-display'),     'icon' => 'dashicons-book'],
        ];
        ?>
        <div id="mdd-wrap">
            <h1 class="mdd-title">
                <span class="dashicons dashicons-drinks"></span>
                <?php _e('Drink Display', 'madame-drink-display'); ?>
            </h1>

            <nav class="mdd-tabs">
                <?php
                $lic_dot = '';
                $ls = MDD_License::get_status();
                if ($ls === 'active') $lic_dot = '<span style="width:8px;height:8px;border-radius:50%;background:var(--mdd-success);display:inline-block;margin-left:4px"></span>';
                elseif ($ls === 'expired' || $ls === 'invalid') $lic_dot = '<span style="width:8px;height:8px;border-radius:50%;background:var(--mdd-danger);display:inline-block;margin-left:4px"></span>';
                elseif ($ls === 'inactive' || $ls === 'unchecked') $lic_dot = '<span style="width:8px;height:8px;border-radius:50%;background:#ffad00;display:inline-block;margin-left:4px"></span>';

                foreach ($tabs as $slug => $info): ?>
                    <a href="<?php echo admin_url("admin.php?page=mdd-settings&tab={$slug}"); ?>"
                       class="mdd-tab <?php echo $tab === $slug ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($info['icon']); ?>"></span>
                        <?php echo esc_html($info['label']); ?>
                        <?php if ($slug === 'license') echo $lic_dot; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mdd-content">
                <?php
                switch ($tab) {
                    case 'dashboard': self::render_dashboard_tab(); break;
                    case 'license':  self::render_license_tab(); break;
                    case 'general': self::render_general_tab(); break;
                    case 'logos':   self::render_logos_tab(); break;
                    case 'tv':     self::render_tv_tab(); break;
                    case 'tablet': self::render_tablet_tab(); break;
                    case 'quiz':   self::render_quiz_tab(); break;
                    case 'qrcode': self::render_qrcode_tab(); break;
                    case 'tagger': self::render_tagger_tab(); break;
                    case 'import': self::render_import_tab(); break;
                    case 'tokens': self::render_tokens_tab(); break;
                    case 'stats':  self::render_stats_tab(); break;
                    case 'guide':  self::render_guide_tab(); break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    // ─── License Tab ───
    private static function render_license_tab() {
        $key        = MDD_License::get_key();
        $status     = MDD_License::get_status();
        $data       = MDD_License::get_data();
        $domain     = MDD_License::get_domain();
        $last_check = MDD_License::get_last_check();
        $next_check = MDD_License::get_next_check();
        $has_key    = !empty($key);

        $status_labels = [
            'active'    => ['label' => 'Ativa',          'color' => 'var(--mdd-success)', 'bg' => 'rgba(34,197,94,.08)'],
            'expired'   => ['label' => 'Expirada',       'color' => 'var(--mdd-danger)',  'bg' => 'rgba(224,60,60,.08)'],
            'invalid'   => ['label' => 'Inválida',       'color' => 'var(--mdd-danger)',  'bg' => 'rgba(224,60,60,.08)'],
            'inactive'  => ['label' => 'Inativa',        'color' => 'var(--mdd-muted)',   'bg' => 'rgba(136,136,136,.08)'],
            'unchecked' => ['label' => 'Não verificada', 'color' => '#ffad00',            'bg' => 'rgba(255,173,0,.08)'],
            'error'     => ['label' => 'Erro',           'color' => 'var(--mdd-danger)',  'bg' => 'rgba(224,60,60,.08)'],
        ];

        $s = $status_labels[$status] ?? $status_labels['unchecked'];

        $masked_key = $has_key ? substr($key, 0, 8) . '••••••••' . substr($key, -4) : '';
        ?>

        <!-- Status Card -->
        <div class="mdd-card" style="border-left:3px solid <?php echo $s['color']; ?>">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
                <div style="width:52px;height:52px;border-radius:12px;background:<?php echo $s['bg']; ?>;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">
                    <?php echo ($status === 'active') ? '✅' : (($status === 'expired' || $status === 'invalid') ? '🔒' : '🔑'); ?>
                </div>
                <div style="flex:1">
                    <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:4px">
                        Status da Licença:
                        <span style="color:<?php echo $s['color']; ?>"><?php echo esc_html($s['label']); ?></span>
                    </div>
                    <div style="font-size:.82rem;color:var(--mdd-muted)">
                        Domínio registrado: <strong style="color:var(--mdd-text)"><?php echo esc_html($domain); ?></strong>
                    </div>
                </div>
                <?php if ($has_key): ?>
                <button type="button" class="button" id="mdd-license-check" style="flex-shrink:0">
                    <span class="dashicons dashicons-update" style="margin-top:3px"></span> Verificar Agora
                </button>
                <?php endif; ?>
            </div>

            <?php if ($has_key): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;padding-top:16px;border-top:1px solid var(--mdd-border)">
                <div>
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);margin-bottom:4px">Chave</div>
                    <div style="font-family:monospace;font-size:.85rem;color:var(--mdd-accent2)"><?php echo esc_html($masked_key); ?></div>
                </div>
                <?php if (!empty($data['plan'])): ?>
                <div>
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);margin-bottom:4px">Plano</div>
                    <div style="font-size:.85rem"><?php echo esc_html($data['plan']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($data['expires_at'])): ?>
                <div>
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);margin-bottom:4px">Validade</div>
                    <div style="font-size:.85rem"><?php echo esc_html($data['expires_at']); ?></div>
                </div>
                <?php endif; ?>
                <div>
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);margin-bottom:4px">Última verificação</div>
                    <div style="font-size:.85rem"><?php echo $last_check ? esc_html($last_check) : '—'; ?></div>
                </div>
                <div>
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);margin-bottom:4px">Próxima automática</div>
                    <div style="font-size:.85rem"><?php echo $next_check ? esc_html($next_check) : '—'; ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Activate / Deactivate -->
        <div class="mdd-card">
            <h2><?php echo $has_key ? '🔄 Gerenciar Licença' : '🔑 Ativar Licença'; ?></h2>

            <?php if (!$has_key): ?>
            <p style="margin-bottom:16px;color:var(--mdd-muted)">
                Insira a chave de licença fornecida ao contratar o Drink Display.
                Sem licença ativa, os displays (TV, Tablet, Quiz) ficam <strong style="color:var(--mdd-danger)">offline</strong>.
                O painel administrativo continua funcionando normalmente.
            </p>
            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <div class="mdd-field" style="flex:1;min-width:280px;margin-bottom:0">
                    <label for="mdd-license-key">Chave de Licença</label>
                    <input type="text" id="mdd-license-key"
                           placeholder="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX"
                           style="font-family:monospace;letter-spacing:.5px">
                </div>
                <button type="button" class="button button-primary" id="mdd-license-activate" style="height:38px;white-space:nowrap">
                    Ativar Licença
                </button>
            </div>
            <div id="mdd-license-msg" style="margin-top:12px;font-size:.85rem"></div>

            <?php else: ?>
            <p style="margin-bottom:16px;color:var(--mdd-muted)">
                Sua licença está vinculada ao domínio <strong style="color:var(--mdd-text)"><?php echo esc_html($domain); ?></strong>.
                Ao desativar, o slot deste domínio é liberado e pode ser usado em outra instalação.
            </p>
            <div style="display:flex;gap:12px;align-items:center">
                <button type="button" class="button button-link-delete" id="mdd-license-deactivate">
                    Desativar Licença
                </button>
                <span id="mdd-license-msg" style="font-size:.85rem"></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="mdd-card" style="background:rgba(249,115,22,.04);border-color:rgba(249,115,22,.15)">
            <h2>ℹ️ Como funciona</h2>
            <div style="display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <span style="font-size:16px;flex-shrink:0;margin-top:2px">1️⃣</span>
                    <div>
                        <strong>Ativação:</strong>
                        <span style="color:var(--mdd-muted)">Cole a chave recebida ao contratar. O plugin valida com o servidor A3 Tecnologias e vincula ao seu domínio.</span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <span style="font-size:16px;flex-shrink:0;margin-top:2px">2️⃣</span>
                    <div>
                        <strong>Verificação automática:</strong>
                        <span style="color:var(--mdd-muted)">A cada 24 horas, o plugin confirma a validade da licença em background. Sem intervenção necessária.</span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <span style="font-size:16px;flex-shrink:0;margin-top:2px">3️⃣</span>
                    <div>
                        <strong>Se a licença expirar:</strong>
                        <span style="color:var(--mdd-muted)">O painel admin continua funcionando (você não perde configurações). Apenas os displays públicos (TV, Tablet, Quiz) ficam offline até a renovação.</span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <span style="font-size:16px;flex-shrink:0;margin-top:2px">4️⃣</span>
                    <div>
                        <strong>Migração de domínio:</strong>
                        <span style="color:var(--mdd-muted)">Se trocar de domínio, desative a licença aqui e reative no novo site. O slot é liberado automaticamente.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mdd-admin-footer">
            Drink Display v<?php echo MDD_VERSION; ?> · Licenciamento gerenciado por
            <a href="https://a3tecnologias.com" target="_blank">A3 Tecnologias</a>
        </div>
        <?php
    }

    // ─── Dashboard Tab ───
    private static function render_dashboard_tab() {
        $bridge = new MDD_CPT_Bridge();
        $drinks = $bridge->get_drinks();
        $tokens = MDD_Token_Manager::get_all_tokens();
        $stats = MDD_Quiz_Engine::get_stats();
        $logo = MDD_Settings::get_active_logo();
        $primary = get_option('mdd_primary_color', '#C8962E');
        $post_type = get_option('mdd_drink_post_type', 'drink');

        // Check setup status
        $has_cpt = post_type_exists($post_type);
        $has_logo = !empty($logo);
        $has_tokens = !empty($tokens);
        $has_drinks = !empty($drinks);

        $drinks_with_tags = 0;
        foreach ($drinks as $d) {
            if (!empty($d['profile_tags'])) $drinks_with_tags++;
        }
        $has_tags = $drinks_with_tags > 0;

        $drinks_with_images = 0;
        foreach ($drinks as $d) {
            if (!empty($d['image'])) $drinks_with_images++;
        }

        $active_tokens = array_filter($tokens, function($t) { return $t->is_active; });
        $tv_tokens = array_filter($active_tokens, function($t) { return $t->device_type === 'tv'; });
        $tablet_tokens = array_filter($active_tokens, function($t) { return $t->device_type === 'tablet'; });

        $setup_complete = $has_cpt && $has_logo && $has_tokens && $has_drinks && $has_tags;
        $lic_status = MDD_License::get_status();
        $lic_valid  = MDD_License::is_valid();
        ?>

        <!-- License Banner -->
        <?php if (!$lic_valid): ?>
        <div class="mdd-card" style="border-left:3px solid <?php echo ($lic_status === 'expired') ? 'var(--mdd-danger)' : '#ffad00'; ?>;padding:16px 24px">
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:20px"><?php echo ($lic_status === 'expired') ? '🔒' : '🔑'; ?></span>
                <div style="flex:1">
                    <strong><?php echo ($lic_status === 'expired') ? 'Licença expirada' : 'Licença não ativada'; ?></strong>
                    <span style="color:var(--mdd-muted);font-size:.85rem;margin-left:8px">— displays (TV, Tablet, Quiz) estão offline</span>
                </div>
                <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=license'); ?>" class="button button-primary">
                    <?php echo ($lic_status === 'expired') ? 'Renovar' : 'Ativar Licença'; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Banner -->
        <?php if (!$setup_complete): ?>
        <div class="mdd-card" style="background:linear-gradient(135deg,#1a1a2e,#2a2a48);color:#fff;border:none">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
                <span style="font-size:36px">🍸</span>
                <div>
                    <h2 style="margin:0!important;padding:0!important;border:none!important;color:#fff!important;font-size:20px"><?php _e('Bem-vindo ao Drink Display!', 'madame-drink-display'); ?></h2>
                    <p style="margin:4px 0 0;color:rgba(255,255,255,.6);font-size:13px"><?php _e('Siga os 5 passos abaixo na ordem. Cada passo que você concluir fica marcado com ✓.', 'madame-drink-display'); ?></p>
                </div>
            </div>
            <div class="mdd-setup-steps">
                <?php
                $steps = [
                    ['done' => $has_cpt && $has_drinks, 'label' => __('Passo 1 — Aba "Geral": Selecione qual tipo de post (CPT) contém seus drinks', 'madame-drink-display'), 'tab' => 'general', 'icon' => '1️⃣'],
                    ['done' => $has_logo,  'label' => __('Passo 2 — Aba "Logos": Suba o logo do seu restaurante (aparece em todas as telas)', 'madame-drink-display'), 'tab' => 'logos', 'icon' => '2️⃣'],
                    ['done' => $has_tags,   'label' => __('Passo 3 — Aba "Auto-Tagger": Clique um botão para classificar seus drinks automaticamente (necessário para o Quiz)', 'madame-drink-display'), 'tab' => 'tagger', 'icon' => '3️⃣'],
                    ['done' => $has_tokens, 'label' => __('Passo 4 — Aba "Tokens": Gere uma chave de acesso para cada TV ou Tablet do salão', 'madame-drink-display'), 'tab' => 'tokens', 'icon' => '4️⃣'],
                ];
                foreach ($steps as $step):
                ?>
                    <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=' . $step['tab']); ?>" class="mdd-setup-step <?php echo $step['done'] ? 'done' : ''; ?>">
                        <span class="mdd-setup-check"><?php echo $step['done'] ? '✓' : $step['icon']; ?></span>
                        <span><?php echo esc_html($step['label']); ?></span>
                    </a>
                <?php endforeach; ?>
                <div class="mdd-setup-step" style="opacity:.6;cursor:default;border-color:transparent">
                    <span class="mdd-setup-check">5️⃣</span>
                    <span><?php _e('Passo 5 — Vá em Configurações → Links Permanentes no menu do WordPress e clique "Salvar" (sem alterar nada). Isso ativa as URLs do display.', 'madame-drink-display'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="mdd-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
            <div class="mdd-stat-card">
                <span class="mdd-stat-number"><?php echo count($drinks); ?></span>
                <span class="mdd-stat-label"><?php _e('Drinks', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-stat-card">
                <span class="mdd-stat-number"><?php echo $drinks_with_images; ?></span>
                <span class="mdd-stat-label"><?php _e('Com Foto', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-stat-card">
                <span class="mdd-stat-number"><?php echo $drinks_with_tags; ?></span>
                <span class="mdd-stat-label"><?php _e('Com Tags', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-stat-card">
                <span class="mdd-stat-number"><?php echo count($tv_tokens); ?></span>
                <span class="mdd-stat-label"><?php _e('TVs Ativas', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-stat-card">
                <span class="mdd-stat-number"><?php echo count($tablet_tokens); ?></span>
                <span class="mdd-stat-label"><?php _e('Tablets Ativos', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-stat-card">
                <span class="mdd-stat-number"><?php echo $stats['total']; ?></span>
                <span class="mdd-stat-label"><?php _e('Quizzes', 'madame-drink-display'); ?></span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <!-- Preview Links -->
            <div class="mdd-card">
                <h2 style="display:flex;align-items:center;gap:8px">
                    <span class="dashicons dashicons-visibility" style="color:<?php echo esc_attr($primary); ?>"></span>
                    <?php _e('Visualizar Telas', 'madame-drink-display'); ?>
                </h2>

                <?php if (!empty($active_tokens)): ?>
                    <?php foreach ($active_tokens as $t):
                        $url = MDD_Display_Router::get_display_url($t->device_type, $t->token);
                        $icon = $t->device_type === 'tv' ? '📺' : '📱';
                    ?>
                        <div class="mdd-preview-link">
                            <span class="mdd-preview-icon"><?php echo $icon; ?></span>
                            <div class="mdd-preview-info">
                                <strong><?php echo esc_html($t->device_name); ?></strong>
                                <span class="mdd-badge mdd-badge-<?php echo esc_attr($t->device_type); ?>"><?php echo strtoupper($t->device_type); ?></span>
                            </div>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" class="button button-small"><?php _e('Abrir', 'madame-drink-display'); ?> ↗</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="description"><?php _e('Nenhum token ativo. Gere tokens na aba Tokens.', 'madame-drink-display'); ?></p>
                <?php endif; ?>

                <div class="mdd-preview-link" style="border-top:1px solid #eee;padding-top:12px;margin-top:8px">
                    <span class="mdd-preview-icon">🧩</span>
                    <div class="mdd-preview-info">
                        <strong><?php _e('Quiz de Drinks', 'madame-drink-display'); ?></strong>
                        <span style="font-size:11px;color:#999"><?php _e('Público (sem token)', 'madame-drink-display'); ?></span>
                    </div>
                    <a href="<?php echo esc_url(home_url('/display/quiz/')); ?>" target="_blank" class="button button-small"><?php _e('Abrir', 'madame-drink-display'); ?> ↗</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mdd-card">
                <h2 style="display:flex;align-items:center;gap:8px">
                    <span class="dashicons dashicons-admin-tools" style="color:<?php echo esc_attr($primary); ?>"></span>
                    <?php _e('Ações Rápidas', 'madame-drink-display'); ?>
                </h2>

                <div class="mdd-quick-actions">
                    <a href="<?php echo admin_url('edit.php?post_type=' . $post_type); ?>" class="mdd-quick-action">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Gerenciar Drinks', 'madame-drink-display'); ?>
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . $post_type); ?>" class="mdd-quick-action">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Adicionar Drink', 'madame-drink-display'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=tokens'); ?>" class="mdd-quick-action">
                        <span class="dashicons dashicons-admin-network"></span>
                        <?php _e('Gerar Token', 'madame-drink-display'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=tagger'); ?>" class="mdd-quick-action">
                        <span class="dashicons dashicons-tag"></span>
                        <?php _e('Auto-Tagger', 'madame-drink-display'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=qrcode'); ?>" class="mdd-quick-action">
                        <span class="dashicons dashicons-smartphone"></span>
                        <?php _e('QR Code Quiz', 'madame-drink-display'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=logos'); ?>" class="mdd-quick-action">
                        <span class="dashicons dashicons-format-image"></span>
                        <?php echo get_option('mdd_event_mode') ? __('Modo Evento ATIVO', 'madame-drink-display') : __('Logos / Evento', 'madame-drink-display'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Active Devices Monitor -->
        <?php if (!empty($active_tokens)): ?>
        <div class="mdd-card">
            <h2 style="display:flex;align-items:center;gap:8px">
                <span class="dashicons dashicons-admin-network" style="color:<?php echo esc_attr($primary); ?>"></span>
                <?php _e('Dispositivos Conectados', 'madame-drink-display'); ?>
            </h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Dispositivo', 'madame-drink-display'); ?></th>
                        <th><?php _e('Tipo', 'madame-drink-display'); ?></th>
                        <th><?php _e('Último Acesso', 'madame-drink-display'); ?></th>
                        <th><?php _e('Status', 'madame-drink-display'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_tokens as $t):
                        $last = $t->last_access ? strtotime($t->last_access . ' UTC') : 0;
                        $ago = $last ? human_time_diff($last) . ' atrás' : '—';
                        $online = $last && (time() - $last) < 600; // < 10min
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($t->device_name); ?></strong></td>
                        <td><span class="mdd-badge mdd-badge-<?php echo esc_attr($t->device_type); ?>"><?php echo strtoupper($t->device_type); ?></span></td>
                        <td><?php echo esc_html($ago); ?></td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:4px">
                                <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $online ? '#4caf50' : '#ff9800'; ?>"></span>
                                <?php echo $online ? __('Online', 'madame-drink-display') : __('Inativo', 'madame-drink-display'); ?>
                            </span>
                        </td>
                        <td><a href="<?php echo esc_url(MDD_Display_Router::get_display_url($t->device_type, $t->token)); ?>" target="_blank" class="button button-small">↗</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Event Mode Indicator -->
        <?php if (get_option('mdd_event_mode')): ?>
        <div class="mdd-card" style="border-left:4px solid <?php echo esc_attr($primary); ?>;background:#faf6ed">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <h3 style="margin:0;color:#333">🎉 <?php _e('Modo Evento Ativo', 'madame-drink-display'); ?></h3>
                    <p style="margin:4px 0 0;color:#666;font-size:13px">
                        <?php echo esc_html(get_option('mdd_event_name', '')); ?> —
                        <?php _e('Todas as telas estão exibindo o logo do evento.', 'madame-drink-display'); ?>
                    </p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=logos'); ?>" class="button"><?php _e('Gerenciar', 'madame-drink-display'); ?></a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Health Check -->
        <?php MDD_Health_Check::render(); ?>

        <?php
    }

    // ─── General Tab ───
    private static function render_general_tab() {
        if (isset($_POST['mdd_save_general']) && check_admin_referer('mdd_general_nonce')) {
            update_option('mdd_drink_post_type', sanitize_text_field($_POST['mdd_drink_post_type'] ?? $_POST['mdd_drink_post_types'][0] ?? 'drink'));
            // Multi-CPT support
            $selected_types = array_map('sanitize_text_field', $_POST['mdd_drink_post_types'] ?? []);
            if (empty($selected_types)) $selected_types = [sanitize_text_field($_POST['mdd_drink_post_type'] ?? 'drink')];
            update_option('mdd_drink_post_types', $selected_types);
            // Keep primary for backward compat
            update_option('mdd_drink_post_type', $selected_types[0]);
            update_option('mdd_food_post_type', sanitize_text_field($_POST['mdd_food_post_type'] ?? ''));
            update_option('mdd_primary_color', sanitize_hex_color($_POST['mdd_primary_color'] ?? '#C8962E'));
            update_option('mdd_secondary_color', sanitize_hex_color($_POST['mdd_secondary_color'] ?? '#1A1A2E'));
            update_option('mdd_accent_color', sanitize_hex_color($_POST['mdd_accent_color'] ?? '#E8593C'));

            // Save field mapping
            $field_map = [];
            $map_fields = ['price', 'price_2', 'price_3', 'short_desc', 'video', 'ingredients', 'gallery', 'variants', 'food_pairing', 'context_msg'];
            foreach ($map_fields as $f) {
                $field_map[$f] = sanitize_text_field($_POST['mdd_map_' . $f] ?? '');
            }
            update_option('mdd_field_map', $field_map);
            update_option('mdd_hide_field', sanitize_text_field($_POST['mdd_hide_field'] ?? '_mdd_hide_from_display'));

            echo '<div class="notice notice-success"><p>✅ Configurações salvas!</p></div>';
        }

        $post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
        $current_pt = get_option('mdd_drink_post_type', 'drink');
        $food_pt    = get_option('mdd_food_post_type', '');
        $bridge     = new MDD_CPT_Bridge();
        $drink_count = count($bridge->get_drinks());
        $field_map  = MDD_Settings::get_field_map();

        // Detect meta keys for current CPT
        $meta_keys  = post_type_exists($current_pt) ? MDD_Settings::detect_meta_keys($current_pt) : [];

        $pri = get_option('mdd_primary_color', '#C8962E');
        $sec = get_option('mdd_secondary_color', '#1A1A2E');
        $acc = get_option('mdd_accent_color', '#E8593C');

        // Field labels for the mapper
        $field_labels = [
            'price'       => ['label' => 'Preço Principal',     'desc' => 'Campo numérico com o preço do drink (ex: 29.90)'],
            'price_2'     => ['label' => 'Preço 2 (variante)',   'desc' => 'Segundo preço — ex: com ingrediente premium'],
            'price_3'     => ['label' => 'Preço 3 (variante)',   'desc' => 'Terceiro preço — ex: versão especial'],
            'short_desc'  => ['label' => 'Descrição Curta',    'desc' => 'Texto curto que aparece nos cards (1-2 frases)'],
            'video'       => ['label' => 'Vídeo Curto',        'desc' => 'URL de vídeo MP4 (5-15 seg) para slides da TV'],
            'ingredients' => ['label' => 'Ingredientes',       'desc' => 'Lista de ingredientes (texto ou repeater)'],
            'gallery'     => ['label' => 'Galeria',            'desc' => 'IDs de imagens adicionais (separados por vírgula)'],
            'variants'    => ['label' => 'Variantes de Preço',  'desc' => 'Ex: Smirnoff R$25, Absolut R$27 (repeater)'],
            'food_pairing'=> ['label' => '🍽️ Harmonização (Pratos)', 'desc' => 'Campo que liga drink aos pratos. No JetEngine Relations: use "jet_related_XXX". Se usa meta field: selecione o campo com IDs dos pratos.'],
            'context_msg' => ['label' => 'Mensagem Contextual', 'desc' => 'Ex: "Excelente drink de abertura!" (texto livre)'],
        ];
        ?>
        <form method="post">
            <?php wp_nonce_field('mdd_general_nonce'); ?>

            <!-- SEÇÃO 1: CPT dos Drinks -->
            <div class="mdd-card">
                <h2>🔗 Origem dos Drinks</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Selecione um ou mais CPTs onde seus drinks/bebidas estão cadastrados.
                    Se usa JetEngine, vá em <strong>JetEngine → Post Types</strong> e veja o slug.
                    Marque vários para juntar diferentes CPTs no mesmo Display.
                </p>
                <div class="mdd-field">
                    <label>CPTs de Drinks / Bebidas</label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px">
                        <?php
                        $selected_pts = get_option('mdd_drink_post_types', []);
                        // Backward compat: if old single value exists, convert
                        if (empty($selected_pts) && $current_pt) $selected_pts = [$current_pt];
                        if (!is_array($selected_pts)) $selected_pts = [$selected_pts];
                        foreach ($post_types as $pt):
                            $checked = in_array($pt->name, $selected_pts);
                            $count = wp_count_posts($pt->name);
                            $pub = isset($count->publish) ? intval($count->publish) : 0;
                        ?>
                        <label style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:<?php echo $checked ? 'rgba(200,150,46,.12)' : 'rgba(255,255,255,.03)'; ?>;border:1.5px solid <?php echo $checked ? 'var(--mdd-accent)' : 'var(--mdd-border)'; ?>;border-radius:10px;cursor:pointer;font-size:.85rem;transition:all .2s">
                            <input type="checkbox" name="mdd_drink_post_types[]" value="<?php echo esc_attr($pt->name); ?>" <?php checked($checked); ?> style="accent-color:var(--mdd-accent)">
                            <strong><?php echo esc_html($pt->label); ?></strong>
                            <span style="font-size:.75rem;color:var(--mdd-muted)">(<?php echo esc_html($pt->name); ?> · <?php echo $pub; ?>)</span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($drink_count > 0): ?>
                    <p style="margin-top:8px;font-size:.82rem;color:var(--mdd-success)">✅ <?php echo $drink_count; ?> drink(s) encontrados no CPT principal.</p>
                <?php endif; ?>
            </div>

            <!-- SEÇÃO 2: CPT dos Pratos (harmonização) -->
            <div class="mdd-card">
                <h2>🍽️ Harmonização — CPT de Pratos/Comidas <span style="font-size:.7rem;background:rgba(249,115,22,.12);color:var(--mdd-accent);padding:2px 8px;border-radius:4px;margin-left:8px;vertical-align:middle">OPCIONAL</span></h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Se seu cardápio tem pratos cadastrados em outro CPT, selecione aqui. O Quiz usará esses pratos para sugerir harmonizações: <em>"Sabia que o Moscow Mule combina com Ceviche?"</em>.
                    Se não tem CPT de pratos, deixe em branco — o Quiz funciona sem esta opção.
                </p>
                <div class="mdd-field">
                    <label for="mdd_food_post_type">CPT de Pratos/Comidas</label>
                    <select name="mdd_food_post_type" id="mdd_food_post_type" class="mdd-select" style="max-width:400px">
                        <option value="">— Nenhum (desativar harmonização)</option>
                        <?php foreach ($post_types as $pt):
                            if ($pt->name === $current_pt) continue; // Não mostra o CPT de drinks
                        ?>
                            <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($food_pt, $pt->name); ?>>
                                <?php echo esc_html($pt->label . ' (' . $pt->name . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Visibilidade controlada pelo toggle "Ocultar do Display" no metabox de cada drink -->

            <!-- SEÇÃO 4: Mapeador de Campos -->
            <div class="mdd-card">
                <h2>🗺️ Mapeador de Campos</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:8px;line-height:1.6">
                    Associe os campos do seu CPT aos dados que o plugin precisa. O sistema detecta automaticamente os meta fields disponíveis.
                    Se deixar em branco, o plugin tenta encontrar automaticamente pelos nomes mais comuns.
                </p>

                <?php if (empty($meta_keys)): ?>
                    <div style="padding:20px;background:#111;border-radius:10px;text-align:center;color:var(--mdd-muted);margin:16px 0">
                        <p style="margin:0">Nenhum meta field encontrado no CPT "<strong style="color:var(--mdd-text)"><?php echo esc_html($current_pt); ?></strong>".</p>
                        <p style="margin:8px 0 0;font-size:.82rem">Cadastre pelo menos 1 drink publicado para que os campos apareçam aqui.</p>
                    </div>
                <?php else: ?>
                    <p style="font-size:.78rem;color:var(--mdd-muted);margin-bottom:16px">
                        📊 <?php echo count($meta_keys); ?> campo(s) detectados no CPT "<?php echo esc_html($current_pt); ?>":
                        <code style="font-size:.72rem"><?php echo esc_html(implode(', ', array_slice($meta_keys, 0, 15))); ?><?php echo count($meta_keys) > 15 ? '...' : ''; ?></code>
                    </p>

                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
                    <?php foreach ($field_labels as $field_key => $info):
                        $current_val = isset($field_map[$field_key]) ? $field_map[$field_key] : '';
                        $auto = MDD_Settings::auto_detect_field($field_key, $meta_keys);
                    ?>
                        <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 16px">
                            <label style="font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);display:block;margin-bottom:6px">
                                <?php echo esc_html($info['label']); ?>
                            </label>
                            <select name="mdd_map_<?php echo $field_key; ?>" style="width:100%;margin-bottom:6px">
                                <option value="">— Automático<?php echo $auto ? ' (detectado: ' . $auto . ')' : ''; ?></option>
                                <?php foreach ($meta_keys as $mk): ?>
                                    <option value="<?php echo esc_attr($mk); ?>" <?php selected($current_val, $mk); ?>>
                                        <?php echo esc_html($mk); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color:var(--mdd-muted);font-size:.72rem"><?php echo esc_html($info['desc']); ?></small>
                            <?php if ($current_val): ?>
                                <span style="display:block;margin-top:4px;font-size:.72rem;color:var(--mdd-success)">✓ Mapeado: <?php echo esc_html($current_val); ?></span>
                            <?php elseif ($auto): ?>
                                <span style="display:block;margin-top:4px;font-size:.72rem;color:var(--mdd-accent)">↻ Auto-detectado: <?php echo esc_html($auto); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEÇÃO 4: Cores -->
            <div class="mdd-card">
                <h2>🎨 Cores das Telas de Exibição</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:20px;line-height:1.6">
                    Aplicadas nas telas de TV, Tablet e Quiz. Não afetam este painel admin. Use as cores da identidade do seu estabelecimento.
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <!-- Color inputs -->
                    <div>
                        <div class="mdd-color-row" style="flex-direction:column;gap:16px">
                            <div class="mdd-color-field">
                                <label>Cor Primária</label>
                                <input type="color" name="mdd_primary_color" id="mdd_pri" value="<?php echo esc_attr($pri); ?>" style="height:42px;width:100%;border-radius:8px;border:1px solid var(--mdd-border);background:#111;cursor:pointer;padding:3px" oninput="mddPreview()">
                                <small style="color:var(--mdd-muted);font-size:.73rem">Títulos, preços, barra de progresso, destaques visuais.</small>
                            </div>
                            <div class="mdd-color-field">
                                <label>Cor de Fundo</label>
                                <input type="color" name="mdd_secondary_color" id="mdd_sec" value="<?php echo esc_attr($sec); ?>" style="height:42px;width:100%;border-radius:8px;border:1px solid var(--mdd-border);background:#111;cursor:pointer;padding:3px" oninput="mddPreview()">
                                <small style="color:var(--mdd-muted);font-size:.73rem">Fundo de todas as telas. Recomendado: cor escura para contraste.</small>
                            </div>
                            <div class="mdd-color-field">
                                <label>Cor de Destaque</label>
                                <input type="color" name="mdd_accent_color" id="mdd_acc" value="<?php echo esc_attr($acc); ?>" style="height:42px;width:100%;border-radius:8px;border:1px solid var(--mdd-border);background:#111;cursor:pointer;padding:3px" oninput="mddPreview()">
                                <small style="color:var(--mdd-muted);font-size:.73rem">Botões de ação (ex: "Faça o Quiz"), badges, alertas.</small>
                            </div>
                        </div>
                    </div>
                    <!-- Live Preview -->
                    <div>
                        <label style="font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);display:block;margin-bottom:8px">Preview ao vivo</label>
                        <div id="mdd-color-preview" style="border-radius:12px;overflow:hidden;border:1px solid var(--mdd-border);background:<?php echo esc_attr($sec); ?>;padding:20px;min-height:200px">
                            <div style="text-align:center;margin-bottom:12px">
                                <div id="prev-title" style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:<?php echo esc_attr($pri); ?>">Moscow Mule</div>
                                <div style="color:rgba(255,255,255,.5);font-size:12px;margin-top:4px">Vodka, ginger beer, limão</div>
                            </div>
                            <div style="display:flex;justify-content:center;gap:8px;margin-bottom:16px">
                                <div style="width:60px;height:60px;border-radius:8px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:28px">🍸</div>
                            </div>
                            <div id="prev-price" style="text-align:center;font-size:20px;font-weight:700;color:<?php echo esc_attr($pri); ?>;margin-bottom:12px">R$ 29,90</div>
                            <div style="text-align:center">
                                <span id="prev-btn" style="display:inline-block;padding:8px 20px;border-radius:8px;background:<?php echo esc_attr($acc); ?>;color:#fff;font-size:13px;font-weight:600">Faça o Quiz 🧩</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="mdd_save_general" class="button button-primary button-large">
                💾 Salvar Configurações
            </button>
        </form>

        <script>
        function mddPreview(){
            var p=document.getElementById('mdd_pri').value;
            var s=document.getElementById('mdd_sec').value;
            var a=document.getElementById('mdd_acc').value;
            document.getElementById('mdd-color-preview').style.background=s;
            document.getElementById('prev-title').style.color=p;
            document.getElementById('prev-price').style.color=p;
            document.getElementById('prev-btn').style.background=a;
        }
        </script>
        <?php
    }

    // ─── Logos Tab ───
    private static function render_logos_tab() {
        if (isset($_POST['mdd_save_logos']) && check_admin_referer('mdd_logos_nonce')) {
            update_option('mdd_establishment_logo', intval($_POST['mdd_establishment_logo'] ?? 0));
            update_option('mdd_event_mode', intval($_POST['mdd_event_mode'] ?? 0));
            update_option('mdd_event_logo', intval($_POST['mdd_event_logo'] ?? 0));
            update_option('mdd_event_name', sanitize_text_field($_POST['mdd_event_name'] ?? ''));
            update_option('mdd_event_start', sanitize_text_field($_POST['mdd_event_start'] ?? ''));
            update_option('mdd_event_end', sanitize_text_field($_POST['mdd_event_end'] ?? ''));
            update_option('mdd_logo_max_height_tv', intval($_POST['mdd_logo_max_height_tv'] ?? 60));
            update_option('mdd_logo_max_height_tablet', intval($_POST['mdd_logo_max_height_tablet'] ?? 50));
            update_option('mdd_logo_max_height_quiz', intval($_POST['mdd_logo_max_height_quiz'] ?? 80));
            echo '<div class="notice notice-success"><p>✅ Logos atualizados!</p></div>';
        }

        $estab_logo_id  = get_option('mdd_establishment_logo', '');
        $estab_logo_url = $estab_logo_id ? wp_get_attachment_url($estab_logo_id) : '';
        $event_logo_id  = get_option('mdd_event_logo', '');
        $event_logo_url = $event_logo_id ? wp_get_attachment_url($event_logo_id) : '';
        $event_mode     = get_option('mdd_event_mode', 0);
        $h_tv    = get_option('mdd_logo_max_height_tv', 60);
        $h_tab   = get_option('mdd_logo_max_height_tablet', 50);
        $h_quiz  = get_option('mdd_logo_max_height_quiz', 80);
        $sec     = get_option('mdd_secondary_color', '#1A1A2E');

        // Get logo metadata for validation display
        $logo_meta = '';
        if ($estab_logo_id) {
            $meta = wp_get_attachment_metadata($estab_logo_id);
            if ($meta) {
                $filesize = filesize(get_attached_file($estab_logo_id));
                $logo_meta = ($meta['width'] ?? '?') . '×' . ($meta['height'] ?? '?') . 'px · ' . size_format($filesize);
                $mime = get_post_mime_type($estab_logo_id);
                $logo_meta .= ' · ' . strtoupper(str_replace('image/', '', $mime));
            }
        }
        ?>
        <form method="post">
            <?php wp_nonce_field('mdd_logos_nonce'); ?>

            <!-- Logo do Estabelecimento -->
            <div class="mdd-card">
                <h2>🏢 Logo do Estabelecimento</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:14px;line-height:1.6">
                    Logo padrão exibido na TV, Tablet e Quiz. Aparece em todas as telas quando não há logo de evento ativo.
                </p>

                <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">
                    <!-- Upload -->
                    <div style="flex:1;min-width:250px">
                        <div class="mdd-logo-upload" data-target="mdd_establishment_logo">
                            <input type="hidden" name="mdd_establishment_logo" id="mdd_establishment_logo" value="<?php echo esc_attr($estab_logo_id); ?>">
                            <div class="mdd-logo-preview" id="mdd_establishment_logo_preview" style="width:200px;height:100px">
                                <?php if ($estab_logo_url): ?>
                                    <img src="<?php echo esc_url($estab_logo_url); ?>" alt="Logo">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span>Nenhum logo</span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:8px;margin-top:8px">
                                <button type="button" class="button mdd-upload-btn" data-target="mdd_establishment_logo">Selecionar Logo</button>
                                <button type="button" class="button mdd-remove-btn" data-target="mdd_establishment_logo" <?php echo !$estab_logo_id ? 'style="display:none"' : ''; ?>>Remover</button>
                            </div>
                        </div>
                        <?php if ($logo_meta): ?>
                            <p style="margin-top:8px;font-size:.78rem;color:var(--mdd-muted)">📐 <?php echo esc_html($logo_meta); ?></p>
                        <?php endif; ?>

                        <!-- Validation rules -->
                        <div style="margin-top:12px;padding:12px 16px;background:#111;border-radius:8px;border:1px solid var(--mdd-border)">
                            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);margin-bottom:6px">Requisitos recomendados</div>
                            <div style="font-size:.8rem;color:var(--mdd-text);line-height:1.8">
                                📏 Dimensões: 400×150px (mínimo 200×80px)<br>
                                📁 Formato: PNG com fundo transparente (ideal) ou SVG<br>
                                ⚖️ Tamanho: até 500KB<br>
                                ↔️ Orientação: horizontal (logos verticais ficam desproporcionais)
                            </div>
                        </div>
                    </div>

                    <!-- Preview multi-tela -->
                    <div style="flex:1;min-width:280px">
                        <label style="font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);display:block;margin-bottom:8px">Preview nas telas</label>
                        <?php $preview_logo = $estab_logo_url ?: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="40"><rect fill="%23333" width="120" height="40" rx="6"/><text x="60" y="25" text-anchor="middle" fill="%23888" font-size="11" font-family="sans-serif">LOGO</text></svg>'; ?>

                        <div style="display:flex;flex-direction:column;gap:10px">
                            <!-- TV preview -->
                            <div style="background:<?php echo esc_attr($sec); ?>;border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;border:1px solid var(--mdd-border)">
                                <img src="<?php echo esc_url($preview_logo); ?>" alt="TV" style="height:<?php echo intval($h_tv); ?>px;max-width:160px;object-fit:contain" id="prev-logo-tv">
                                <div style="flex:1;text-align:right">
                                    <span style="font-size:.65rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.1em">📺 TV</span>
                                </div>
                            </div>
                            <!-- Tablet preview -->
                            <div style="background:<?php echo esc_attr($sec); ?>;border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:10px;border:1px solid var(--mdd-border)">
                                <img src="<?php echo esc_url($preview_logo); ?>" alt="Tablet" style="height:<?php echo intval($h_tab); ?>px;max-width:120px;object-fit:contain" id="prev-logo-tab">
                                <div style="flex:1;text-align:right">
                                    <span style="font-size:.65rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.1em">📱 Tablet</span>
                                </div>
                            </div>
                            <!-- Quiz preview -->
                            <div style="background:<?php echo esc_attr($sec); ?>;border-radius:8px;padding:14px;text-align:center;border:1px solid var(--mdd-border)">
                                <img src="<?php echo esc_url($preview_logo); ?>" alt="Quiz" style="height:<?php echo intval($h_quiz); ?>px;max-width:200px;object-fit:contain" id="prev-logo-quiz">
                                <div style="margin-top:6px"><span style="font-size:.65rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.1em">🧩 Quiz</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controle de tamanho -->
            <div class="mdd-card">
                <h2>📐 Tamanho do Logo por Tela</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Ajuste a altura máxima do logo em cada tipo de tela. O logo mantém a proporção automaticamente.
                </p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
                    <div class="mdd-field">
                        <label>📺 TV (altura máx.)</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_logo_max_height_tv" value="<?php echo intval($h_tv); ?>" min="30" max="120" step="5" oninput="document.getElementById('val-tv').textContent=this.value+'px';document.getElementById('prev-logo-tv').style.height=this.value+'px'" style="flex:1">
                            <span id="val-tv" style="font-size:.85rem;min-width:40px;color:var(--mdd-accent2)"><?php echo intval($h_tv); ?>px</span>
                        </div>
                    </div>
                    <div class="mdd-field">
                        <label>📱 Tablet (altura máx.)</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_logo_max_height_tablet" value="<?php echo intval($h_tab); ?>" min="25" max="100" step="5" oninput="document.getElementById('val-tab').textContent=this.value+'px';document.getElementById('prev-logo-tab').style.height=this.value+'px'" style="flex:1">
                            <span id="val-tab" style="font-size:.85rem;min-width:40px;color:var(--mdd-accent2)"><?php echo intval($h_tab); ?>px</span>
                        </div>
                    </div>
                    <div class="mdd-field">
                        <label>🧩 Quiz (altura máx.)</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_logo_max_height_quiz" value="<?php echo intval($h_quiz); ?>" min="40" max="160" step="5" oninput="document.getElementById('val-quiz').textContent=this.value+'px';document.getElementById('prev-logo-quiz').style.height=this.value+'px'" style="flex:1">
                            <span id="val-quiz" style="font-size:.85rem;min-width:40px;color:var(--mdd-accent2)"><?php echo intval($h_quiz); ?>px</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modo Evento -->
            <div class="mdd-card">
                <h2>
                    🎉 Modo Evento
                    <label class="mdd-switch">
                        <input type="checkbox" name="mdd_event_mode" value="1" <?php checked($event_mode, 1); ?> id="mdd_event_mode_toggle">
                        <span class="mdd-slider"></span>
                    </label>
                </h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);line-height:1.6">
                    Para <strong>eventos particulares</strong> (casamento, formatura, corporativo). Quando ativo, substitui o logo do restaurante em todas as telas.
                    <br>Agende início e fim — o evento ativa e desativa automaticamente! Ou deixe vazio para controle manual.
                </p>

                <div class="mdd-event-fields" id="mdd_event_fields" <?php echo !$event_mode ? 'style="display:none"' : ''; ?>>
                    <div class="mdd-field">
                        <label>Nome do Evento</label>
                        <input type="text" name="mdd_event_name" value="<?php echo esc_attr(get_option('mdd_event_name', '')); ?>" placeholder="Ex: Casamento João & Maria" style="max-width:400px">
                        <p class="description">Badge nas TVs e título no Tablet. Mantenha curto (2-4 palavras).</p>
                    </div>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
                        <div class="mdd-field" style="margin:0;width:200px">
                            <label>📅 Início do Evento</label>
                            <input type="datetime-local" name="mdd_event_start" value="<?php echo esc_attr(get_option('mdd_event_start', '')); ?>" style="width:100%">
                        </div>
                        <div class="mdd-field" style="margin:0;width:200px">
                            <label>📅 Fim do Evento</label>
                            <input type="datetime-local" name="mdd_event_end" value="<?php echo esc_attr(get_option('mdd_event_end', '')); ?>" style="width:100%">
                            <small style="color:var(--mdd-muted);font-size:.7rem">Ao atingir o fim, o modo evento desativa automaticamente.</small>
                        </div>
                    </div>
                    <div class="mdd-field">
                        <label>Logo do Evento</label>
                        <div class="mdd-logo-upload" data-target="mdd_event_logo">
                            <input type="hidden" name="mdd_event_logo" id="mdd_event_logo" value="<?php echo esc_attr($event_logo_id); ?>">
                            <div class="mdd-logo-preview" id="mdd_event_logo_preview">
                                <?php if ($event_logo_url): ?>
                                    <img src="<?php echo esc_url($event_logo_url); ?>" alt="Event Logo">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span>Nenhum logo</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button mdd-upload-btn" data-target="mdd_event_logo">Selecionar Logo do Evento</button>
                            <button type="button" class="button mdd-remove-btn" data-target="mdd_event_logo" <?php echo !$event_logo_id ? 'style="display:none"' : ''; ?>>Remover</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hierarquia -->
            <div class="mdd-card mdd-info-card">
                <p style="margin:0;font-size:.85rem;line-height:1.7">
                    <strong>ℹ️ Hierarquia de prioridade do logo:</strong><br>
                    <strong>1º</strong> Logo do Token (se configurado nas opções avançadas do dispositivo)<br>
                    <strong>2º</strong> Logo do Evento (se Modo Evento ativo)<br>
                    <strong>3º</strong> Logo do Estabelecimento (padrão)
                </p>
            </div>

            <button type="submit" name="mdd_save_logos" class="button button-primary button-large">
                💾 Salvar Logos
            </button>
        </form>
        <?php
    }

    // ─── TV Tab ───
    private static function render_tv_tab() {
        if (isset($_POST['mdd_save_tv']) && check_admin_referer('mdd_tv_nonce')) {
            update_option('mdd_tv_slide_duration', intval($_POST['mdd_tv_slide_duration'] ?? 8));
            update_option('mdd_tv_transition', sanitize_text_field($_POST['mdd_tv_transition'] ?? 'fade'));
            update_option('mdd_tv_layout', sanitize_text_field($_POST['mdd_tv_layout'] ?? 'fullscreen'));
            update_option('mdd_tv_show_price', intval($_POST['mdd_tv_show_price'] ?? 0));
            update_option('mdd_tv_show_qr', intval($_POST['mdd_tv_show_qr'] ?? 0));
            update_option('mdd_tv_qr_position', sanitize_text_field($_POST['mdd_tv_qr_position'] ?? 'bottom-right'));
            update_option('mdd_tv_qr_text', sanitize_text_field($_POST['mdd_tv_qr_text'] ?? 'Faça o Quiz'));
            // TV text colors
            update_option('mdd_tv_title_color', sanitize_hex_color($_POST['mdd_tv_title_color'] ?? ''));
            update_option('mdd_tv_desc_color', sanitize_hex_color($_POST['mdd_tv_desc_color'] ?? ''));
            update_option('mdd_tv_price_color', sanitize_hex_color($_POST['mdd_tv_price_color'] ?? ''));
            update_option('mdd_tv_cat_color', sanitize_hex_color($_POST['mdd_tv_cat_color'] ?? ''));
            // TV font sizes
            update_option('mdd_tv_title_size', intval($_POST['mdd_tv_title_size'] ?? 32));
            update_option('mdd_tv_desc_size', intval($_POST['mdd_tv_desc_size'] ?? 14));
            update_option('mdd_tv_price_size', intval($_POST['mdd_tv_price_size'] ?? 24));
            update_option('mdd_tv_cat_size', intval($_POST['mdd_tv_cat_size'] ?? 12));
            // TV background image
            $tv_bg_img = sanitize_text_field($_POST['mdd_tv_bg_image'] ?? '');
            if (is_numeric($tv_bg_img)) $tv_bg_img = wp_get_attachment_url(intval($tv_bg_img));
            update_option('mdd_tv_bg_image', esc_url_raw($tv_bg_img));

            // Save custom slides
            $slides = [];
            if (!empty($_POST['mdd_slide_title']) && is_array($_POST['mdd_slide_title'])) {
                foreach ($_POST['mdd_slide_title'] as $i => $title) {
                    if (empty(trim($title))) continue;
                    $slides[] = [
                        'title'       => sanitize_text_field($title),
                        'content'     => sanitize_textarea_field($_POST['mdd_slide_content'][$i] ?? ''),
                        'bg_color'    => sanitize_hex_color($_POST['mdd_slide_bg'][$i] ?? '#E8593C'),
                        'text_color'  => sanitize_hex_color($_POST['mdd_slide_text_color'][$i] ?? '#FFFFFF'),
                        'bg_image'    => esc_url_raw($_POST['mdd_slide_bg_image'][$i] ?? ''),
                        'type'        => sanitize_text_field($_POST['mdd_slide_type'][$i] ?? 'promo'),
                        'days'        => array_map('intval', $_POST['mdd_slide_days'][$i] ?? []),
                        'time_start'  => sanitize_text_field($_POST['mdd_slide_start'][$i] ?? ''),
                        'time_end'    => sanitize_text_field($_POST['mdd_slide_end'][$i] ?? ''),
                        'end_behavior'=> sanitize_text_field($_POST['mdd_slide_end_behavior'][$i] ?? 'hide'),
                        'end_message' => sanitize_text_field($_POST['mdd_slide_end_msg'][$i] ?? ''),
                        'active'      => intval($_POST['mdd_slide_active'][$i] ?? 1),
                    ];
                }
            }
            update_option('mdd_tv_custom_slides', $slides);

            echo '<div class="notice notice-success"><p>✅ Configurações da TV salvas!</p></div>';
        }

        $layout    = get_option('mdd_tv_layout', 'fullscreen');
        $duration  = get_option('mdd_tv_slide_duration', 8);
        $transition= get_option('mdd_tv_transition', 'fade');
        $show_price= get_option('mdd_tv_show_price', 1);
        $show_qr   = get_option('mdd_tv_show_qr', 1);
        $qr_pos    = get_option('mdd_tv_qr_position', 'bottom-right');
        $qr_text   = get_option('mdd_tv_qr_text', 'Faça o Quiz');
        $slides    = get_option('mdd_tv_custom_slides', []);
        $sec       = get_option('mdd_secondary_color', '#1A1A2E');
        $pri       = get_option('mdd_primary_color', '#C8962E');
        ?>
        <form method="post">
            <?php wp_nonce_field('mdd_tv_nonce'); ?>

            <!-- Info -->
            <div class="mdd-card mdd-info-card">
                <p style="margin:0;font-size:.85rem;line-height:1.7">
                    <strong>📺 Configuração Global da TV</strong> — Estas configurações valem para <strong>todas as TVs</strong>.
                    Se precisar de algo diferente em uma TV específica, use as "Opções Avançadas" ao gerar o token na aba Tokens (override por dispositivo).
                </p>
            </div>

            <!-- Layout Selector -->
            <div class="mdd-card">
                <h2>🖥️ Layout do Slideshow</h2>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
                    <?php
                    $layouts = [
                        'fullscreen' => ['label' => 'Fullscreen', 'desc' => 'Foto 55% + info ao lado', 'ideal' => 'TVs 55"+'],
                        'split'      => ['label' => 'Split',      'desc' => 'Foto centralizada + info', 'ideal' => 'TVs 32-50"'],
                        'grid'       => ['label' => 'Grid',       'desc' => '4 drinks por vez',         'ideal' => 'Bar/Balcão'],
                    ];
                    foreach ($layouts as $val => $info):
                        $active = ($layout === $val);
                    ?>
                    <label style="background:#111;border:2px solid <?php echo $active ? 'var(--mdd-accent)' : 'var(--mdd-border)'; ?>;border-radius:12px;padding:16px;cursor:pointer;transition:border-color .2s<?php echo $active ? ';box-shadow:0 0 0 2px rgba(249,115,22,.15)' : ''; ?>">
                        <input type="radio" name="mdd_tv_layout" value="<?php echo $val; ?>" <?php checked($layout, $val); ?> style="display:none">
                        <!-- Mini preview -->
                        <div style="background:<?php echo esc_attr($sec); ?>;border-radius:6px;height:60px;margin-bottom:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid var(--mdd-border)">
                            <?php if ($val === 'fullscreen'): ?>
                                <div style="display:flex;width:100%;height:100%"><div style="flex:0 0 55%;background:rgba(255,255,255,.08)"></div><div style="flex:1;padding:6px"><div style="width:60%;height:4px;background:<?php echo esc_attr($pri); ?>;border-radius:2px;margin-bottom:4px"></div><div style="width:40%;height:3px;background:rgba(255,255,255,.15);border-radius:2px"></div></div></div>
                            <?php elseif ($val === 'split'): ?>
                                <div style="display:flex;width:100%;height:100%"><div style="flex:1;display:flex;align-items:center;justify-content:center"><div style="width:32px;height:32px;background:rgba(255,255,255,.08);border-radius:4px"></div></div><div style="flex:1;padding:8px 6px"><div style="width:70%;height:4px;background:<?php echo esc_attr($pri); ?>;border-radius:2px;margin-bottom:4px"></div><div style="width:50%;height:3px;background:rgba(255,255,255,.15);border-radius:2px"></div></div></div>
                            <?php else: ?>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:3px;padding:4px;width:100%;height:100%"><?php for($i=0;$i<4;$i++): ?><div style="background:rgba(255,255,255,.06);border-radius:3px"></div><?php endfor; ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight:600;font-size:.9rem;margin-bottom:2px;color:<?php echo $active ? 'var(--mdd-accent)' : 'var(--mdd-text)'; ?>"><?php echo $info['label']; ?></div>
                        <div style="font-size:.75rem;color:var(--mdd-muted)"><?php echo $info['desc']; ?></div>
                        <div style="font-size:.7rem;color:var(--mdd-muted);margin-top:4px">Ideal: <?php echo $info['ideal']; ?></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Slideshow Config -->
            <div class="mdd-card">
                <h2>⚙️ Configurações do Slideshow</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
                    <div class="mdd-field">
                        <label>Duração por slide</label>
                        <select name="mdd_tv_slide_duration" class="mdd-select">
                            <?php foreach ([5 => '5s (rápido)', 8 => '8s (recomendado)', 10 => '10s', 15 => '15s', 20 => '20s (lento)'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php selected($duration, $v); ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mdd-field">
                        <label>Transição</label>
                        <select name="mdd_tv_transition" class="mdd-select">
                            <option value="fade" <?php selected($transition, 'fade'); ?>>Fade (suave)</option>
                            <option value="slide" <?php selected($transition, 'slide'); ?>>Slide (lateral)</option>
                            <option value="zoom" <?php selected($transition, 'zoom'); ?>>Zoom (impacto)</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;margin-top:20px">
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_tv_show_price" value="1" <?php checked($show_price); ?>> Exibir preço nos slides
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">Mostra o valor do drink (ex: R$ 29,90) em cada slide.</p>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_tv_show_qr" value="1" <?php checked($show_qr); ?> id="mdd_qr_toggle"> Exibir QR Code do Quiz
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">Mostra um QR Code fixo convidando para o Quiz.</p>
                        <div id="mdd_qr_options" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--mdd-border);display:flex;gap:14px;flex-wrap:wrap<?php echo !$show_qr ? ';display:none' : ''; ?>">
                            <div class="mdd-field" style="margin:0;flex:1;min-width:140px">
                                <label>Posição</label>
                                <select name="mdd_tv_qr_position" class="mdd-select">
                                    <option value="bottom-right" <?php selected($qr_pos, 'bottom-right'); ?>>Inferior direito</option>
                                    <option value="bottom-left" <?php selected($qr_pos, 'bottom-left'); ?>>Inferior esquerdo</option>
                                    <option value="top-right" <?php selected($qr_pos, 'top-right'); ?>>Superior direito</option>
                                </select>
                            </div>
                            <div class="mdd-field" style="margin:0;flex:1;min-width:140px">
                                <label>Texto abaixo do QR</label>
                                <input type="text" name="mdd_tv_qr_text" value="<?php echo esc_attr($qr_text); ?>" placeholder="Faça o Quiz">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Slides -->
            <!-- TV Text Colors + Font Sizes -->
            <div class="mdd-card">
                <h2>🎨 Textos do Display TV — Cor e Tamanho</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Personalize cor e tamanho de cada texto no display. Tamanhos em px (mínimo). O display usa <code>clamp()</code> para escalar proporcionalmente à tela.
                </p>
                <?php
                $tv_title_c = get_option('mdd_tv_title_color', '');
                $tv_desc_c  = get_option('mdd_tv_desc_color', '');
                $tv_price_c = get_option('mdd_tv_price_color', '');
                $tv_cat_c   = get_option('mdd_tv_cat_color', '');
                $tv_title_s = get_option('mdd_tv_title_size', 32);
                $tv_desc_s  = get_option('mdd_tv_desc_size', 14);
                $tv_price_s = get_option('mdd_tv_price_size', 24);
                $tv_cat_s   = get_option('mdd_tv_cat_size', 12);
                ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px">
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:8px;display:block">Título (nome do drink)</label>
                        <input type="color" name="mdd_tv_title_color" value="<?php echo esc_attr($tv_title_c ?: '#FFFFFF'); ?>" style="height:32px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_tv_title_size" min="18" max="80" value="<?php echo intval($tv_title_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($tv_title_s); ?>px</span>
                        </div>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:8px;display:block">Descrição</label>
                        <input type="color" name="mdd_tv_desc_color" value="<?php echo esc_attr($tv_desc_c ?: '#FFFFFF'); ?>" style="height:32px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_tv_desc_size" min="10" max="36" value="<?php echo intval($tv_desc_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($tv_desc_s); ?>px</span>
                        </div>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:8px;display:block">Preço</label>
                        <input type="color" name="mdd_tv_price_color" value="<?php echo esc_attr($tv_price_c ?: '#C8962E'); ?>" style="height:32px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_tv_price_size" min="16" max="64" value="<?php echo intval($tv_price_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($tv_price_s); ?>px</span>
                        </div>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:8px;display:block">Categoria</label>
                        <input type="color" name="mdd_tv_cat_color" value="<?php echo esc_attr($tv_cat_c ?: '#C8962E'); ?>" style="height:32px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px;margin-bottom:8px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_tv_cat_size" min="8" max="24" value="<?php echo intval($tv_cat_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($tv_cat_s); ?>px</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TV Background Image -->
            <div class="mdd-card">
                <h2>🖼️ Imagem de Fundo do Display</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:12px;line-height:1.6">
                    Imagem que aparece por trás dos slides na TV. Substitui a cor de fundo global. Ideal para identidade visual personalizada.
                </p>
                <?php $tv_bg_img = get_option('mdd_tv_bg_image', ''); ?>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="mdd_tv_bg_image" id="mdd_tv_bg_image" value="<?php echo esc_attr($tv_bg_img); ?>">
                    <button type="button" class="button mdd-upload-btn" data-target="mdd_tv_bg_image" data-save-url="1" style="font-size:.82rem">Selecionar Imagem</button>
                    <button type="button" class="button mdd-remove-btn" data-target="mdd_tv_bg_image" style="font-size:.82rem;<?php echo empty($tv_bg_img) ? 'display:none' : ''; ?>">Remover</button>
                </div>
                <div class="mdd-logo-preview" id="mdd_tv_bg_image_preview">
                    <?php if ($tv_bg_img): ?>
                        <img src="<?php echo esc_url($tv_bg_img); ?>">
                    <?php else: ?>
                        <span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span>
                    <?php endif; ?>
                </div>
                <small style="color:var(--mdd-muted);font-size:.72rem">Formatos: JPG, PNG, WebP. Paisagem (1920×1080px). Até 500KB.</small>
            </div>

            <!-- Custom Slides -->
            <div class="mdd-card">
                <h2>📝 Slides Personalizados <span style="font-size:.7rem;background:rgba(249,115,22,.12);color:var(--mdd-accent);padding:2px 8px;border-radius:4px;margin-left:8px;vertical-align:middle">NOVO</span></h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Crie slides de texto que entram na rotação entre os drinks. Ideal para promoções, Happy Hour, avisos e boas-vindas.
                    Cada slide pode ter <strong>agendamento por dia e horário</strong> — o slide entra e sai automaticamente.
                </p>

                <div id="mdd-custom-slides">
                    <?php if (!empty($slides)): foreach ($slides as $si => $sl): ?>
                    <div class="mdd-slide-item" style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:18px;margin-bottom:14px;position:relative">
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">
                            <div class="mdd-field" style="flex:2;min-width:200px;margin:0">
                                <label>Título interno</label>
                                <input type="text" name="mdd_slide_title[<?php echo $si; ?>]" value="<?php echo esc_attr($sl['title']); ?>" placeholder="Ex: Happy Hour Sexta">
                            </div>
                            <div class="mdd-field" style="flex:1;min-width:100px;margin:0">
                                <label>Tipo</label>
                                <select name="mdd_slide_type[<?php echo $si; ?>]">
                                    <option value="promo" <?php selected($sl['type'] ?? '', 'promo'); ?>>Promoção</option>
                                    <option value="welcome" <?php selected($sl['type'] ?? '', 'welcome'); ?>>Boas-vindas</option>
                                    <option value="info" <?php selected($sl['type'] ?? '', 'info'); ?>>Aviso</option>
                                    <option value="quiz" <?php selected($sl['type'] ?? '', 'quiz'); ?>>QR Quiz grande</option>
                                </select>
                            </div>
                            <div class="mdd-field" style="width:80px;margin:0">
                                <label>Cor fundo</label>
                                <input type="color" name="mdd_slide_bg[<?php echo $si; ?>]" value="<?php echo esc_attr($sl['bg_color'] ?? '#E8593C'); ?>" style="height:36px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px">
                            </div>
                            <div class="mdd-field" style="width:80px;margin:0">
                                <label>Cor texto</label>
                                <input type="color" name="mdd_slide_text_color[<?php echo $si; ?>]" value="<?php echo esc_attr($sl['text_color'] ?? '#FFFFFF'); ?>" style="height:36px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px">
                            </div>
                        </div>
                        <div class="mdd-field" style="margin-bottom:12px">
                            <label>Conteúdo do slide</label>
                            <textarea name="mdd_slide_content[<?php echo $si; ?>]" rows="2" placeholder="Ex: Happy Hour até 20h — Drinks com 30% off" style="resize:vertical"><?php echo esc_textarea($sl['content'] ?? ''); ?></textarea>
                        </div>
                        <div class="mdd-field" style="margin-bottom:12px">
                            <label>🖼️ Imagem de fundo (opcional — substitui a cor de fundo)</label>
                            <div style="display:flex;gap:8px;align-items:center">
                                <input type="hidden" name="mdd_slide_bg_image[<?php echo $si; ?>]" id="mdd_slide_img_<?php echo $si; ?>" value="<?php echo esc_attr($sl['bg_image'] ?? ''); ?>">
                                <button type="button" class="button" onclick="mddSlideUpload('mdd_slide_img_<?php echo $si; ?>')" style="font-size:.82rem">Selecionar Imagem</button>
                                <button type="button" class="button" id="mdd_slide_img_<?php echo $si; ?>_rmv" onclick="mddSlideRemove('mdd_slide_img_<?php echo $si; ?>')" style="font-size:.82rem;<?php echo empty($sl['bg_image']) ? 'display:none' : ''; ?>">Remover</button>
                            </div>
                            <div class="mdd-logo-preview" id="mdd_slide_img_<?php echo $si; ?>_preview">
                                <?php if (!empty($sl['bg_image'])): ?>
                                    <img src="<?php echo esc_url($sl['bg_image']); ?>">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span>
                                <?php endif; ?>
                            </div>
                            <small style="color:var(--mdd-muted);font-size:.72rem">JPG/PNG/WebP. Paisagem (1920×1080). A arte pronta aparece como fundo do slide.</small>
                        </div>
                        <!-- Scheduling -->
                        <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
                            <div class="mdd-field" style="margin:0">
                                <label>Dias</label>
                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                    <?php $day_labels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
                                    $active_days = $sl['days'] ?? [];
                                    foreach ($day_labels as $di => $dl):
                                        $checked = in_array($di, $active_days) ? 'checked' : '';
                                    ?>
                                    <label style="display:inline-flex;align-items:center;gap:2px;padding:4px 8px;background:<?php echo $checked ? 'rgba(249,115,22,.15)' : 'rgba(255,255,255,.04)'; ?>;border-radius:6px;font-size:.75rem;cursor:pointer;border:1px solid <?php echo $checked ? 'var(--mdd-accent)' : 'var(--mdd-border)'; ?>">
                                        <input type="checkbox" name="mdd_slide_days[<?php echo $si; ?>][]" value="<?php echo $di; ?>" <?php echo $checked; ?> style="display:none">
                                        <?php echo $dl; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mdd-field" style="margin:0;width:100px">
                                <label>Início</label>
                                <input type="time" name="mdd_slide_start[<?php echo $si; ?>]" value="<?php echo esc_attr($sl['time_start'] ?? ''); ?>">
                            </div>
                            <div class="mdd-field" style="margin:0;width:100px">
                                <label>Fim</label>
                                <input type="time" name="mdd_slide_end[<?php echo $si; ?>]" value="<?php echo esc_attr($sl['time_end'] ?? ''); ?>">
                            </div>
                            <div class="mdd-field" style="margin:0;flex:1;min-width:150px">
                                <label>Ao encerrar</label>
                                <select name="mdd_slide_end_behavior[<?php echo $si; ?>]">
                                    <option value="hide" <?php selected($sl['end_behavior'] ?? '', 'hide'); ?>>Desaparecer silenciosamente</option>
                                    <option value="message" <?php selected($sl['end_behavior'] ?? '', 'message'); ?>>Mostrar mensagem de encerramento</option>
                                </select>
                            </div>
                            <div class="mdd-field" style="margin:0;flex:1;min-width:180px">
                                <label>Mensagem de encerramento</label>
                                <input type="text" name="mdd_slide_end_msg[<?php echo $si; ?>]" value="<?php echo esc_attr($sl['end_message'] ?? ''); ?>" placeholder="Ex: Happy Hour encerrado!">
                            </div>
                        </div>
                        <input type="hidden" name="mdd_slide_active[<?php echo $si; ?>]" value="1">
                        <div style="text-align:right;margin-top:10px;padding-top:10px;border-top:1px solid var(--mdd-border)"><button type="button" onclick="this.closest('.mdd-slide-item').remove()" style="background:rgba(224,60,60,.12);border:1px solid rgba(224,60,60,.3);color:#e03c3c;cursor:pointer;font-size:13px;padding:6px 16px;border-radius:8px;font-weight:600" title="Remover slide">✕ Remover Slide</button></div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

                <button type="button" class="button" id="mdd-add-slide" style="margin-top:8px">➕ Adicionar Slide Personalizado</button>
            </div>

            <button type="submit" name="mdd_save_tv" class="button button-primary button-large">
                💾 Salvar Configurações TV
            </button>
        </form>

        <script>
        // ─── Slide Image Upload/Remove (inline, no cache issues) ───
        function mddSlideUpload(id) {
            var f = wp.media({ title:'Imagem de Fundo do Slide', button:{text:'Usar esta imagem'}, multiple:false, library:{type:'image'} });
            f.on('select', function(){
                var a = f.state().get('selection').first().toJSON();
                document.getElementById(id).value = a.url;
                document.getElementById(id+'_preview').innerHTML = '<img src="'+a.url+'" alt="Preview">';
                var r = document.getElementById(id+'_rmv'); if(r) r.style.display='';
            });
            f.open();
        }
        function mddSlideRemove(id) {
            document.getElementById(id).value = '';
            document.getElementById(id+'_preview').innerHTML = '<span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span>';
            var r = document.getElementById(id+'_rmv'); if(r) r.style.display='none';
        }

        document.getElementById('mdd_qr_toggle').addEventListener('change', function(){
            document.getElementById('mdd_qr_options').style.display = this.checked ? 'flex' : 'none';
        });

        var slideCounter = <?php echo count($slides); ?>;
        document.getElementById('mdd-add-slide').addEventListener('click', function(){
            var idx = slideCounter++;
            var days = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
            var daysHtml = days.map(function(d,i){
                return '<label style="display:inline-flex;align-items:center;gap:2px;padding:4px 8px;background:rgba(255,255,255,.04);border-radius:6px;font-size:.75rem;cursor:pointer;border:1px solid var(--mdd-border)"><input type="checkbox" name="mdd_slide_days['+idx+'][]" value="'+i+'" style="display:none">'+d+'</label>';
            }).join('');

            var html = '<div class="mdd-slide-item" style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:18px;margin-bottom:14px;position:relative">'
                + '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">'
                + '<div class="mdd-field" style="flex:2;min-width:200px;margin:0"><label>Título interno</label><input type="text" name="mdd_slide_title['+idx+']" placeholder="Ex: Happy Hour Sexta"></div>'
                + '<div class="mdd-field" style="flex:1;min-width:100px;margin:0"><label>Tipo</label><select name="mdd_slide_type['+idx+']"><option value="promo">Promoção</option><option value="welcome">Boas-vindas</option><option value="info">Aviso</option><option value="quiz">QR Quiz grande</option></select></div>'
                + '<div class="mdd-field" style="width:80px;margin:0"><label>Cor fundo</label><input type="color" name="mdd_slide_bg['+idx+']" value="#E8593C" style="height:36px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px"></div>'
                + '<div class="mdd-field" style="width:80px;margin:0"><label>Cor texto</label><input type="color" name="mdd_slide_text_color['+idx+']" value="#FFFFFF" style="height:36px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px"></div>'
                + '</div>'
                + '<div class="mdd-field" style="margin-bottom:12px"><label>Conteúdo do slide</label><textarea name="mdd_slide_content['+idx+']" rows="2" placeholder="Ex: Happy Hour até 20h — Drinks com 30% off" style="resize:vertical"></textarea></div>'
                + '<div class="mdd-field" style="margin-bottom:12px"><label>🖼️ Imagem de fundo (opcional)</label>'
                + '<div style="display:flex;gap:8px;align-items:center">'
                + '<input type="hidden" name="mdd_slide_bg_image['+idx+']" id="mdd_slide_img_'+idx+'" value="">'
                + '<button type="button" class="button" onclick="mddSlideUpload(\'mdd_slide_img_'+idx+'\')" style="font-size:.82rem">Selecionar Imagem</button>'
                + '<button type="button" class="button" id="mdd_slide_img_'+idx+'_rmv" onclick="mddSlideRemove(\'mdd_slide_img_'+idx+'\')" style="font-size:.82rem;display:none">Remover</button>'
                + '</div><div class="mdd-logo-preview" id="mdd_slide_img_'+idx+'_preview"><span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span></div>'
                + '<small style="color:var(--mdd-muted);font-size:.72rem">JPG/PNG/WebP. Paisagem (1920×1080).</small></div>'
                + '<div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">'
                + '<div class="mdd-field" style="margin:0"><label>Dias</label><div style="display:flex;gap:4px;flex-wrap:wrap">'+daysHtml+'</div></div>'
                + '<div class="mdd-field" style="margin:0;width:100px"><label>Início</label><input type="time" name="mdd_slide_start['+idx+']"></div>'
                + '<div class="mdd-field" style="margin:0;width:100px"><label>Fim</label><input type="time" name="mdd_slide_end['+idx+']"></div>'
                + '<div class="mdd-field" style="margin:0;flex:1;min-width:150px"><label>Ao encerrar</label><select name="mdd_slide_end_behavior['+idx+']"><option value="hide">Desaparecer silenciosamente</option><option value="message">Mostrar mensagem de encerramento</option></select></div>'
                + '<div class="mdd-field" style="margin:0;flex:1;min-width:180px"><label>Mensagem de encerramento</label><input type="text" name="mdd_slide_end_msg['+idx+']" placeholder="Ex: Happy Hour encerrado!"></div>'
                + '</div>'
                + '<input type="hidden" name="mdd_slide_active['+idx+']" value="1">'
                + '<div style="text-align:right;margin-top:10px;padding-top:10px;border-top:1px solid var(--mdd-border)"><button type="button" onclick="this.closest(\'.mdd-slide-item\').remove()" style="background:rgba(224,60,60,.12);border:1px solid rgba(224,60,60,.3);color:#e03c3c;cursor:pointer;font-size:13px;padding:6px 16px;border-radius:8px;font-weight:600" title="Remover slide">✕ Remover Slide</button></div>'
                + '</div>';

            document.getElementById('mdd-custom-slides').insertAdjacentHTML('beforeend', html);
        });
        </script>
        <?php
    }

    // ─── Tablet Tab ───
    private static function render_tablet_tab() {
        if (isset($_POST['mdd_save_tablet']) && check_admin_referer('mdd_tablet_nonce')) {
            update_option('mdd_tablet_columns', intval($_POST['mdd_tablet_columns'] ?? 2));
            update_option('mdd_tablet_columns_portrait', intval($_POST['mdd_tablet_columns_portrait'] ?? 1));
            update_option('mdd_tablet_timeout', intval($_POST['mdd_tablet_timeout'] ?? 60));
            update_option('mdd_tablet_show_price', intval($_POST['mdd_tablet_show_price'] ?? 1));
            update_option('mdd_tablet_show_badge', intval($_POST['mdd_tablet_show_badge'] ?? 1));
            update_option('mdd_tablet_show_desc', intval($_POST['mdd_tablet_show_desc'] ?? 1));
            update_option('mdd_tablet_quiz_text', sanitize_text_field($_POST['mdd_tablet_quiz_text'] ?? 'Faça o Quiz ✨'));
            update_option('mdd_tablet_screensaver_text', sanitize_text_field($_POST['mdd_tablet_screensaver_text'] ?? 'Cardápio de Drinks'));
            update_option('mdd_tablet_header_title', sanitize_text_field($_POST['mdd_tablet_header_title'] ?? 'Drinks'));
            update_option('mdd_tablet_font_title', sanitize_text_field($_POST['mdd_tablet_font_title'] ?? 'Playfair Display'));
            update_option('mdd_tablet_font_body', sanitize_text_field($_POST['mdd_tablet_font_body'] ?? 'Outfit'));
            echo '<div class="notice notice-success"><p>✅ Configurações do Tablet salvas!</p></div>';
        }

        $cols       = get_option('mdd_tablet_columns', 2);
        $cols_p     = get_option('mdd_tablet_columns_portrait', 1);
        $timeout    = get_option('mdd_tablet_timeout', 60);
        $show_price = get_option('mdd_tablet_show_price', 1);
        $show_badge = get_option('mdd_tablet_show_badge', 1);
        $show_desc  = get_option('mdd_tablet_show_desc', 1);
        $quiz_text  = get_option('mdd_tablet_quiz_text', 'Faça o Quiz ✨');
        $ss_text    = get_option('mdd_tablet_screensaver_text', 'Cardápio de Drinks');
        $h_title    = get_option('mdd_tablet_header_title', 'Drinks');
        $font_title = get_option('mdd_tablet_font_title', 'Playfair Display');
        $font_body  = get_option('mdd_tablet_font_body', 'Outfit');
        $sec        = get_option('mdd_secondary_color', '#1A1A2E');
        $pri        = get_option('mdd_primary_color', '#C8962E');
        $acc        = get_option('mdd_accent_color', '#E8593C');
        ?>
        <form method="post">
            <?php wp_nonce_field('mdd_tablet_nonce'); ?>

            <!-- Info -->
            <div class="mdd-card mdd-info-card">
                <p style="margin:0;font-size:.85rem;line-height:1.7">
                    <strong>📱 Configuração Global do Tablet</strong> — O cardápio interativo exibe seus drinks em grid com filtros por categoria. O cliente toca no card para ver detalhes, vídeo e ingredientes. Após inatividade, um screensaver com logo protege a tela.
                </p>
            </div>

            <!-- Grid Layout -->
            <div class="mdd-card">
                <h2>🗂️ Layout do Grid</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <!-- Landscape -->
                    <div>
                        <label style="font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);display:block;margin-bottom:8px">↔️ Horizontal (landscape)</label>
                        <div style="display:flex;gap:10px">
                            <?php foreach ([2 => '2 colunas', 3 => '3 colunas'] as $v => $l):
                                $active = ($cols == $v);
                            ?>
                            <label style="flex:1;background:#111;border:2px solid <?php echo $active ? 'var(--mdd-accent)' : 'var(--mdd-border)'; ?>;border-radius:10px;padding:12px;cursor:pointer;text-align:center;transition:border-color .2s">
                                <input type="radio" name="mdd_tablet_columns" value="<?php echo $v; ?>" <?php checked($cols, $v); ?> style="display:none">
                                <div style="background:<?php echo esc_attr($sec); ?>;border-radius:6px;padding:8px;margin-bottom:8px;display:grid;grid-template-columns:repeat(<?php echo $v; ?>,1fr);gap:4px;height:40px">
                                    <?php for ($i = 0; $i < $v; $i++): ?><div style="background:rgba(255,255,255,.08);border-radius:3px"></div><?php endfor; ?>
                                </div>
                                <div style="font-size:.82rem;color:<?php echo $active ? 'var(--mdd-accent)' : 'var(--mdd-text)'; ?>"><?php echo $l; ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Portrait -->
                    <div>
                        <label style="font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:var(--mdd-muted);display:block;margin-bottom:8px">↕️ Vertical (portrait)</label>
                        <div style="display:flex;gap:10px">
                            <?php foreach ([1 => '1 coluna', 2 => '2 colunas'] as $v => $l):
                                $active = ($cols_p == $v);
                            ?>
                            <label style="flex:1;background:#111;border:2px solid <?php echo $active ? 'var(--mdd-accent)' : 'var(--mdd-border)'; ?>;border-radius:10px;padding:12px;cursor:pointer;text-align:center;transition:border-color .2s">
                                <input type="radio" name="mdd_tablet_columns_portrait" value="<?php echo $v; ?>" <?php checked($cols_p, $v); ?> style="display:none">
                                <div style="background:<?php echo esc_attr($sec); ?>;border-radius:6px;padding:8px;margin-bottom:8px;display:grid;grid-template-columns:repeat(<?php echo $v; ?>,1fr);gap:4px;height:50px;width:40px;margin:0 auto 8px">
                                    <?php for ($i = 0; $i < $v; $i++): ?><div style="background:rgba(255,255,255,.08);border-radius:3px"></div><?php endfor; ?>
                                </div>
                                <div style="font-size:.82rem;color:<?php echo $active ? 'var(--mdd-accent)' : 'var(--mdd-text)'; ?>"><?php echo $l; ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Options -->
            <div class="mdd-card">
                <h2>🃏 Conteúdo dos Cards</h2>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_tablet_show_price" value="1" <?php checked($show_price); ?>> Exibir preço no card
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">Mostra o valor em cada card do grid (ex: R$ 29,90).</p>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_tablet_show_badge" value="1" <?php checked($show_badge); ?>> Exibir badge de categoria
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">Mostra badge (ex: "BIG DRINK") no canto da foto.</p>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_tablet_show_desc" value="1" <?php checked($show_desc); ?>> Exibir descrição curta
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">Mostra 1-2 linhas de descrição abaixo do nome (trunca automaticamente).</p>
                    </div>
                </div>
            </div>

            <!-- Textos e Fontes -->
            <div class="mdd-card">
                <h2>✏️ Textos e Tipografia</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
                    <div class="mdd-field">
                        <label>Título do header</label>
                        <input type="text" name="mdd_tablet_header_title" value="<?php echo esc_attr($h_title); ?>" placeholder="Drinks">
                        <p class="description">Texto ao lado do logo no topo.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Texto do botão Quiz</label>
                        <input type="text" name="mdd_tablet_quiz_text" value="<?php echo esc_attr($quiz_text); ?>" placeholder="Faça o Quiz ✨">
                        <p class="description">Botão pulsante no header.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Texto do screensaver</label>
                        <input type="text" name="mdd_tablet_screensaver_text" value="<?php echo esc_attr($ss_text); ?>" placeholder="Cardápio de Drinks">
                        <p class="description">Exibido na tela de inatividade.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Fonte dos títulos</label>
                        <select name="mdd_tablet_font_title">
                            <?php foreach (['Playfair Display', 'Syne', 'Cormorant Garamond', 'Lora', 'Outfit'] as $f): ?>
                                <option value="<?php echo esc_attr($f); ?>" <?php selected($font_title, $f); ?>><?php echo esc_html($f); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Nomes dos drinks, preços, títulos.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Fonte do corpo</label>
                        <select name="mdd_tablet_font_body">
                            <?php foreach (['Outfit', 'DM Sans', 'Inter', 'Poppins', 'Nunito'] as $f): ?>
                                <option value="<?php echo esc_attr($f); ?>" <?php selected($font_body, $f); ?>><?php echo esc_html($f); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Descrições, filtros, botões.</p>
                    </div>
                </div>
            </div>

            <!-- Screensaver -->
            <div class="mdd-card">
                <h2>💤 Screensaver (Inatividade)</h2>
                <div class="mdd-field">
                    <label>Tempo para ativar</label>
                    <div style="display:flex;align-items:center;gap:10px;max-width:400px">
                        <input type="range" name="mdd_tablet_timeout" value="<?php echo intval($timeout); ?>" min="15" max="300" step="15" oninput="document.getElementById('timeout-val').textContent=this.value+'s'" style="flex:1">
                        <span id="timeout-val" style="font-size:.9rem;min-width:40px;color:var(--mdd-accent2)"><?php echo intval($timeout); ?>s</span>
                    </div>
                    <p class="description">Após esse tempo sem toque na tela, o screensaver ativa com o logo do estabelecimento. O cliente toca para voltar ao cardápio. Recomendado: 60s.</p>
                </div>
            </div>

            <!-- Preview -->
            <div class="mdd-card">
                <h2>👁️ Preview</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <!-- Card preview -->
                    <div style="background:<?php echo esc_attr($sec); ?>;border-radius:12px;padding:12px;border:1px solid var(--mdd-border)">
                        <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);margin-bottom:8px;text-align:center">Preview do card</div>
                        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden;max-width:200px;margin:0 auto">
                            <div style="padding-top:100%;background:rgba(255,255,255,.06);position:relative">
                                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:32px;opacity:.3">🍸</div>
                                <?php if ($show_badge): ?><span style="position:absolute;top:8px;right:8px;padding:3px 8px;background:rgba(0,0,0,.6);border-radius:8px;font-size:9px;letter-spacing:1px;text-transform:uppercase;color:<?php echo esc_attr($pri); ?>">DRINK</span><?php endif; ?>
                            </div>
                            <div style="padding:10px 12px">
                                <div style="font-family:'<?php echo esc_attr($font_title); ?>',serif;font-size:14px;font-weight:600;color:#fff;margin-bottom:3px">Moscow Mule</div>
                                <?php if ($show_desc): ?><div style="font-size:10px;color:rgba(255,255,255,.5);line-height:1.3;margin-bottom:6px">Vodka, ginger beer, suco de limão</div><?php endif; ?>
                                <?php if ($show_price): ?><div style="font-size:15px;font-weight:600;color:<?php echo esc_attr($pri); ?>">R$ 29,90</div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Screensaver preview -->
                    <div style="background:<?php echo esc_attr($sec); ?>;border-radius:12px;padding:24px;border:1px solid var(--mdd-border);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:200px">
                        <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);margin-bottom:8px">Preview do screensaver</div>
                        <div style="font-size:28px;opacity:.3">🍸</div>
                        <div style="font-size:13px;color:rgba(255,255,255,.3);letter-spacing:2px;text-transform:uppercase"><?php echo esc_html($ss_text); ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,.15);margin-top:12px">Toque para explorar</div>
                    </div>
                </div>
            </div>

            <button type="submit" name="mdd_save_tablet" class="button button-primary button-large">
                💾 Salvar Configurações Tablet
            </button>
        </form>
        <?php
    }

    // ─── Quiz Tab ───
    private static function render_quiz_tab() {
        if (isset($_POST['mdd_save_quiz']) && check_admin_referer('mdd_quiz_nonce')) {
            update_option('mdd_quiz_ask_name', isset($_POST['mdd_quiz_ask_name']) ? 1 : 0);
            update_option('mdd_quiz_skip_base_if_no_alcohol', isset($_POST['mdd_quiz_skip_base']) ? 1 : 0);
            update_option('mdd_quiz_cta_text', sanitize_text_field($_POST['mdd_quiz_cta_text'] ?? 'Experimentar'));
            update_option('mdd_quiz_confirm_text', sanitize_text_field($_POST['mdd_quiz_confirm_text'] ?? 'Informe ao garçom para confirmar seu pedido.'));
            update_option('mdd_quiz_pairing_text', sanitize_text_field($_POST['mdd_quiz_pairing_text'] ?? 'Sabia que esse drink combina com'));
            update_option('mdd_quiz_show_rating', isset($_POST['mdd_quiz_show_rating']) ? 1 : 0);
            update_option('mdd_quiz_rating_text', sanitize_text_field($_POST['mdd_quiz_rating_text'] ?? 'Como foi o Quiz?'));
            update_option('mdd_quiz_share_text', sanitize_text_field($_POST['mdd_quiz_share_text'] ?? ''));
            update_option('mdd_quiz_post_rating_msg', sanitize_text_field($_POST['mdd_quiz_post_rating_msg'] ?? ''));
            update_option('mdd_quiz_final_msg', sanitize_text_field($_POST['mdd_quiz_final_msg'] ?? 'Aproveite a experiência!'));
            update_option('mdd_quiz_phone_text', sanitize_text_field($_POST['mdd_quiz_phone_text'] ?? ''));
            // Quiz visual overrides
            update_option('mdd_quiz_bg_color', sanitize_hex_color($_POST['mdd_quiz_bg_color'] ?? ''));
            update_option('mdd_quiz_primary_color', sanitize_hex_color($_POST['mdd_quiz_primary_color'] ?? ''));
            update_option('mdd_quiz_accent_color', sanitize_hex_color($_POST['mdd_quiz_accent_color'] ?? ''));
            // bg_image: handle attachment ID or URL
            $quiz_bg_img = sanitize_text_field($_POST['mdd_quiz_bg_image'] ?? '');
            if (is_numeric($quiz_bg_img)) $quiz_bg_img = wp_get_attachment_url(intval($quiz_bg_img));
            update_option('mdd_quiz_bg_image', esc_url_raw($quiz_bg_img));
            // Quiz font sizes
            update_option('mdd_quiz_title_size', intval($_POST['mdd_quiz_title_size'] ?? 22));
            update_option('mdd_quiz_option_size', intval($_POST['mdd_quiz_option_size'] ?? 15));
            update_option('mdd_quiz_btn_size', intval($_POST['mdd_quiz_btn_size'] ?? 16));
            echo '<div class="notice notice-success"><p>✅ Configurações do Quiz salvas!</p></div>';
        }

        $engine    = new MDD_Quiz_Engine();
        $questions = $engine->get_questions();
        $ask_name  = get_option('mdd_quiz_ask_name', 1);
        $skip_base = get_option('mdd_quiz_skip_base_if_no_alcohol', 1);
        $cta       = get_option('mdd_quiz_cta_text', 'Experimentar');
        $confirm   = get_option('mdd_quiz_confirm_text', 'Informe ao garçom para confirmar seu pedido.');
        $pairing   = get_option('mdd_quiz_pairing_text', 'Sabia que esse drink combina com');
        $show_rate = get_option('mdd_quiz_show_rating', 1);
        $rate_text = get_option('mdd_quiz_rating_text', 'Como foi o Quiz?');
        $share_text= get_option('mdd_quiz_share_text', '');
        $post_msg  = get_option('mdd_quiz_post_rating_msg', 'Se possível, após sua experiência com o drink, volte e avalie-o. É importante para nós!');
        $final_msg = get_option('mdd_quiz_final_msg', 'Aproveite a experiência!');
        $quiz_bg   = get_option('mdd_quiz_bg_color', '');
        $quiz_prim = get_option('mdd_quiz_primary_color', '');
        $quiz_acc  = get_option('mdd_quiz_accent_color', '');
        $quiz_bg_img = get_option('mdd_quiz_bg_image', '');
        $quiz_title_s = get_option('mdd_quiz_title_size', 22);
        $quiz_opt_s   = get_option('mdd_quiz_option_size', 15);
        $quiz_btn_s   = get_option('mdd_quiz_btn_size', 16);
        $food_cpt  = get_option('mdd_food_post_type', '');
        $quiz_url  = home_url('/display/quiz/');
        ?>
        <form method="post">
            <?php wp_nonce_field('mdd_quiz_nonce'); ?>

            <!-- URL -->
            <div class="mdd-card mdd-info-card">
                <p style="margin:0;font-size:.85rem;line-height:1.7">
                    <strong>🧩 URL do Quiz (pública, sem token):</strong>
                    <code style="font-size:13px;padding:6px 12px;display:inline-block;margin-left:8px;cursor:pointer" onclick="navigator.clipboard.writeText('<?php echo esc_js($quiz_url); ?>')" title="Clique para copiar"><?php echo esc_html($quiz_url); ?></code>
                </p>
            </div>

            <!-- Perguntas -->
            <div class="mdd-card">
                <h2>❓ Perguntas do Quiz</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    As 5 perguntas padrão estão pré-configuradas e funcionam imediatamente.
                    Cada resposta gera tags que são cruzadas com as Tags de Perfil dos drinks para calcular o match.
                </p>
                <div style="display:flex;flex-direction:column;gap:12px">
                <?php foreach ($questions as $i => $q): ?>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                            <span style="font-size:16px"><?php echo $q['icon'] ?? ''; ?></span>
                            <strong style="font-size:.9rem"><?php echo esc_html(($i + 1) . '. ' . $q['question']); ?></strong>
                            <code style="margin-left:auto;font-size:.7rem"><?php echo esc_html($q['id']); ?></code>
                        </div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <?php foreach ($q['options'] as $opt): ?>
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:rgba(255,255,255,.04);border:1px solid var(--mdd-border);border-radius:16px;font-size:.78rem">
                                    <?php echo $opt['icon'] ?? ''; ?> <?php echo esc_html($opt['label']); ?>
                                    <small style="color:var(--mdd-muted);margin-left:2px">(<?php echo esc_html(implode(', ', $opt['tags'])); ?>)</small>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Configurações do fluxo -->
            <div class="mdd-card">
                <h2>⚙️ Configurações do Fluxo</h2>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_quiz_ask_name" value="1" <?php checked($ask_name); ?>>
                            Perguntar o nome do cliente
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">
                            Exibe "Como posso te chamar?" antes das perguntas. Permite mensagens personalizadas: "João, você escolheu o Moscow Mule!"
                        </p>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_quiz_skip_base" value="1" <?php checked($skip_base); ?>>
                            Pular pergunta "Base preferida" se escolheu "Sem álcool"
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">
                            Lógica condicional: se o cliente selecionou "Sem álcool" na intensidade, não faz sentido perguntar sobre vodka/gin/rum.
                        </p>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 18px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="mdd_quiz_show_rating" value="1" <?php checked($show_rate); ?>>
                            Pedir avaliação do Quiz (estrelas)
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:.75rem;color:var(--mdd-muted)">
                            Após o cliente escolher o drink, exibe "<?php echo esc_html($rate_text); ?>" com 5 estrelas. Avalia a experiência do Quiz, não o drink.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Textos personalizáveis -->
            <div class="mdd-card">
                <h2>✏️ Textos Personalizáveis</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">
                    <div class="mdd-field">
                        <label>Botão de escolha (CTA)</label>
                        <input type="text" name="mdd_quiz_cta_text" value="<?php echo esc_attr($cta); ?>" placeholder="Experimentar">
                        <p class="description">Texto do botão nos cards de resultado. Evite "Pedir" — o cliente não está fazendo pedido online.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Mensagem de confirmação</label>
                        <input type="text" name="mdd_quiz_confirm_text" value="<?php echo esc_attr($confirm); ?>" placeholder="Informe ao garçom para confirmar seu pedido.">
                        <p class="description">Aparece após o cliente tocar no botão de escolha. Ex: "Informe ao garçom" ou "Peça ao bartender".</p>
                    </div>
                    <div class="mdd-field">
                        <label>Texto da harmonização</label>
                        <input type="text" name="mdd_quiz_pairing_text" value="<?php echo esc_attr($pairing); ?>" placeholder="Sabia que esse drink combina com">
                        <p class="description">Título da seção de pratos que combinam. Só aparece se o CPT de pratos está configurado na aba Geral<?php echo $food_cpt ? ' <span style="color:var(--mdd-success)">(✓ configurado: ' . esc_html($food_cpt) . ')</span>' : ' <span style="color:var(--mdd-accent)">(não configurado)</span>'; ?>.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Texto da avaliação</label>
                        <input type="text" name="mdd_quiz_rating_text" value="<?php echo esc_attr($rate_text); ?>" placeholder="Como foi o Quiz?">
                        <p class="description">Pergunta que aparece com as 5 estrelas após a escolha do drink.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Texto do compartilhamento</label>
                        <input type="text" name="mdd_quiz_share_text" value="<?php echo esc_attr($share_text); ?>" placeholder="Fiz o quiz de drinks no {estabelecimento} e as sugestões ideais para mim são:">
                        <p class="description">Use <code>{estabelecimento}</code> para inserir o nome do site. Deixe vazio para usar o padrão.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Mensagem pós-experiência</label>
                        <input type="text" name="mdd_quiz_post_rating_msg" value="<?php echo esc_attr($post_msg); ?>" placeholder="Se possível, após sua experiência com o drink, volte e avalie-o.">
                        <p class="description">Aparece na tela final do Quiz incentivando o cliente a avaliar o drink depois.</p>
                    </div>
                    <div class="mdd-field">
                        <label>Mensagem da tela final</label>
                        <input type="text" name="mdd_quiz_final_msg" value="<?php echo esc_attr($final_msg); ?>" placeholder="Aproveite a experiência!">
                        <p class="description">Texto principal da tela "Obrigado!" ao final do Quiz. NÃO mencione pedido — o sistema não gera pedidos.</p>
                    </div>
                    <div class="mdd-field">
                        <label>📱 Texto incentivo telefone</label>
                        <?php $phone_text = get_option('mdd_quiz_phone_text', 'Cadastre seu telefone e receba promoções exclusivas de drinks!'); ?>
                        <input type="text" name="mdd_quiz_phone_text" value="<?php echo esc_attr($phone_text); ?>" placeholder="Cadastre seu telefone e receba promoções exclusivas de drinks!">
                        <p class="description">Texto persuasivo exibido abaixo do campo telefone na tela de nome. Convence o cliente a informar o número.</p>
                    </div>
                </div>
            </div>

            <!-- FN5: Editor de Perguntas -->
            <div class="mdd-card">
                <h2>📝 Editor de Perguntas do Quiz</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Personalize as perguntas e opções de resposta. Cada opção tem <strong>tags</strong> que conectam com os drinks cadastrados (mesmas tags do Auto-Tagger).
                    <br>Clique em "Restaurar Padrão" para voltar às perguntas originais.
                </p>

                <?php
                $engine = new MDD_Quiz_Engine();
                $questions = $engine->get_questions();
                ?>

                <div id="mdd_questions_editor">
                    <?php foreach ($questions as $qi => $q): ?>
                    <div class="mdd-question-card" data-index="<?php echo $qi; ?>" style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:16px;margin-bottom:12px">
                        <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px">
                            <input type="text" class="mdd-q-icon" value="<?php echo esc_attr($q['icon'] ?? ''); ?>" style="width:50px;text-align:center;font-size:20px;padding:6px;background:#0a0a0a;border:1px solid var(--mdd-border);border-radius:8px;color:#fff" placeholder="🍋">
                            <input type="text" class="mdd-q-text" value="<?php echo esc_attr($q['question']); ?>" style="flex:1;padding:8px 14px;background:#0a0a0a;border:1px solid var(--mdd-border);border-radius:8px;color:var(--mdd-text);font-size:.9rem;font-weight:600" placeholder="Texto da pergunta">
                            <input type="hidden" class="mdd-q-id" value="<?php echo esc_attr($q['id']); ?>">
                            <button type="button" onclick="this.closest('.mdd-question-card').remove()" style="background:rgba(224,60,60,.1);border:1px solid rgba(224,60,60,.2);color:#e03c3c;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:.8rem">✕</button>
                        </div>
                        <div class="mdd-q-options" style="display:flex;flex-direction:column;gap:6px">
                            <?php foreach ($q['options'] as $oi => $opt): ?>
                            <div class="mdd-q-option" style="display:flex;gap:8px;align-items:center;padding:8px 12px;background:#0a0a0a;border:1px solid var(--mdd-border);border-radius:8px">
                                <input type="text" class="mdd-o-icon" value="<?php echo esc_attr($opt['icon'] ?? ''); ?>" style="width:40px;text-align:center;font-size:16px;padding:4px;background:transparent;border:1px solid var(--mdd-border);border-radius:6px;color:#fff" placeholder="🍊">
                                <input type="text" class="mdd-o-label" value="<?php echo esc_attr($opt['label']); ?>" style="flex:1;padding:6px 10px;background:transparent;border:1px solid var(--mdd-border);border-radius:6px;color:var(--mdd-text);font-size:.85rem" placeholder="Rótulo da opção">
                                <input type="text" class="mdd-o-tags" value="<?php echo esc_attr(implode(', ', $opt['tags'] ?? [])); ?>" style="flex:1;padding:6px 10px;background:transparent;border:1px solid rgba(249,115,22,.2);border-radius:6px;color:var(--mdd-accent);font-size:.78rem" placeholder="tags: citrico, refrescante">
                                <button type="button" onclick="this.closest('.mdd-q-option').remove()" style="background:none;border:none;color:#e03c3c;cursor:pointer;font-size:14px;padding:2px 6px">✕</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="mddAddOption(this)" style="margin-top:8px;background:rgba(255,255,255,.04);border:1px dashed var(--mdd-border);border-radius:8px;padding:6px 16px;color:var(--mdd-muted);cursor:pointer;font-size:.8rem;width:100%">+ Adicionar Opção</button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex;gap:10px;margin-top:12px">
                    <button type="button" id="mdd_add_question" class="button" style="font-size:.85rem">+ Nova Pergunta</button>
                    <button type="button" id="mdd_save_questions" class="button button-primary" style="font-size:.85rem">💾 Salvar Perguntas</button>
                    <button type="button" id="mdd_reset_questions" class="button" style="font-size:.85rem;color:var(--mdd-danger)">↩ Restaurar Padrão</button>
                    <span id="mdd_questions_msg" style="font-size:.82rem;display:flex;align-items:center"></span>
                </div>
            </div>

            <script>
            function mddAddOption(btn) {
                var html = '<div class="mdd-q-option" style="display:flex;gap:8px;align-items:center;padding:8px 12px;background:#0a0a0a;border:1px solid var(--mdd-border);border-radius:8px">'
                    + '<input type="text" class="mdd-o-icon" value="" style="width:40px;text-align:center;font-size:16px;padding:4px;background:transparent;border:1px solid var(--mdd-border);border-radius:6px;color:#fff" placeholder="✨">'
                    + '<input type="text" class="mdd-o-label" value="" style="flex:1;padding:6px 10px;background:transparent;border:1px solid var(--mdd-border);border-radius:6px;color:var(--mdd-text);font-size:.85rem" placeholder="Rótulo da opção">'
                    + '<input type="text" class="mdd-o-tags" value="" style="flex:1;padding:6px 10px;background:transparent;border:1px solid rgba(249,115,22,.2);border-radius:6px;color:var(--mdd-accent);font-size:.78rem" placeholder="tags: tag1, tag2">'
                    + '<button type="button" onclick="this.closest(\'.mdd-q-option\').remove()" style="background:none;border:none;color:#e03c3c;cursor:pointer;font-size:14px;padding:2px 6px">✕</button>'
                    + '</div>';
                btn.previousElementSibling.insertAdjacentHTML('beforeend', html);
            }

            jQuery(function($) {
                // Add new question
                $('#mdd_add_question').on('click', function() {
                    var idx = $('#mdd_questions_editor .mdd-question-card').length;
                    var html = '<div class="mdd-question-card" data-index="'+idx+'" style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:16px;margin-bottom:12px">'
                        + '<div style="display:flex;gap:10px;align-items:center;margin-bottom:12px">'
                        + '<input type="text" class="mdd-q-icon" value="❓" style="width:50px;text-align:center;font-size:20px;padding:6px;background:#0a0a0a;border:1px solid var(--mdd-border);border-radius:8px;color:#fff">'
                        + '<input type="text" class="mdd-q-text" value="" style="flex:1;padding:8px 14px;background:#0a0a0a;border:1px solid var(--mdd-border);border-radius:8px;color:var(--mdd-text);font-size:.9rem;font-weight:600" placeholder="Texto da pergunta">'
                        + '<input type="hidden" class="mdd-q-id" value="q'+idx+'">'
                        + '<button type="button" onclick="this.closest(\'.mdd-question-card\').remove()" style="background:rgba(224,60,60,.1);border:1px solid rgba(224,60,60,.2);color:#e03c3c;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:.8rem">✕</button>'
                        + '</div>'
                        + '<div class="mdd-q-options" style="display:flex;flex-direction:column;gap:6px"></div>'
                        + '<button type="button" onclick="mddAddOption(this)" style="margin-top:8px;background:rgba(255,255,255,.04);border:1px dashed var(--mdd-border);border-radius:8px;padding:6px 16px;color:var(--mdd-muted);cursor:pointer;font-size:.8rem;width:100%">+ Adicionar Opção</button>'
                        + '</div>';
                    $('#mdd_questions_editor').append(html);
                });

                // Save questions
                $('#mdd_save_questions').on('click', function() {
                    var questions = [];
                    $('#mdd_questions_editor .mdd-question-card').each(function() {
                        var card = $(this);
                        var options = [];
                        card.find('.mdd-q-option').each(function() {
                            var opt = $(this);
                            var tags = opt.find('.mdd-o-tags').val().split(',').map(function(t){ return t.trim().toLowerCase(); }).filter(function(t){ return t; });
                            options.push({ label: opt.find('.mdd-o-label').val(), icon: opt.find('.mdd-o-icon').val(), tags: tags });
                        });
                        questions.push({
                            id: card.find('.mdd-q-id').val(),
                            question: card.find('.mdd-q-text').val(),
                            icon: card.find('.mdd-q-icon').val(),
                            options: options
                        });
                    });

                    var btn = $(this);
                    btn.prop('disabled', true).text('Salvando...');
                    $.post(mddAdmin.ajaxUrl, {
                        action: 'mdd_save_quiz_questions',
                        nonce: mddAdmin.nonce,
                        questions: JSON.stringify(questions)
                    }, function(res) {
                        btn.prop('disabled', false).text('💾 Salvar Perguntas');
                        $('#mdd_questions_msg').html(res.success
                            ? '<span style="color:var(--mdd-success)">✅ Perguntas salvas!</span>'
                            : '<span style="color:var(--mdd-danger)">❌ Erro ao salvar.</span>');
                        setTimeout(function(){ $('#mdd_questions_msg').html(''); }, 3000);
                    });
                });

                // Reset to defaults
                $('#mdd_reset_questions').on('click', function() {
                    if (!confirm('Restaurar perguntas padrão? Suas personalizações serão perdidas.')) return;
                    $.post(mddAdmin.ajaxUrl, {
                        action: 'mdd_save_quiz_questions',
                        nonce: mddAdmin.nonce,
                        questions: '[]'
                    }, function(res) {
                        if (res.success) location.reload();
                    });
                });
            });
            </script>

            <!-- Avaliação pós-experiência -->
            <div class="mdd-card mdd-info-card">
                <p style="margin:0;font-size:.85rem;line-height:1.7">
                    <strong>⭐ Sistema de Avaliação Dupla</strong><br>
                    <strong>1. Quiz (imediata):</strong> O cliente avalia a experiência do Quiz logo após escolher o drink. Mede se o Quiz é divertido e útil.<br>
                    <strong>2. Drink (pós-experiência):</strong> Um link único é gerado automaticamente (<code>seusite.com/display/quiz/?rate=aB3kX</code>) e incluído no compartilhamento. O cliente pode avaliar o drink depois de experimentar — mesmo horas ou dias depois.<br>
                    Ambas as notas aparecem separadas no Dashboard de Estatísticas.
                </p>
            </div>

            <!-- Visual do Quiz -->
            <div class="mdd-card">
                <h2>🎨 Visual do Quiz (Cores Independentes)</h2>
                <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                    Configure cores e fundo exclusivos para o Quiz. Se deixar em branco, usa as cores globais da aba Geral.
                    Útil para eventos onde o tema do Quiz difere do display da TV.
                </p>
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
                    <div class="mdd-field" style="margin:0;width:140px">
                        <label>Cor de Fundo</label>
                        <input type="color" name="mdd_quiz_bg_color" value="<?php echo esc_attr($quiz_bg ?: get_option('mdd_secondary_color', '#1A1A2E')); ?>" style="height:40px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px">
                        <small style="color:var(--mdd-muted);font-size:.7rem">Vazio = cor global</small>
                    </div>
                    <div class="mdd-field" style="margin:0;width:140px">
                        <label>Cor Primária</label>
                        <input type="color" name="mdd_quiz_primary_color" value="<?php echo esc_attr($quiz_prim ?: get_option('mdd_primary_color', '#C8962E')); ?>" style="height:40px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px">
                        <small style="color:var(--mdd-muted);font-size:.7rem">Títulos, destaques</small>
                    </div>
                    <div class="mdd-field" style="margin:0;width:140px">
                        <label>Cor de Destaque</label>
                        <input type="color" name="mdd_quiz_accent_color" value="<?php echo esc_attr($quiz_acc ?: get_option('mdd_accent_color', '#E8593C')); ?>" style="height:40px;width:100%;border-radius:6px;border:1px solid var(--mdd-border);background:#111;padding:2px">
                        <small style="color:var(--mdd-muted);font-size:.7rem">Botões</small>
                    </div>
                </div>
                <h3 style="font-size:.9rem;margin:20px 0 12px;color:var(--mdd-text)">📏 Tamanho das Fontes</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:16px">
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:12px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:6px;display:block">Títulos / Perguntas</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_quiz_title_size" min="14" max="36" value="<?php echo intval($quiz_title_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($quiz_title_s); ?>px</span>
                        </div>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:12px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:6px;display:block">Opções de Resposta</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_quiz_option_size" min="12" max="24" value="<?php echo intval($quiz_opt_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($quiz_opt_s); ?>px</span>
                        </div>
                    </div>
                    <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:12px">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:6px;display:block">Botões</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input type="range" name="mdd_quiz_btn_size" min="12" max="24" value="<?php echo intval($quiz_btn_s); ?>" style="flex:1" oninput="this.nextElementSibling.textContent=this.value+'px'">
                            <span style="font-size:.75rem;color:var(--mdd-muted);min-width:35px"><?php echo intval($quiz_btn_s); ?>px</span>
                        </div>
                    </div>
                </div>
                <div class="mdd-field" style="margin-bottom:0">
                    <label>🖼️ Imagem de Fundo do Quiz (opcional)</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="hidden" name="mdd_quiz_bg_image" id="mdd_quiz_bg_image" value="<?php echo esc_attr($quiz_bg_img); ?>">
                        <button type="button" class="button mdd-upload-btn" data-target="mdd_quiz_bg_image" data-save-url="1" style="font-size:.82rem">Selecionar Imagem</button>
                        <button type="button" class="button mdd-remove-btn" data-target="mdd_quiz_bg_image" style="font-size:.82rem;<?php echo empty($quiz_bg_img) ? 'display:none' : ''; ?>">Remover</button>
                    </div>
                    <div class="mdd-logo-preview" id="mdd_quiz_bg_image_preview">
                        <?php if ($quiz_bg_img): ?>
                            <img src="<?php echo esc_url($quiz_bg_img); ?>">
                        <?php else: ?>
                            <span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span>
                        <?php endif; ?>
                    </div>
                    <small style="color:var(--mdd-muted);font-size:.72rem">Formatos: JPG, PNG, WebP. Orientação vertical recomendada (1080×1920px). Tamanho: até 500KB. A imagem aparece como fundo em todas as telas do Quiz com overlay escuro para legibilidade.</small>
                </div>
            </div>

            <button type="submit" name="mdd_save_quiz" class="button button-primary button-large">
                💾 Salvar Configurações Quiz
            </button>
        </form>
        <?php
    }

    // ─── QR Code Tab ───
    private static function render_qrcode_tab() {
        MDD_QR_Generator::render_admin_section();
    }

    // ─── Auto-Tagger Tab ───
    private static function render_tagger_tab() {
        $bridge = new MDD_CPT_Bridge();
        $drinks = $bridge->get_drinks();
        $total = count($drinks);
        $with_tags = 0;
        foreach ($drinks as $d) { if (!empty($d['profile_tags'])) $with_tags++; }
        $without = $total - $with_tags;
        ?>
        <!-- Explicação -->
        <div class="mdd-card mdd-info-card">
            <p style="margin:0;font-size:.85rem;line-height:1.7">
                <strong>🏷️ Como funciona o Auto-Tagger?</strong><br>
                O algoritmo analisa título, descrição e ingredientes de cada drink e atribui <strong>Tags de Perfil</strong> automaticamente.
                Essas tags são usadas pelo Quiz para recomendar drinks — se "Moscow Mule" contém "limão" e "vodka", recebe as tags <em>cítrico</em>, <em>refrescante</em>, <em>vodka</em>.
                Drinks sem tags não serão bem recomendados.
            </p>
        </div>

        <!-- Stats + Action -->
        <div class="mdd-card">
            <h2>⚡ Auto-Tagear Drinks</h2>
            <div style="display:flex;gap:16px;margin-bottom:16px">
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 20px;text-align:center;min-width:100px">
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--mdd-accent)"><?php echo $total; ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted);text-transform:uppercase;letter-spacing:.08em">Total</div>
                </div>
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 20px;text-align:center;min-width:100px">
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--mdd-success)"><?php echo $with_tags; ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted);text-transform:uppercase;letter-spacing:.08em">Com Tags</div>
                </div>
                <?php if ($without > 0): ?>
                <div style="background:#111;border:1px solid rgba(224,60,60,.2);border-radius:10px;padding:14px 20px;text-align:center;min-width:100px">
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--mdd-danger)"><?php echo $without; ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted);text-transform:uppercase;letter-spacing:.08em">Sem Tags</div>
                </div>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding-top:16px;border-top:1px solid var(--mdd-border)">
                <button type="button" id="mdd_auto_tag_all_btn" class="button button-primary button-large">
                    ⚡ Auto-tagear todos os drinks
                </button>
                <label style="font-size:.85rem;color:var(--mdd-muted);display:flex;align-items:center;gap:6px;cursor:pointer">
                    <input type="checkbox" id="mdd_auto_tag_overwrite">
                    Sobrescrever tags existentes
                </label>
                <span id="mdd_auto_tag_status" style="font-size:.85rem;display:none"></span>
            </div>
        </div>

        <!-- Tags Table -->
        <div class="mdd-card" style="padding:0;overflow:hidden">
            <div style="padding:20px 24px 12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <h2 style="margin:0!important;padding:0!important;border:none!important">📋 Tags Atuais dos Drinks</h2>
                <button type="button" class="button" id="mdd_export_tags" style="font-size:.82rem">📥 Exportar CSV</button>
            </div>
            <!-- Search + Filters -->
            <div style="padding:0 24px 12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <input type="text" id="mdd_tagger_search" placeholder="🔍 Buscar drink..." style="flex:1;min-width:180px;padding:8px 14px;background:#111;border:1px solid var(--mdd-border);border-radius:8px;color:var(--mdd-text);font-size:.85rem">
                <select id="mdd_tagger_cat_filter" style="padding:8px 14px;background:#111;border:1px solid var(--mdd-border);border-radius:8px;color:var(--mdd-text);font-size:.85rem;min-width:150px">
                    <option value="">Todas as categorias</option>
                    <?php
                    $all_cats = [];
                    foreach ($drinks as $d) { foreach ($d['categories'] as $c) { $all_cats[$c['slug']] = $c['name']; } }
                    asort($all_cats);
                    foreach ($all_cats as $slug => $name): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="mdd_tagger_status_filter" style="padding:8px 14px;background:#111;border:1px solid var(--mdd-border);border-radius:8px;color:var(--mdd-text);font-size:.85rem">
                    <option value="">Todos os status</option>
                    <option value="ok">✓ Com tags</option>
                    <option value="sem">⚠ Sem tags</option>
                </select>
                <select id="mdd_tagger_tag_filter" style="padding:8px 14px;background:#111;border:1px solid var(--mdd-border);border-radius:8px;color:var(--mdd-text);font-size:.85rem;min-width:140px">
                    <option value="">Todas as tags</option>
                    <?php
                    $all_tags = [];
                    foreach ($drinks as $d) { foreach ($d['profile_tags'] as $t) { $all_tags[$t] = true; } }
                    ksort($all_tags);
                    foreach ($all_tags as $tag => $v): ?>
                        <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($tag); ?></option>
                    <?php endforeach; ?>
                </select>
                <span id="mdd_tagger_count" style="font-size:.78rem;color:var(--mdd-muted)"></span>
            </div>
            <?php
            // Category color map
            $cat_colors = [
                '#E8593C','#3B82F6','#22C55E','#A855F7','#F59E0B','#EC4899','#06B6D4','#84CC16','#F97316','#6366F1',
                '#14B8A6','#EF4444','#8B5CF6','#10B981','#F43F5E'
            ];
            $cat_color_map = [];
            $ci = 0;
            foreach ($all_cats as $slug => $name) {
                $cat_color_map[$slug] = $cat_colors[$ci % count($cat_colors)];
                $ci++;
            }
            ?>
            <table class="mdd-tagger-table" id="mdd_tagger_table">
                <thead>
                    <tr>
                        <th style="width:30%">Drink</th>
                        <th style="width:18%">Categoria</th>
                        <th>Tags de Perfil</th>
                        <th style="width:70px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drinks as $d):
                        $tags = $d['profile_tags'];
                        $cats = $d['categories'];
                        $cat_slugs = array_map(function($c) { return $c['slug']; }, $cats);
                        $has_tags = !empty($tags);
                    ?>
                        <tr data-name="<?php echo esc_attr(mb_strtolower($d['title'])); ?>" data-cats="<?php echo esc_attr(implode(',', $cat_slugs)); ?>" data-tags="<?php echo esc_attr(implode(',', $tags)); ?>" data-status="<?php echo $has_tags ? 'ok' : 'sem'; ?>">
                            <td>
                                <strong style="color:var(--mdd-text)"><?php echo esc_html($d['title']); ?></strong>
                                <div style="font-size:.75rem;color:var(--mdd-muted);margin-top:2px"><?php echo esc_html(wp_trim_words($d['short_desc'] ?? '', 10)); ?></div>
                            </td>
                            <td>
                                <?php foreach ($cats as $cat):
                                    $cc = $cat_color_map[$cat['slug']] ?? '#888';
                                ?>
                                    <span style="display:inline-block;padding:2px 8px;background:<?php echo $cc; ?>20;border:1px solid <?php echo $cc; ?>40;border-radius:10px;font-size:.72rem;margin:1px;color:<?php echo $cc; ?>"><?php echo esc_html($cat['name']); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($has_tags): ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <span style="display:inline-block;padding:2px 8px;background:rgba(249,115,22,.1);border:1px solid rgba(249,115,22,.2);border-radius:10px;font-size:.72rem;margin:1px;color:var(--mdd-accent)"><?php echo esc_html($tag); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:var(--mdd-muted);font-size:.8rem">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_tags): ?>
                                    <span style="color:var(--mdd-success);font-size:.8rem">✓ OK</span>
                                <?php else: ?>
                                    <span style="color:var(--mdd-danger);font-size:.8rem">⚠ Sem</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(function($) {
            // Auto-tag all
            $('#mdd_auto_tag_all_btn').on('click', function() {
                var btn = $(this);
                var overwrite = $('#mdd_auto_tag_overwrite').is(':checked');
                var status = $('#mdd_auto_tag_status');

                btn.prop('disabled', true).text('Processando...');
                status.show().html('<span style="color:var(--mdd-muted)">Analisando drinks...</span>');

                $.post(mddAdmin.ajaxUrl, {
                    action: 'mdd_auto_tag_all',
                    nonce: mddAdmin.nonce,
                    overwrite: overwrite ? 1 : 0
                }, function(res) {
                    btn.prop('disabled', false).html('⚡ Auto-tagear todos os drinks');
                    if (res.success) {
                        status.html('<span style="color:var(--mdd-success)">✅ ' + res.data.tagged + ' drinks tageados, ' + res.data.skipped + ' ignorados.</span>');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        status.html('<span style="color:var(--mdd-danger)">❌ Erro ao processar.</span>');
                    }
                }).fail(function() {
                    btn.prop('disabled', false).html('⚡ Auto-tagear todos os drinks');
                    status.html('<span style="color:var(--mdd-danger)">❌ Erro de conexão.</span>');
                });
            });

            // Export CSV
            $('#mdd_export_tags').on('click', function() {
                var rows = [['Drink','Categorias','Tags','Status']];
                $('.mdd-tagger-table tbody tr').each(function() {
                    var cells = $(this).find('td');
                    var name = cells.eq(0).find('strong').text().trim();
                    var cats = [];
                    cells.eq(1).find('span').each(function(){ cats.push($(this).text().trim()); });
                    var tags = [];
                    cells.eq(2).find('span').each(function(){ var t=$(this).text().trim(); if(t!=='—') tags.push(t); });
                    var status = cells.eq(3).text().trim();
                    rows.push(['"'+name+'"', '"'+cats.join(', ')+'"', '"'+tags.join(', ')+'"', status]);
                });
                var csv = rows.map(function(r){return r.join(',')}).join('\n');
                var blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'drinks-tags-export.csv';
                a.click();
            });

            // ─── Search + Filters ───
            function filterTaggerTable() {
                var search = $('#mdd_tagger_search').val().toLowerCase();
                var cat = $('#mdd_tagger_cat_filter').val();
                var status = $('#mdd_tagger_status_filter').val();
                var tag = $('#mdd_tagger_tag_filter').val();
                var visible = 0;
                $('#mdd_tagger_table tbody tr').each(function() {
                    var row = $(this);
                    var name = row.data('name') || '';
                    var cats = (row.data('cats') || '') + '';
                    var tags = (row.data('tags') || '') + '';
                    var st = row.data('status') || '';
                    var show = true;
                    if (search && name.indexOf(search) === -1) show = false;
                    if (cat && cats.indexOf(cat) === -1) show = false;
                    if (status && st !== status) show = false;
                    if (tag && tags.indexOf(tag) === -1) show = false;
                    row.toggle(show);
                    if (show) visible++;
                });
                $('#mdd_tagger_count').text(visible + ' de ' + $('#mdd_tagger_table tbody tr').length + ' drinks');
            }
            $('#mdd_tagger_search').on('input', filterTaggerTable);
            $('#mdd_tagger_cat_filter').on('change', filterTaggerTable);
            $('#mdd_tagger_status_filter').on('change', filterTaggerTable);
            $('#mdd_tagger_tag_filter').on('change', filterTaggerTable);
            filterTaggerTable(); // initial count
        });
        </script>
        <?php
    }

    // ─── Import IA Tab ───
    private static function render_import_tab() {
        $post_type = get_option('mdd_drink_post_type', 'drink');
        $nonce = wp_create_nonce('mdd_ai_nonce');
        ?>
        <!-- Explicação -->
        <div class="mdd-card mdd-info-card">
            <p style="margin:0;font-size:.85rem;line-height:1.7">
                <strong>🤖 Importação em Massa com IA</strong><br>
                Cole uma lista de nomes de drinks (um por linha). O sistema verifica o cache central — se o drink já foi processado por outro cliente, retorna instantaneamente (custo zero). Os que não estão no cache são processados pela IA e adicionados ao cache para futuros clientes.
            </p>
        </div>

        <!-- Passo 1: Cola a lista -->
        <div class="mdd-card">
            <h2>📝 Passo 1 — Lista de Produtos</h2>
            <div class="mdd-field">
                <label>Cole os nomes (um por linha)</label>
                <textarea id="mdd_import_list" rows="8" placeholder="Moscow Mule&#10;Caipirinha&#10;Gin Tônica&#10;Mojito&#10;Piña Colada&#10;Negroni" style="width:100%;font-family:monospace;resize:vertical"></textarea>
            </div>
            <div style="display:flex;gap:10px;align-items:center;margin-top:12px">
                <div class="mdd-field" style="margin:0;width:150px">
                    <label>Tipo</label>
                    <select id="mdd_import_type">
                        <option value="drink">Drink / Bebida</option>
                        <option value="food">Prato / Comida</option>
                    </select>
                </div>
                <button type="button" class="button button-primary" id="mdd_import_check" style="height:38px">🔍 Verificar Cache</button>
                <span id="mdd_import_status" style="font-size:.85rem"></span>
            </div>
        </div>

        <!-- Passo 2: Resultado da verificação -->
        <div class="mdd-card" id="mdd_import_results" style="display:none">
            <h2>📊 Passo 2 — Resultado da Verificação</h2>
            <div id="mdd_import_stats" style="display:flex;gap:14px;margin-bottom:16px"></div>

            <!-- Produtos do cache -->
            <div id="mdd_cached_section" style="display:none;margin-bottom:20px">
                <h3 style="font-size:.9rem;color:var(--mdd-success);margin-bottom:10px">📦 Disponíveis no Cache (custo zero)</h3>
                <div id="mdd_cached_list"></div>
            </div>

            <!-- Produtos que precisam de IA -->
            <div id="mdd_missing_section" style="display:none;margin-bottom:20px">
                <h3 style="font-size:.9rem;color:var(--mdd-accent);margin-bottom:10px">🤖 Precisam de IA</h3>
                <div id="mdd_missing_list"></div>
                <button type="button" class="button" id="mdd_fill_missing" style="margin-top:10px">⚡ Processar com IA</button>
                <span id="mdd_fill_status" style="font-size:.85rem;margin-left:10px"></span>
            </div>
        </div>

        <!-- Passo 3: Importar -->
        <div class="mdd-card" id="mdd_import_final" style="display:none">
            <h2>✅ Passo 3 — Importar Selecionados</h2>
            <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:12px">
                Selecione os drinks que deseja importar. Serão criados como <strong>Rascunho</strong> no CPT "<?php echo esc_html($post_type); ?>" — você revisa e publica depois.
            </p>
            <div style="margin-bottom:12px">
                <label style="font-size:.85rem;cursor:pointer"><input type="checkbox" id="mdd_select_all" checked> Selecionar todos</label>
            </div>
            <div id="mdd_import_table"></div>
            <div style="display:flex;gap:10px;align-items:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--mdd-border)">
                <button type="button" class="button button-primary button-large" id="mdd_do_import">📥 Importar Selecionados como Rascunho</button>
                <span id="mdd_import_final_status" style="font-size:.85rem"></span>
            </div>
        </div>

        <script>
        (function($){
            var allProducts = [];
            var aiNonce = '<?php echo esc_js($nonce); ?>';

            // Passo 1: Verificar cache
            $('#mdd_import_check').on('click', function(){
                var list = $('#mdd_import_list').val().trim();
                if (!list) { alert('Cole a lista de produtos.'); return; }

                var btn = $(this);
                btn.prop('disabled',true).text('Verificando...');
                $('#mdd_import_status').html('<span style="color:var(--mdd-muted)">Consultando cache central...</span>');

                $.post(ajaxurl, {
                    action: 'mdd_ai_batch_check',
                    nonce: aiNonce,
                    products: list,
                    product_type: $('#mdd_import_type').val()
                }, function(res){
                    btn.prop('disabled',false).text('🔍 Verificar Cache');
                    if (!res.success) {
                        $('#mdd_import_status').html('<span style="color:var(--mdd-danger)">❌ '+(res.data&&res.data.message?res.data.message:'Erro')+'</span>');
                        return;
                    }

                    var d = res.data;
                    $('#mdd_import_status').html('<span style="color:var(--mdd-success)">✅ Verificação concluída!</span>');

                    // Stats
                    $('#mdd_import_stats').html(
                        '<div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:12px 16px;text-align:center;min-width:80px"><div style="font-size:1.3rem;font-weight:700;color:var(--mdd-accent)">'+d.stats.total+'</div><div style="font-size:.7rem;color:var(--mdd-muted)">Total</div></div>'+
                        '<div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:12px 16px;text-align:center;min-width:80px"><div style="font-size:1.3rem;font-weight:700;color:var(--mdd-success)">'+d.stats.cached+'</div><div style="font-size:.7rem;color:var(--mdd-muted)">No Cache</div></div>'+
                        '<div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:12px 16px;text-align:center;min-width:80px"><div style="font-size:1.3rem;font-weight:700;color:var(--mdd-accent2)">'+d.stats.need_ai+'</div><div style="font-size:.7rem;color:var(--mdd-muted)">Precisam IA</div></div>'
                    );

                    // Cached products
                    allProducts = [];
                    if (d.cached && d.cached.length) {
                        var html = '';
                        d.cached.forEach(function(p){
                            allProducts.push(p.data);
                            html += '<div style="padding:8px 12px;background:#111;border:1px solid var(--mdd-border);border-radius:8px;margin-bottom:6px;display:flex;align-items:center;gap:8px"><span style="color:var(--mdd-success)">✓</span> <strong style="color:var(--mdd-text)">'+p.name+'</strong> <span style="font-size:.75rem;color:var(--mdd-muted);margin-left:auto">📦 cache</span></div>';
                        });
                        $('#mdd_cached_list').html(html);
                        $('#mdd_cached_section').show();
                    } else {
                        $('#mdd_cached_section').hide();
                    }

                    // Missing products
                    if (d.missing && d.missing.length) {
                        var html = '';
                        d.missing.forEach(function(name){
                            html += '<div class="mdd-missing-item" data-name="'+name+'" style="padding:8px 12px;background:#111;border:1px solid var(--mdd-border);border-radius:8px;margin-bottom:6px;display:flex;align-items:center;gap:8px"><span style="color:var(--mdd-accent2)">⏳</span> <strong style="color:var(--mdd-text)">'+name+'</strong> <span class="mdd-missing-status" style="font-size:.75rem;color:var(--mdd-muted);margin-left:auto">aguardando IA</span></div>';
                        });
                        $('#mdd_missing_list').html(html);
                        $('#mdd_missing_section').show();
                    } else {
                        $('#mdd_missing_section').hide();
                    }

                    $('#mdd_import_results').show();

                    // If all cached, go directly to import step
                    if (!d.missing || d.missing.length === 0) {
                        buildImportTable();
                    }
                }).fail(function(){
                    btn.prop('disabled',false).text('🔍 Verificar Cache');
                    $('#mdd_import_status').html('<span style="color:var(--mdd-danger)">❌ Erro de conexão.</span>');
                });
            });

            // Passo 2: Processar faltantes com IA
            $('#mdd_fill_missing').on('click', function(){
                var items = $('.mdd-missing-item');
                if (!items.length) return;
                var btn = $(this);
                btn.prop('disabled',true);
                var idx = 0;
                var type = $('#mdd_import_type').val();

                function processNext(){
                    if (idx >= items.length) {
                        btn.prop('disabled',false).text('✅ Concluído');
                        $('#mdd_fill_status').html('<span style="color:var(--mdd-success)">Todos processados!</span>');
                        buildImportTable();
                        return;
                    }
                    var item = $(items[idx]);
                    var name = item.data('name');
                    item.find('.mdd-missing-status').html('<span style="color:var(--mdd-accent)">processando...</span>');
                    $('#mdd_fill_status').html('<span style="color:var(--mdd-muted)">'+(idx+1)+'/'+items.length+' — '+name+'</span>');

                    $.post(ajaxurl, {
                        action: 'mdd_ai_fill_single',
                        nonce: aiNonce,
                        product_name: name,
                        product_type: type
                    }, function(res){
                        if (res.success && res.data.data) {
                            allProducts.push(res.data.data);
                            item.find('.mdd-missing-status').html('<span style="color:var(--mdd-success)">✅ ok</span>');
                            item.find('span:first').text('✓').css('color','var(--mdd-success)');
                        } else {
                            item.find('.mdd-missing-status').html('<span style="color:var(--mdd-danger)">❌ falhou</span>');
                        }
                        idx++;
                        processNext();
                    }).fail(function(){
                        item.find('.mdd-missing-status').html('<span style="color:var(--mdd-danger)">❌ erro</span>');
                        idx++;
                        processNext();
                    });
                }
                processNext();
            });

            // Passo 3: Montar tabela de importação
            function buildImportTable(){
                if (!allProducts.length) return;
                var html = '<table class="mdd-tagger-table"><thead><tr><th style="width:30px">✓</th><th>Nome</th><th>Descrição</th><th>Tags</th></tr></thead><tbody>';
                allProducts.forEach(function(p, i){
                    var desc = (p.short_description||'').substring(0,60);
                    var tags = (p.tags||[]).join(', ');
                    html += '<tr><td><input type="checkbox" class="mdd-import-check" data-idx="'+i+'" checked></td>';
                    html += '<td><strong style="color:var(--mdd-text)">'+(p.name||'')+'</strong></td>';
                    html += '<td style="font-size:.82rem;color:var(--mdd-muted)">'+desc+'</td>';
                    html += '<td style="font-size:.78rem;color:var(--mdd-accent)">'+tags+'</td></tr>';
                });
                html += '</tbody></table>';
                $('#mdd_import_table').html(html);
                $('#mdd_import_final').show();
            }

            // Select all toggle
            $('#mdd_select_all').on('change', function(){
                var checked = $(this).is(':checked');
                $('.mdd-import-check').prop('checked', checked);
            });

            // Passo 3: Importar
            $('#mdd_do_import').on('click', function(){
                var selected = [];
                $('.mdd-import-check:checked').each(function(){
                    selected.push(allProducts[$(this).data('idx')]);
                });
                if (!selected.length) { alert('Selecione pelo menos 1 produto.'); return; }

                var btn = $(this);
                btn.prop('disabled',true).text('Importando...');

                $.post(ajaxurl, {
                    action: 'mdd_ai_import_products',
                    nonce: aiNonce,
                    products: JSON.stringify(selected)
                }, function(res){
                    btn.prop('disabled',false).text('📥 Importar Selecionados como Rascunho');
                    if (res.success) {
                        $('#mdd_import_final_status').html('<span style="color:var(--mdd-success)">✅ '+res.data.imported+' drink(s) importados como rascunho! <a href="<?php echo admin_url('edit.php?post_type=' . esc_js($post_type) . '&post_status=draft'); ?>">Ver rascunhos →</a></span>');
                    } else {
                        $('#mdd_import_final_status').html('<span style="color:var(--mdd-danger)">❌ '+(res.data&&res.data.message?res.data.message:'Erro')+'</span>');
                    }
                }).fail(function(){
                    btn.prop('disabled',false).text('📥 Importar Selecionados como Rascunho');
                    $('#mdd_import_final_status').html('<span style="color:var(--mdd-danger)">❌ Erro de conexão.</span>');
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    // ─── Tokens Tab ───
    private static function render_tokens_tab() {
        $tokens = MDD_Token_Manager::get_all_tokens();
        $bridge = new MDD_CPT_Bridge();
        $categories = $bridge->get_categories();
        $active_count = 0;
        foreach ($tokens as $t) { if ($t->is_active) $active_count++; }
        ?>
        <!-- Explicação -->
        <div class="mdd-card mdd-info-card">
            <p style="margin:0;font-size:.85rem;line-height:1.7">
                <strong>🔑 Como funcionam os Tokens?</strong><br>
                Cada TV ou Tablet precisa de um <strong>token exclusivo</strong> para acessar o cardápio. O token é embutido na URL — basta abrir a URL no navegador do dispositivo.
                As configurações globais (aba TV/Tablet) valem para todos. Mas cada token pode ter <strong>overrides</strong>: logo, layout ou filtro de categoria diferentes.
            </p>
        </div>

        <!-- Gerar Novo Token -->
        <div class="mdd-card">
            <h2>➕ Gerar Novo Token</h2>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">
                <div class="mdd-field" style="flex:2;min-width:200px;margin:0">
                    <label>Nome do dispositivo</label>
                    <input type="text" id="mdd_new_device_name" placeholder="Ex: TV do Bar, Tablet Mesa 5">
                </div>
                <div class="mdd-field" style="width:120px;margin:0">
                    <label>Tipo</label>
                    <select id="mdd_new_device_type">
                        <option value="tv">📺 TV</option>
                        <!-- Tablet em standby -->
                    </select>
                </div>
                <button type="button" id="mdd_create_token_btn" class="button button-primary" style="height:38px">Gerar Token</button>
            </div>

            <!-- Overrides (accordion) -->
            <details style="border:1px solid var(--mdd-border);border-radius:10px;background:#111;margin-top:4px">
                <summary style="padding:12px 16px;cursor:pointer;font-size:.85rem;color:var(--mdd-muted)">⚙️ Overrides por dispositivo (opcional)</summary>
                <div style="padding:4px 16px 16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
                    <div class="mdd-field" style="margin:0">
                        <label>CPTs neste dispositivo</label>
                        <select id="mdd_new_cpt_filter" multiple size="3" style="min-height:60px">
                            <?php
                            $all_pts = get_option('mdd_drink_post_types', [get_option('mdd_drink_post_type', 'drink')]);
                            foreach ($all_pts as $pt_name):
                                $pt_obj = get_post_type_object($pt_name);
                                if (!$pt_obj) continue;
                            ?>
                                <option value="<?php echo esc_attr($pt_name); ?>"><?php echo esc_html($pt_obj->label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:var(--mdd-muted);font-size:.72rem">Ctrl+click para selecionar vários. Vazio = todos os CPTs configurados.</small>
                    </div>
                    <div class="mdd-field" style="margin:0">
                        <label>Filtrar por Categoria</label>
                        <select id="mdd_new_category_filter">
                            <option value="">Todas (padrão global)</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat['slug']); ?>"><?php echo esc_html($cat['name']); ?> (<?php echo $cat['count']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:var(--mdd-muted);font-size:.72rem">Mostra apenas drinks desta categoria neste dispositivo.</small>
                    </div>
                    <div class="mdd-field" style="margin:0">
                        <label>Layout TV (override)</label>
                        <select id="mdd_new_layout_override">
                            <option value="">Padrão global</option>
                            <option value="fullscreen">Fullscreen</option>
                            <option value="split">Split</option>
                            <option value="grid">Grid</option>
                        </select>
                        <small style="color:var(--mdd-muted);font-size:.72rem">Ignora o layout da aba TV só para este dispositivo.</small>
                    </div>
                    <div class="mdd-field" style="margin:0">
                        <label>Logo específico (override)</label>
                        <div style="display:flex;gap:6px">
                            <input type="hidden" id="mdd_new_logo_override" value="">
                            <button type="button" class="button mdd-upload-btn" data-target="mdd_new_logo_override" style="font-size:.82rem">Selecionar</button>
                            <button type="button" class="button mdd-remove-btn" data-target="mdd_new_logo_override" style="font-size:.82rem;display:none">Remover</button>
                        </div>
                        <small style="color:var(--mdd-muted);font-size:.72rem">Substitui o logo global só neste dispositivo (prioridade máxima).</small>
                        <div class="mdd-logo-preview" id="mdd_new_logo_override_preview"><span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span></div>
                    </div>
                </div>
            </details>
            <span id="mdd_create_msg" style="font-size:.85rem;display:block;margin-top:10px"></span>
        </div>

        <!-- Token List -->
        <div class="mdd-card" style="padding:0;overflow:hidden">
            <div style="padding:20px 24px 12px;display:flex;align-items:center;justify-content:space-between">
                <h2 style="margin:0!important;padding:0!important;border:none!important">
                    📋 Tokens (<?php echo $active_count; ?> ativo<?php echo $active_count !== 1 ? 's' : ''; ?>)
                </h2>
            </div>
            <?php if (empty($tokens)): ?>
                <div style="padding:24px 32px;text-align:center;color:var(--mdd-muted)">
                    Nenhum token criado. Gere o primeiro token acima para conectar uma TV ou Tablet.
                </div>
            <?php else: ?>
                <div style="max-height:500px;overflow-y:auto">
                <table class="mdd-tagger-table">
                    <thead>
                        <tr>
                            <th>Dispositivo</th>
                            <th>Tipo</th>
                            <th>Token / URL</th>
                            <th>Último Acesso</th>
                            <th>Status</th>
                            <th style="width:130px">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="mdd_tokens_list">
                        <?php foreach ($tokens as $t):
                            $url = MDD_Display_Router::get_display_url($t->device_type, $t->token);
                            $last = $t->last_access ? strtotime($t->last_access . ' UTC') : 0;
                            $ago = $last ? human_time_diff($last) . ' atrás' : '—';
                            $online = $last && (time() - $last) < 600;
                        ?>
                            <tr data-id="<?php echo intval($t->id); ?>">
                                <td>
                                    <strong style="color:var(--mdd-text)"><?php echo esc_html($t->device_name); ?></strong>
                                    <?php
                                    $overrides = [];
                                    if (!empty($t->cpt_filter)) $overrides[] = '🔗 CPTs: ' . $t->cpt_filter;
                                    if (!empty($t->category_filter)) $overrides[] = '📁 ' . $t->category_filter;
                                    if (!empty($t->layout_override)) $overrides[] = '📐 ' . $t->layout_override;
                                    if (!empty($t->logo_override)) $overrides[] = '🖼️ Logo custom';
                                    if (!empty($overrides)): ?>
                                        <div style="font-size:.72rem;color:var(--mdd-muted);margin-top:2px"><?php echo esc_html(implode(' · ', $overrides)); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="display:inline-block;padding:3px 10px;border-radius:6px;font-size:.7rem;font-weight:600;letter-spacing:.5px;text-transform:uppercase;<?php echo $t->device_type === 'tv' ? 'background:rgba(99,102,241,.12);color:#818cf8' : 'background:rgba(34,197,94,.12);color:#22c55e'; ?>">
                                        <?php echo $t->device_type === 'tv' ? '📺 TV' : '📱 TAB'; ?>
                                    </span>
                                </td>
                                <td>
                                    <code style="font-size:.72rem;background:#111;padding:3px 8px;border-radius:4px;color:var(--mdd-accent2);border:1px solid var(--mdd-border);cursor:pointer" onclick="navigator.clipboard.writeText('<?php echo esc_js($url); ?>');this.style.borderColor='var(--mdd-success)';setTimeout(()=>this.style.borderColor='',1500)" title="Clique para copiar URL"><?php echo esc_html(substr($t->token, 0, 12) . '…'); ?></code>
                                </td>
                                <td style="font-size:.8rem;color:var(--mdd-muted)"><?php echo esc_html($ago); ?></td>
                                <td>
                                    <?php if ($t->is_active): ?>
                                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:.8rem">
                                            <span style="width:7px;height:7px;border-radius:50%;background:<?php echo $online ? 'var(--mdd-success)' : '#ffad00'; ?>"></span>
                                            <?php echo $online ? 'Online' : 'Ativo'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size:.8rem;color:var(--mdd-danger)">Revogado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px">
                                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="button button-small" title="Abrir display" style="padding:2px 8px">↗</a>
                                        <button class="button button-small mdd-edit-token-btn" data-id="<?php echo intval($t->id); ?>" data-name="<?php echo esc_attr($t->device_name); ?>" data-cat="<?php echo esc_attr($t->category_filter); ?>" data-layout="<?php echo esc_attr($t->layout_override); ?>" data-cpt="<?php echo esc_attr($t->cpt_filter ?? ''); ?>" style="padding:2px 8px" title="Editar">✏️</button>
                                        <?php if ($t->is_active): ?>
                                            <button class="button button-small mdd-revoke-btn" data-id="<?php echo intval($t->id); ?>" style="padding:2px 8px;color:var(--mdd-danger)">Revogar</button>
                                        <?php else: ?>
                                            <button class="button button-small mdd-reactivate-btn" data-id="<?php echo intval($t->id); ?>" style="padding:2px 8px">Reativar</button>
                                        <?php endif; ?>
                                        <button class="button button-small mdd-delete-btn" data-id="<?php echo intval($t->id); ?>" style="padding:2px 8px;color:var(--mdd-danger)" title="Excluir">✕</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ─── Stats Tab ───
    private static function render_stats_tab() {
        $stats = MDD_Quiz_Engine::get_stats();
        $daily = MDD_Quiz_Engine::get_daily_counts(14);
        $avg7d = MDD_Quiz_Engine::get_7day_avg();
        $drink_ratings = MDD_Quiz_Engine::get_drink_ratings();
        $rating_entries = MDD_Quiz_Engine::get_rating_entries(30);
        $pending_ratings = MDD_Quiz_Engine::get_pending_ratings(30);
        $bridge = new MDD_CPT_Bridge();
        $drinks = $bridge->get_drinks();
        $all_tokens = MDD_Token_Manager::get_all_tokens();
        $drinks_with_images = 0;
        $drinks_with_tags = 0;
        foreach ($drinks as $d) {
            if (!empty($d['image'])) $drinks_with_images++;
            if (!empty($d['profile_tags'])) $drinks_with_tags++;
        }

        // Estimate ROI: chosen drinks * avg price
        $avg_price = 0;
        $prices = [];
        foreach ($drinks as $d) { if ($d['price']) $prices[] = $d['price']; }
        if (!empty($prices)) $avg_price = array_sum($prices) / count($prices);
        $roi_value = $stats['drinks_chosen'] > 0 ? $stats['drinks_chosen'] * $avg_price : 0;

        // Chart max for scaling
        $chart_max = 1;
        foreach ($daily as $d) { if ($d['total'] > $chart_max) $chart_max = $d['total']; }
        ?>

        <!-- Export Button -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px">
            <div style="display:flex;gap:8px;align-items:center">
                <select id="mdd_reset_period" style="padding:6px 12px;background:#111;border:1px solid var(--mdd-border);border-radius:6px;color:var(--mdd-text);font-size:.82rem">
                    <option value="30">Mais de 30 dias</option>
                    <option value="60">Mais de 60 dias</option>
                    <option value="90">Mais de 90 dias</option>
                    <option value="all">Tudo (zerar)</option>
                </select>
                <button type="button" class="button" id="mdd_reset_stats_btn" style="font-size:.82rem;color:var(--mdd-danger)">🗑️ Resetar</button>
            </div>
            <button type="button" class="button" onclick="mddExportStats()" style="font-size:.82rem">📥 Exportar CSV</button>
        </div>

        <!-- KPI Cards -->
        <div class="mdd-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
            <div class="mdd-stat-card" title="Total de quizzes finalizados (exclui abandonados)">
                <span class="mdd-stat-number"><?php echo $stats['total']; ?></span>
                <span class="mdd-stat-label">Quizzes Finalizados</span>
            </div>
            <div class="mdd-stat-card" title="Quizzes finalizados hoje">
                <span class="mdd-stat-number"><?php echo $stats['today']; ?></span>
                <span class="mdd-stat-label">Hoje</span>
            </div>
            <div class="mdd-stat-card" title="Média de quizzes finalizados nos últimos 7 dias">
                <span class="mdd-stat-number"><?php echo $avg7d; ?></span>
                <span class="mdd-stat-label">Média / dia (7d)</span>
            </div>
            <div class="mdd-stat-card" title="Drinks escolhidos via botão Experimentar">
                <span class="mdd-stat-number" style="color:var(--mdd-accent2)"><?php echo $stats['drinks_chosen']; ?></span>
                <span class="mdd-stat-label">Drinks Escolhidos</span>
            </div>
            <div class="mdd-stat-card" title="Vezes que o resultado do Quiz foi compartilhado nas redes">
                <span class="mdd-stat-number" style="color:var(--mdd-accent)"><?php echo $stats['shared_count']; ?></span>
                <span class="mdd-stat-label">Compartilhados 📤</span>
            </div>
            <div class="mdd-stat-card" title="% dos que iniciaram o quiz e finalizaram">
                <span class="mdd-stat-number" style="color:<?php echo $stats['completion_rate'] >= 50 ? 'var(--mdd-success)' : 'var(--mdd-accent)'; ?>"><?php echo $stats['completion_rate']; ?>%</span>
                <span class="mdd-stat-label">Taxa Conclusão</span>
            </div>
            <div class="mdd-stat-card" title="Nota média do Quiz (<?php echo $stats['quiz_rating_count']; ?> avaliações)">
                <span class="mdd-stat-number"><?php echo $stats['avg_quiz_rating'] ?: '—'; ?></span>
                <span class="mdd-stat-label">Nota Quiz ⭐ (<?php echo $stats['quiz_rating_count']; ?>)</span>
            </div>
            <div class="mdd-stat-card" title="Nota média pós-experiência (<?php echo $stats['drink_rating_count']; ?> avaliações)">
                <span class="mdd-stat-number"><?php echo $stats['avg_drink_rating'] ?: '—'; ?></span>
                <span class="mdd-stat-label">Nota Drink ⭐ (<?php echo $stats['drink_rating_count']; ?>)</span>
            </div>
        </div>

        <!-- Volume Chart (CSS-only bar chart) -->
        <div class="mdd-card">
            <h2>📊 Volume de Quizzes — Últimos 14 dias</h2>
            <div style="display:flex;align-items:flex-end;gap:4px;height:140px;padding-top:20px">
                <?php foreach ($daily as $date => $d):
                    $pct = $chart_max > 0 ? ($d['total'] / $chart_max * 100) : 0;
                    $is_today = ($date === current_time('Y-m-d'));
                    $day_label = date_i18n('d/m', strtotime($date));
                    $dow = date_i18n('D', strtotime($date));
                ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
                    <span style="font-size:.65rem;color:var(--mdd-muted)"><?php echo $d['total']; ?></span>
                    <div style="width:100%;max-width:32px;background:<?php echo $is_today ? 'var(--mdd-accent)' : 'rgba(249,115,22,.25)'; ?>;border-radius:4px 4px 0 0;height:<?php echo max(2, $pct); ?>%;min-height:2px;transition:height .3s" title="<?php echo $day_label . ': ' . $d['total'] . ' quizzes, ' . $d['completed'] . ' concluídos'; ?>"></div>
                    <span style="font-size:.55rem;color:<?php echo $is_today ? 'var(--mdd-accent)' : 'var(--mdd-muted)'; ?>;writing-mode:vertical-lr;transform:rotate(180deg);height:24px"><?php echo esc_html($dow); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <!-- Conversão: Recomendado vs Escolhido -->
            <div class="mdd-card">
                <h2>🎯 Conversão — Top 5</h2>
                <p style="font-size:.78rem;color:var(--mdd-muted);margin-bottom:12px">Drinks mais recomendados pelo algoritmo vs efetivamente escolhidos pelo cliente.</p>
                <?php if (!empty($stats['top_recommended'])): ?>
                <table class="mdd-tagger-table">
                    <thead><tr><th>Drink</th><th style="width:80px">Recom.</th><th style="width:80px">Escolhido</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['top_recommended'] as $id => $rec_count):
                        $chosen_count = $stats['top_chosen'][$id] ?? 0;
                        $title = get_the_title($id);
                        if (!$title) continue;
                    ?>
                        <tr>
                            <td><strong style="color:var(--mdd-text)"><?php echo esc_html($title); ?></strong></td>
                            <td style="text-align:center"><span style="color:var(--mdd-accent)"><?php echo intval($rec_count); ?></span></td>
                            <td style="text-align:center"><span style="color:var(--mdd-success)"><?php echo intval($chosen_count); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color:var(--mdd-muted);font-size:.85rem;text-align:center;padding:20px 0">Ainda sem dados. Os dados aparecem após os primeiros quizzes.</p>
                <?php endif; ?>
            </div>

            <!-- Satisfação por Drink -->
            <div class="mdd-card">
                <h2>⭐ Satisfação por Drink</h2>
                <p style="font-size:.78rem;color:var(--mdd-muted);margin-bottom:12px">Avaliações pós-experiência (nota do drink, não do quiz). <?php echo $stats['drink_rating_count']; ?> avaliações recebidas.</p>
                <?php if (!empty($drink_ratings)): ?>
                <table class="mdd-tagger-table">
                    <thead><tr><th>Drink</th><th style="width:60px">Vezes</th><th style="width:80px">Nota</th></tr></thead>
                    <tbody>
                    <?php foreach ($drink_ratings as $dr):
                        $title = get_the_title($dr->chosen_drink_id);
                        if (!$title) continue;
                        $avg = $dr->avg_rating ? round(floatval($dr->avg_rating), 1) : '—';
                        $star_pct = $dr->avg_rating ? (floatval($dr->avg_rating) / 5 * 100) : 0;
                    ?>
                        <tr>
                            <td><strong style="color:var(--mdd-text)"><?php echo esc_html($title); ?></strong></td>
                            <td style="text-align:center;color:var(--mdd-muted)"><?php echo intval($dr->times_chosen); ?></td>
                            <td style="text-align:center">
                                <?php if ($dr->rated_count > 0): ?>
                                    <span style="color:<?php echo $star_pct >= 80 ? 'var(--mdd-success)' : ($star_pct >= 60 ? 'var(--mdd-accent2)' : 'var(--mdd-danger)'); ?>;font-weight:600"><?php echo $avg; ?></span>
                                    <span style="font-size:.7rem;color:var(--mdd-muted)"> / 5</span>
                                <?php else: ?>
                                    <span style="color:var(--mdd-muted);font-size:.8rem">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color:var(--mdd-muted);font-size:.85rem;text-align:center;padding:20px 0">Ainda sem avaliações de drinks.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enviar Avaliação via WhatsApp -->
        <?php if (!empty($pending_ratings)): ?>
        <div class="mdd-card" style="padding:0;overflow:hidden;border:1px solid rgba(37,211,102,.2)">
            <div style="padding:20px 24px 12px;background:rgba(37,211,102,.05);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div>
                    <h2 style="margin:0!important;padding:0!important;border:none!important">📲 Enviar Avaliação via WhatsApp</h2>
                    <p style="font-size:.78rem;color:var(--mdd-muted);margin-top:4px"><?php echo count($pending_ratings); ?> cliente(s) aguardando. <span style="color:#e03c3c">●</span> = há mais de 30min (prioridade).</p>
                </div>
                <button type="button" id="mdd_wa_batch_btn" class="button" style="background:rgba(37,211,102,.15);border:1px solid rgba(37,211,102,.3);color:#25d366;font-weight:600;font-size:.85rem;padding:8px 20px">
                    📲 Enviar Todos (<?php echo count($pending_ratings); ?>)
                </button>
            </div>
            <div id="mdd_wa_batch_status" style="display:none;padding:10px 24px;background:rgba(37,211,102,.03);font-size:.82rem;color:var(--mdd-muted)"></div>
            <div style="max-height:350px;overflow-y:auto">
            <table class="mdd-tagger-table">
                <thead><tr><th>Cliente</th><th>Telefone</th><th>Drink</th><th style="width:90px">Tempo</th><th style="width:120px">Ação</th></tr></thead>
                <tbody>
                <?php
                $wa_links_js = [];
                foreach ($pending_ratings as $pr):
                    $drink_title = get_the_title($pr->chosen_drink_id);
                    if (!$drink_title) $drink_title = 'Drink #' . $pr->chosen_drink_id;
                    $rate_url = home_url('/display/quiz/?rate=' . $pr->rating_token);
                    $phone_clean = preg_replace('/[^0-9]/', '', $pr->customer_phone);
                    if (strlen($phone_clean) <= 11) $phone_clean = '55' . $phone_clean;
                    $wa_text = "Olá" . ($pr->customer_name ? " {$pr->customer_name}" : "") . "! 😊\n\nVocê escolheu o *{$drink_title}* no nosso Quiz de Drinks.\n\nGostou? Avalie com 1 clique:\n{$rate_url}\n\nSua opinião nos ajuda a melhorar! ⭐";
                    $wa_link = 'https://wa.me/' . $phone_clean . '?text=' . rawurlencode($wa_text);
                    $wa_links_js[] = $wa_link;

                    // Timer: minutes since quiz
                    $mins_ago = round((time() - strtotime($pr->created_at)) / 60);
                    $is_urgent = $mins_ago >= 30;
                    if ($mins_ago < 60) {
                        $time_label = $mins_ago . 'min';
                    } elseif ($mins_ago < 1440) {
                        $time_label = round($mins_ago / 60) . 'h';
                    } else {
                        $time_label = round($mins_ago / 1440) . 'd';
                    }
                ?>
                    <tr style="<?php echo $is_urgent ? 'background:rgba(224,60,60,.06)' : ''; ?>">
                        <td>
                            <strong style="color:var(--mdd-text)"><?php echo esc_html($pr->customer_name ?: 'Anônimo'); ?></strong>
                        </td>
                        <td style="font-size:.82rem;color:var(--mdd-muted)"><?php echo esc_html($pr->customer_phone); ?></td>
                        <td style="color:var(--mdd-muted)"><?php echo esc_html($drink_title); ?></td>
                        <td style="text-align:center">
                            <?php if ($is_urgent): ?>
                                <span style="color:#e03c3c;font-weight:600;font-size:.82rem">🔴 <?php echo $time_label; ?></span>
                            <?php else: ?>
                                <span style="color:var(--mdd-success);font-size:.82rem">🟢 <?php echo $time_label; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_attr($wa_link); ?>" target="_blank" rel="noopener" class="mdd-wa-single" style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:rgba(37,211,102,.15);border:1px solid rgba(37,211,102,.3);border-radius:8px;color:#25d366;font-size:.78rem;font-weight:600;text-decoration:none;cursor:pointer">
                                📲 Enviar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <script>
        (function(){
            var waLinks = <?php echo json_encode($wa_links_js); ?>;
            var batchBtn = document.getElementById('mdd_wa_batch_btn');
            var statusEl = document.getElementById('mdd_wa_batch_status');
            var sending = false;

            batchBtn.addEventListener('click', function() {
                if (sending) return;
                if (!confirm('Abrir WhatsApp para ' + waLinks.length + ' cliente(s)?\n\nCada link abrirá em uma nova aba a cada 3 segundos.\nVocê precisa clicar ENVIAR em cada aba do WhatsApp.')) return;

                sending = true;
                batchBtn.disabled = true;
                batchBtn.textContent = 'Enviando...';
                statusEl.style.display = 'block';

                var i = 0;
                function sendNext() {
                    if (i >= waLinks.length) {
                        statusEl.innerHTML = '✅ Todos os ' + waLinks.length + ' links foram abertos! Verifique as abas do WhatsApp.';
                        batchBtn.textContent = '✅ Concluído';
                        sending = false;
                        return;
                    }
                    statusEl.innerHTML = '📲 Enviando ' + (i + 1) + ' de ' + waLinks.length + '...';
                    window.open(waLinks[i], '_blank');
                    i++;
                    setTimeout(sendNext, 3000);
                }
                sendNext();
            });
        })();
        </script>
        <?php endif; ?>

        <!-- Listagem de Avaliações -->
        <div class="mdd-card" style="padding:0;overflow:hidden">
            <div style="padding:20px 24px 12px">
                <h2 style="margin:0!important;padding:0!important;border:none!important">📝 Últimas Avaliações de Drinks</h2>
                <p style="font-size:.78rem;color:var(--mdd-muted);margin-top:4px">Avaliações pós-experiência recebidas via link de rating.</p>
            </div>
            <?php if (!empty($rating_entries)): ?>
            <div style="max-height:400px;overflow-y:auto">
            <table class="mdd-tagger-table">
                <thead><tr><th>Cliente</th><th>Telefone</th><th>Drink</th><th style="width:70px">Nota Drink</th><th style="width:70px">Nota Quiz</th><th style="width:40px">📤</th><th style="width:90px">Data</th></tr></thead>
                <tbody>
                <?php foreach ($rating_entries as $re):
                    $drink_title = get_the_title($re->chosen_drink_id);
                    if (!$drink_title) $drink_title = '#' . $re->chosen_drink_id;
                    $star_color = intval($re->drink_rating) >= 4 ? 'var(--mdd-success)' : (intval($re->drink_rating) >= 3 ? 'var(--mdd-accent2)' : 'var(--mdd-danger)');
                ?>
                    <tr>
                        <td><strong style="color:var(--mdd-text)"><?php echo esc_html($re->customer_name ?: 'Anônimo'); ?></strong></td>
                        <td style="font-size:.78rem;color:var(--mdd-muted)"><?php echo esc_html($re->customer_phone ?: '—'); ?></td>
                        <td style="color:var(--mdd-muted)"><?php echo esc_html($drink_title); ?></td>
                        <td style="text-align:center"><span style="color:<?php echo $star_color; ?>;font-weight:600"><?php echo intval($re->drink_rating); ?></span><span style="font-size:.7rem;color:var(--mdd-muted)"> / 5</span></td>
                        <td style="text-align:center"><?php echo $re->quiz_rating ? intval($re->quiz_rating) . '<span style="font-size:.7rem;color:var(--mdd-muted)"> / 5</span>' : '<span style="color:var(--mdd-muted)">—</span>'; ?></td>
                        <td style="text-align:center"><?php echo !empty($re->shared) ? '✅' : '—'; ?></td>
                        <td style="font-size:.78rem;color:var(--mdd-muted)"><?php echo date_i18n('d/m/Y H:i', strtotime($re->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
                <p style="color:var(--mdd-muted);font-size:.85rem;text-align:center;padding:20px 0">Ainda sem avaliações de drinks recebidas.</p>
            <?php endif; ?>
        </div>

        <!-- ROI Estimado -->
        <div class="mdd-card">
            <h2>💰 ROI Estimado — Vendas Assistidas pelo Quiz</h2>
            <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                Estimativa baseada nos drinks escolhidos via Quiz × preço médio. Não é uma medição direta de vendas — é uma estimativa do <strong>potencial de influência</strong> do Quiz nas decisões dos clientes.
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:16px 20px;text-align:center">
                    <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--mdd-success)"><?php echo $stats['completed']; ?></div>
                    <div style="font-size:.72rem;color:var(--mdd-muted);text-transform:uppercase;letter-spacing:.08em">Drinks escolhidos</div>
                </div>
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:16px 20px;text-align:center">
                    <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--mdd-accent2)"><?php echo $avg_price > 0 ? 'R$ ' . number_format($avg_price, 2, ',', '.') : '—'; ?></div>
                    <div style="font-size:.72rem;color:var(--mdd-muted);text-transform:uppercase;letter-spacing:.08em">Preço médio</div>
                </div>
                <div style="background:#111;border:1px solid rgba(34,197,94,.15);border-radius:10px;padding:16px 20px;text-align:center">
                    <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--mdd-success)"><?php echo $roi_value > 0 ? 'R$ ' . number_format($roi_value, 2, ',', '.') : '—'; ?></div>
                    <div style="font-size:.72rem;color:var(--mdd-muted);text-transform:uppercase;letter-spacing:.08em">Vendas assistidas (est.)</div>
                </div>
            </div>
        </div>

        <!-- Saúde do Cardápio -->
        <div class="mdd-card">
            <h2>🩺 Saúde do Cardápio</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 16px;text-align:center">
                    <div style="font-size:1.3rem;font-weight:700;color:var(--mdd-accent)"><?php echo count($drinks); ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted)">Drinks cadastrados</div>
                </div>
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 16px;text-align:center">
                    <div style="font-size:1.3rem;font-weight:700;color:<?php echo $drinks_with_images === count($drinks) ? 'var(--mdd-success)' : 'var(--mdd-accent2)'; ?>"><?php echo $drinks_with_images; ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted)">Com foto</div>
                </div>
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 16px;text-align:center">
                    <div style="font-size:1.3rem;font-weight:700;color:<?php echo $drinks_with_tags === count($drinks) ? 'var(--mdd-success)' : 'var(--mdd-danger)'; ?>"><?php echo $drinks_with_tags; ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted)">Com tags (Quiz)</div>
                </div>
                <div style="background:#111;border:1px solid var(--mdd-border);border-radius:10px;padding:14px 16px;text-align:center">
                    <div style="font-size:1.3rem;font-weight:700;color:var(--mdd-accent)"><?php echo count($all_tokens); ?></div>
                    <div style="font-size:.7rem;color:var(--mdd-muted)">Dispositivos</div>
                </div>
            </div>
        </div>

        <script>
        function mddExportStats() {
            var rows = [['Métrica','Valor']];
            rows.push(['Quizzes Finalizados', '<?php echo $stats['total']; ?>']);
            rows.push(['Hoje', '<?php echo $stats['today']; ?>']);
            rows.push(['Média/dia 7d', '<?php echo $avg7d; ?>']);
            rows.push(['Drinks Escolhidos', '<?php echo $stats['drinks_chosen']; ?>']);
            rows.push(['Compartilhados', '<?php echo $stats['shared_count']; ?>']);
            rows.push(['Taxa Conclusão', '<?php echo $stats['completion_rate']; ?>%']);
            rows.push(['Nota Quiz', '<?php echo $stats['avg_quiz_rating']; ?> (<?php echo $stats['quiz_rating_count']; ?> avaliações)']);
            rows.push(['Nota Drink', '<?php echo $stats['avg_drink_rating']; ?> (<?php echo $stats['drink_rating_count']; ?> avaliações)']);
            rows.push(['ROI Estimado', 'R$ <?php echo number_format($roi_value, 2, ',', '.'); ?>']);
            rows.push([]);
            rows.push(['Top Drinks Escolhidos','Vezes']);
            <?php foreach ($stats['top_chosen'] as $did => $cnt):
                $dtitle = get_the_title($did);
            ?>
            rows.push(['<?php echo esc_js($dtitle); ?>', '<?php echo $cnt; ?>']);
            <?php endforeach; ?>
            var csv = rows.map(function(r){ return r.join(','); }).join('\n');
            var blob = new Blob(['\uFEFF' + csv], {type:'text/csv;charset=utf-8;'});
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'drink-display-stats-<?php echo date('Y-m-d'); ?>.csv';
            a.click();
        }
        </script>
        <?php
    }

    // ─── Guide Tab ───
    private static function render_guide_tab() {
        include MDD_PLUGIN_DIR . 'admin/views/guia.php';
    }

    // ─── AJAX Handlers ───
    public static function ajax_create_token() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $name = sanitize_text_field($_POST['device_name'] ?? '');
        $type = sanitize_text_field($_POST['device_type'] ?? 'tv');
        $logo_input = sanitize_text_field($_POST['logo_override'] ?? '');
        // Media uploader stores attachment ID; convert to URL
        $logo = '';
        if ($logo_input) {
            if (is_numeric($logo_input)) {
                $logo = wp_get_attachment_url(intval($logo_input));
            } else {
                $logo = esc_url_raw($logo_input);
            }
        }
        $category = sanitize_text_field($_POST['category_filter'] ?? '');
        $layout = sanitize_text_field($_POST['layout_override'] ?? '');
        $cpt_filter = sanitize_text_field($_POST['cpt_filter'] ?? '');

        if (empty($name)) wp_send_json_error(__('Nome do dispositivo obrigatório.', 'madame-drink-display'));

        $token = MDD_Token_Manager::create_token($name, $type, [
            'logo_override'   => $logo,
            'cpt_filter'      => $cpt_filter,
            'category_filter' => $category,
            'layout_override' => $layout,
        ]);
        $url = MDD_Display_Router::get_display_url($type, $token);

        wp_send_json_success(['token' => $token, 'url' => $url]);
    }

    public static function ajax_revoke_token() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        MDD_Token_Manager::revoke_token(intval($_POST['token_id']));
        wp_send_json_success();
    }

    public static function ajax_reactivate_token() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        MDD_Token_Manager::reactivate_token(intval($_POST['token_id']));
        wp_send_json_success();
    }

    public static function ajax_delete_token() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        MDD_Token_Manager::delete_token(intval($_POST['token_id']));
        wp_send_json_success();
    }

    public static function ajax_edit_token() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $id = intval($_POST['token_id'] ?? 0);
        if (!$id) wp_send_json_error('ID inválido');

        $updates = [];
        if (isset($_POST['device_name'])) $updates['device_name'] = sanitize_text_field($_POST['device_name']);
        if (isset($_POST['category_filter'])) $updates['category_filter'] = sanitize_text_field($_POST['category_filter']);
        if (isset($_POST['layout_override'])) $updates['layout_override'] = sanitize_text_field($_POST['layout_override']);
        if (isset($_POST['cpt_filter'])) $updates['cpt_filter'] = sanitize_text_field($_POST['cpt_filter']);

        if (!empty($updates)) {
            MDD_Token_Manager::update_token($id, $updates);
        }
        wp_send_json_success();
    }

    public static function ajax_reset_stats() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        global $wpdb;
        $table = $wpdb->prefix . 'mdd_quiz_results';
        $period = sanitize_text_field($_POST['period'] ?? 'all');

        if ($period === 'all') {
            $wpdb->query("TRUNCATE TABLE $table");
        } else {
            $days = intval($period);
            if ($days > 0) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                ));
            }
        }
        wp_send_json_success(['message' => 'Estatísticas resetadas.']);
    }

    public static function ajax_save_quiz_questions() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $questions = json_decode(stripslashes($_POST['questions'] ?? '[]'), true);
        if (is_array($questions)) {
            update_option('mdd_quiz_questions', $questions);
            wp_send_json_success();
        }
        wp_send_json_error(__('Dados inválidos.', 'madame-drink-display'));
    }
}
