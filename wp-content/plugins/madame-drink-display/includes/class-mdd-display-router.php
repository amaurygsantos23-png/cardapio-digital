<?php
if (!defined('ABSPATH')) exit;

class MDD_Display_Router {

    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite_rules'], 1);
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_action('template_redirect', [__CLASS__, 'handle_display']);

        // Auto-flush rewrite rules if our rules are missing
        add_action('init', [__CLASS__, 'maybe_flush_rules'], 99);
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule('^display/tv/?$', 'index.php?mdd_display=tv', 'top');
        add_rewrite_rule('^display/tablet/?$', 'index.php?mdd_display=tablet', 'top');
        add_rewrite_rule('^display/quiz/?$', 'index.php?mdd_display=quiz', 'top');
    }

    /**
     * Auto-detect if rewrite rules are missing and flush
     */
    public static function maybe_flush_rules() {
        $rules = get_option('rewrite_rules', []);
        if (is_array($rules)) {
            $found = false;
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'display/') !== false && strpos($rewrite, 'mdd_display') !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                flush_rewrite_rules(false);
            }
        }
    }

    public static function add_query_vars($vars) {
        $vars[] = 'mdd_display';
        $vars[] = 'rate';
        return $vars;
    }

    public static function handle_display() {
        $display = get_query_var('mdd_display');

        // Fallback: parse URL directly if rewrite didn't catch it
        if (empty($display)) {
            $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            if (preg_match('#^display/(tv|tablet|quiz)/?$#', $path, $m)) {
                $display = $m[1];
            }
        }

        if (empty($display)) return;
        if (!in_array($display, ['tv', 'tablet', 'quiz'])) return;

        // License check — all displays require active license
        if (!MDD_License::can_show_display()) {
            MDD_License::render_expired_page();
            // render_expired_page() calls exit
        }

        // For TV and Tablet, validate token
        if (in_array($display, ['tv', 'tablet'])) {
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            if (empty($token)) {
                wp_die(
                    __('Token de acesso obrigatório. Acesse /display/tv/?token=SEU_TOKEN', 'madame-drink-display'),
                    __('Acesso Negado', 'madame-drink-display'),
                    ['response' => 401]
                );
            }

            $device = MDD_Token_Manager::validate_token($token);
            if (!$device) {
                wp_die(
                    __('Token inválido ou revogado. Contate o administrador.', 'madame-drink-display'),
                    __('Acesso Negado', 'madame-drink-display'),
                    ['response' => 403]
                );
            }

            $GLOBALS['mdd_device'] = $device;
            $GLOBALS['mdd_token'] = $token;
        }

        // Quiz: check for ?rate=TOKEN (drink rating page)
        if ($display === 'quiz') {
            $rate = isset($_GET['rate']) ? sanitize_text_field($_GET['rate']) : get_query_var('rate', '');
            if (!empty($rate)) {
                $GLOBALS['mdd_rating_token'] = $rate;
            }
        }

        // Load the appropriate template
        $template = MDD_PLUGIN_DIR . "templates/{$display}-display.php";

        if (file_exists($template)) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            include $template;
            exit;
        }
    }

    public static function get_display_url($mode, $token = '') {
        $url = home_url("/display/{$mode}/");
        if ($token) {
            $url = add_query_arg('token', $token, $url);
        }
        return $url;
    }
}
