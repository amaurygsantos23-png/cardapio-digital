<?php
/**
 * Plugin Name: Drink Display
 * Plugin URI: https://a3tecnologias.com
 * Description: Cardápio digital de drinks para Smart TVs, Tablets e Quiz interativo via QR Code. Conecta-se a qualquer CPT WordPress ou JetEngine.
 * Version: 2.2.0
 * Author: Amaury Santos
 * Author URI: https://a3tecnologias.com
 * Text Domain: madame-drink-display
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

define('MDD_VERSION', '2.2.0');
define('MDD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MDD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MDD_PLUGIN_BASENAME', plugin_basename(__FILE__));

final class Madame_Drink_Display {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-license.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-cpt-bridge.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-token-manager.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-rest-api.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-quiz-engine.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-display-router.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-settings.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-auto-tagger.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-qr-generator.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-drink-metabox.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-shortcodes.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-pwa.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-elementor-widgets.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-dashboard-widget.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-health-check.php';
        require_once MDD_PLUGIN_DIR . 'includes/class-mdd-ai-assistant.php';

        if (is_admin()) {
            require_once MDD_PLUGIN_DIR . 'admin/class-mdd-admin-page.php';
        }
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // License system (must be first)
        MDD_License::init();

        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);

        // Display router
        MDD_Display_Router::init();

        // Shortcodes
        MDD_Shortcodes::init();

        // Elementor widgets (if Elementor is active)
        if (did_action('elementor/loaded') || defined('ELEMENTOR_VERSION')) {
            MDD_Elementor_Widgets::init();
        } else {
            add_action('elementor/loaded', function() {
                MDD_Elementor_Widgets::init();
            });
        }

        // Admin
        if (is_admin()) {
            MDD_Admin_Page::init();
            MDD_Drink_Metabox::init();
            MDD_Dashboard_Widget::init();
            new MDD_AI_Assistant();

            // AJAX: Auto-tag single drink
            add_action('wp_ajax_mdd_auto_tag_single', [$this, 'ajax_auto_tag_single']);

            // AJAX: Auto-tag all drinks
            add_action('wp_ajax_mdd_auto_tag_all', [$this, 'ajax_auto_tag_all']);

            // AJAX: Detect meta fields from CPT
            add_action('wp_ajax_mdd_detect_fields', [$this, 'ajax_detect_fields']);

            // AJAX: Print QR card
            add_action('wp_ajax_mdd_print_qr_card', ['MDD_QR_Generator', 'ajax_print_qr_card']);
        }
    }

    public function init() {
        load_plugin_textdomain('madame-drink-display', false, dirname(MDD_PLUGIN_BASENAME) . '/languages');

        // Register quiz profile tags taxonomy if not exists
        if (!taxonomy_exists('mdd_drink_profile')) {
            register_taxonomy('mdd_drink_profile', $this->get_drink_post_type(), [
                'labels' => [
                    'name'          => __('Perfil do Drink', 'madame-drink-display'),
                    'singular_name' => __('Tag de Perfil', 'madame-drink-display'),
                    'search_items'  => __('Buscar Tags', 'madame-drink-display'),
                    'all_items'     => __('Todas as Tags', 'madame-drink-display'),
                    'edit_item'     => __('Editar Tag', 'madame-drink-display'),
                    'add_new_item'  => __('Adicionar Tag', 'madame-drink-display'),
                ],
                'hierarchical'  => false,
                'public'        => false,
                'show_ui'       => true,
                'show_in_rest'  => true,
                'rewrite'       => false,
            ]);
        }
    }

    public function register_rest_routes() {
        $api = new MDD_Rest_API();
        $api->register_routes();

        // PWA manifest and service worker
        MDD_PWA::register_routes();
    }

    public function maybe_enqueue_assets() {
        // Assets are loaded only in display templates
    }

    public function get_drink_post_type() {
        return get_option('mdd_drink_post_type', 'drink');
    }

    public function activate() {
        // Default options
        $defaults = [
            'mdd_drink_post_type'     => 'drink',
            'mdd_tv_slide_duration'   => 8,
            'mdd_tv_transition'       => 'fade',
            'mdd_tv_layout'           => 'fullscreen',
            'mdd_tv_show_price'       => 1,
            'mdd_tv_show_qr'          => 1,
            'mdd_tablet_columns'      => 2,
            'mdd_tablet_columns_portrait' => 1,
            'mdd_tablet_timeout'      => 60,
            'mdd_tablet_show_price'   => 1,
            'mdd_tablet_show_badge'   => 1,
            'mdd_tablet_show_desc'    => 1,
            'mdd_tablet_quiz_text'    => 'Faça o Quiz ✨',
            'mdd_tablet_screensaver_text' => 'Cardápio de Drinks',
            'mdd_tablet_header_title' => 'Drinks',
            'mdd_tablet_font_title'   => 'Playfair Display',
            'mdd_tablet_font_body'    => 'Outfit',
            'mdd_quiz_num_questions'  => 4,
            'mdd_quiz_ask_name'       => 1,
            'mdd_quiz_skip_base_if_no_alcohol' => 1,
            'mdd_quiz_cta_text'       => 'Experimentar',
            'mdd_quiz_confirm_text'   => 'Informe ao garçom para confirmar seu pedido.',
            'mdd_quiz_pairing_text'   => 'Sabia que esse drink combina com',
            'mdd_quiz_show_rating'    => 1,
            'mdd_quiz_rating_text'    => 'Como foi o Quiz?',
            'mdd_quiz_share_text'     => '',
            'mdd_quiz_post_rating_msg'=> 'Se possível, após sua experiência com o drink, volte e avalie-o. É importante para nós!',
            'mdd_hide_field'          => '_mdd_hide_from_display',
            'mdd_primary_color'       => '#C8962E',
            'mdd_secondary_color'     => '#1A1A2E',
            'mdd_accent_color'        => '#E8593C',
            'mdd_establishment_logo'  => '',
            'mdd_event_logo'          => '',
            'mdd_event_mode'          => 0,
            'mdd_event_name'          => '',
            'mdd_food_post_type'      => '',
            'mdd_field_map'           => [],
            'mdd_logo_max_height_tv'  => 60,
            'mdd_logo_max_height_tablet' => 50,
            'mdd_logo_max_height_quiz'   => 80,
            'mdd_qr_fg_color'         => '#1A1A2E',
            'mdd_qr_bg_color'         => '#FFFFFF',
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Create tokens table
        MDD_Token_Manager::create_table();

        // Create quiz results table
        MDD_Quiz_Engine::create_table();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        MDD_License::deactivate();
        flush_rewrite_rules();
    }

    /**
     * AJAX: Auto-tag a single drink
     */
    public function ajax_auto_tag_single() {
        check_ajax_referer('mdd_auto_tag', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) wp_send_json_error('Post ID inválido');

        $tags = MDD_Auto_Tagger::auto_tag_drink($post_id);
        wp_send_json_success(['tags' => $tags, 'post_id' => $post_id]);
    }

    /**
     * AJAX: Auto-tag all drinks
     */
    public function ajax_auto_tag_all() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $overwrite = !empty($_POST['overwrite']);
        $results = MDD_Auto_Tagger::auto_tag_all($overwrite);

        $tagged = 0;
        $skipped = 0;
        foreach ($results as $r) {
            if ($r['status'] === 'tagged') $tagged++;
            else $skipped++;
        }

        wp_send_json_success([
            'tagged'  => $tagged,
            'skipped' => $skipped,
            'total'   => count($results),
        ]);
    }

    /**
     * AJAX: Detect meta fields from a CPT
     */
    public function ajax_detect_fields() {
        check_ajax_referer('mdd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $post_type = sanitize_text_field($_POST['post_type'] ?? '');
        if (empty($post_type) || !post_type_exists($post_type)) {
            wp_send_json_error('CPT inválido');
        }

        $keys = MDD_Settings::detect_meta_keys($post_type);

        // Auto-detect best matches
        $suggestions = [];
        $fields = ['price', 'short_desc', 'video', 'ingredients', 'gallery', 'variants', 'food_pairing', 'context_msg'];
        foreach ($fields as $f) {
            $suggestions[$f] = MDD_Settings::auto_detect_field($f, $keys);
        }

        wp_send_json_success([
            'keys'        => $keys,
            'suggestions' => $suggestions,
            'count'       => count($keys),
        ]);
    }
}

function mdd() {
    return Madame_Drink_Display::instance();
}

// Initialize
mdd();
