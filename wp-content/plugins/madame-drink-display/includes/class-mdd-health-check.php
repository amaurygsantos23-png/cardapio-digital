<?php
if (!defined('ABSPATH')) exit;

/**
 * Verifica a saúde do sistema e identifica problemas comuns.
 */
class MDD_Health_Check {

    /**
     * Run all checks and return results
     */
    public static function run_checks() {
        $checks = [];

        // 1. CPT exists
        $post_type = get_option('mdd_drink_post_type', 'drink');
        $checks['cpt'] = [
            'label'  => __('Custom Post Type', 'madame-drink-display'),
            'status' => post_type_exists($post_type) ? 'ok' : 'error',
            'detail' => post_type_exists($post_type)
                ? sprintf(__('CPT "%s" encontrado.', 'madame-drink-display'), $post_type)
                : sprintf(__('CPT "%s" não existe. Verifique se o JetEngine está ativo.', 'madame-drink-display'), $post_type),
        ];

        // 2. Drinks exist
        $bridge = new MDD_CPT_Bridge();
        $drinks = $bridge->get_drinks();
        $checks['drinks'] = [
            'label'  => __('Drinks cadastrados', 'madame-drink-display'),
            'status' => count($drinks) > 0 ? 'ok' : 'warn',
            'detail' => count($drinks) > 0
                ? sprintf(__('%d drinks encontrados.', 'madame-drink-display'), count($drinks))
                : __('Nenhum drink publicado.', 'madame-drink-display'),
        ];

        // 3. Drinks with images
        $no_img = array_filter($drinks, function($d) { return empty($d['image']); });
        $checks['images'] = [
            'label'  => __('Imagens dos drinks', 'madame-drink-display'),
            'status' => empty($no_img) ? 'ok' : 'warn',
            'detail' => empty($no_img)
                ? __('Todos os drinks possuem imagem.', 'madame-drink-display')
                : sprintf(__('%d drink(s) sem imagem destacada: %s', 'madame-drink-display'),
                    count($no_img),
                    implode(', ', array_map(function($d) { return $d['title']; }, array_slice($no_img, 0, 5)))),
        ];

        // 4. Profile tags
        $no_tags = array_filter($drinks, function($d) { return empty($d['profile_tags']); });
        $checks['tags'] = [
            'label'  => __('Tags de perfil (Quiz)', 'madame-drink-display'),
            'status' => empty($no_tags) ? 'ok' : (count($no_tags) === count($drinks) ? 'error' : 'warn'),
            'detail' => empty($no_tags)
                ? __('Todos os drinks possuem tags de perfil.', 'madame-drink-display')
                : sprintf(__('%d drink(s) sem tags. Rode o Auto-Tagger.', 'madame-drink-display'), count($no_tags)),
        ];

        // 5. Logo
        $logo = MDD_Settings::get_active_logo();
        $checks['logo'] = [
            'label'  => __('Logo configurado', 'madame-drink-display'),
            'status' => !empty($logo) ? 'ok' : 'warn',
            'detail' => !empty($logo)
                ? __('Logo ativo encontrado.', 'madame-drink-display')
                : __('Nenhum logo configurado. As telas ficarão sem logo.', 'madame-drink-display'),
        ];

        // 6. Tokens
        $tokens = MDD_Token_Manager::get_all_tokens();
        $active = array_filter($tokens, function($t) { return $t->is_active; });
        $checks['tokens'] = [
            'label'  => __('Tokens de dispositivo', 'madame-drink-display'),
            'status' => count($active) > 0 ? 'ok' : 'warn',
            'detail' => count($active) > 0
                ? sprintf(__('%d token(s) ativo(s).', 'madame-drink-display'), count($active))
                : __('Nenhum token ativo. TVs e Tablets não poderão acessar.', 'madame-drink-display'),
        ];

        // 7. Permalinks (check if rewrite rules exist)
        $rules = get_option('rewrite_rules', []);
        $has_rules = false;
        if (is_array($rules)) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'display/tv') !== false || strpos($rule, 'display/tablet') !== false) {
                    $has_rules = true;
                    break;
                }
            }
        }
        $checks['permalinks'] = [
            'label'  => __('Permalinks (rotas /display/)', 'madame-drink-display'),
            'status' => $has_rules ? 'ok' : 'error',
            'detail' => $has_rules
                ? __('Rotas registradas corretamente.', 'madame-drink-display')
                : __('Rotas não encontradas! Vá em Configurações → Links Permanentes e clique "Salvar".', 'madame-drink-display'),
        ];

        // 8. REST API accessible
        $rest_url = rest_url('mdd/v1/');
        $checks['rest_api'] = [
            'label'  => __('REST API', 'madame-drink-display'),
            'status' => !empty($rest_url) ? 'ok' : 'error',
            'detail' => sprintf(__('Disponível em %s', 'madame-drink-display'), $rest_url),
        ];

        // 9. SSL
        $checks['ssl'] = [
            'label'  => __('SSL (HTTPS)', 'madame-drink-display'),
            'status' => is_ssl() ? 'ok' : 'warn',
            'detail' => is_ssl()
                ? __('Site acessível via HTTPS.', 'madame-drink-display')
                : __('Site sem HTTPS. Dispositivos podem bloquear conteúdo misto (mixed content).', 'madame-drink-display'),
        ];

        // 10. Database tables
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'mdd_tokens';
        $quiz_table = $wpdb->prefix . 'mdd_quiz_results';
        $t_exists = $wpdb->get_var("SHOW TABLES LIKE '$tokens_table'") === $tokens_table;
        $q_exists = $wpdb->get_var("SHOW TABLES LIKE '$quiz_table'") === $quiz_table;
        $checks['database'] = [
            'label'  => __('Tabelas do banco de dados', 'madame-drink-display'),
            'status' => ($t_exists && $q_exists) ? 'ok' : 'error',
            'detail' => ($t_exists && $q_exists)
                ? __('Tabelas mdd_tokens e mdd_quiz_results OK.', 'madame-drink-display')
                : __('Tabelas ausentes. Desative e reative o plugin.', 'madame-drink-display'),
        ];

        return $checks;
    }

    /**
     * Render health check in admin
     */
    public static function render() {
        $checks = self::run_checks();
        $icons = ['ok' => '✅', 'warn' => '⚠️', 'error' => '❌'];
        $colors = ['ok' => '#e8f5e9', 'warn' => '#fff8e1', 'error' => '#fce4ec'];
        $text_colors = ['ok' => '#2e7d32', 'warn' => '#e65100', 'error' => '#c62828'];
        ?>
        <div class="mdd-card">
            <h2><?php _e('Diagnóstico do Sistema', 'madame-drink-display'); ?></h2>
            <p class="description"><?php _e('Verificação automática de todos os componentes do Drink Display.', 'madame-drink-display'); ?></p>

            <table class="wp-list-table widefat" style="margin-top:12px">
                <thead>
                    <tr>
                        <th style="width:30px"></th>
                        <th><?php _e('Componente', 'madame-drink-display'); ?></th>
                        <th><?php _e('Status', 'madame-drink-display'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td style="text-align:center"><?php echo $icons[$check['status']]; ?></td>
                            <td><strong><?php echo esc_html($check['label']); ?></strong></td>
                            <td style="color:<?php echo $text_colors[$check['status']]; ?>;font-size:13px">
                                <?php echo esc_html($check['detail']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
