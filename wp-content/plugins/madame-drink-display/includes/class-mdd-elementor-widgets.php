<?php
if (!defined('ABSPATH')) exit;

/**
 * Integração com Elementor Pro.
 * Registra widgets nativos do Elementor para uso no editor visual.
 */
class MDD_Elementor_Widgets {

    public static function init() {
        add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [__CLASS__, 'add_category']);
    }

    public static function add_category($elements_manager) {
        $elements_manager->add_category('madame-drink-display', [
            'title' => __('Drink Display', 'madame-drink-display'),
            'icon'  => 'eicon-drink',
        ]);
    }

    public static function register_widgets($widgets_manager) {
        // Only register if Elementor is active
        if (!did_action('elementor/loaded')) return;

        require_once MDD_PLUGIN_DIR . 'includes/elementor/widget-quiz-qr.php';
        require_once MDD_PLUGIN_DIR . 'includes/elementor/widget-drink-showcase.php';

        $widgets_manager->register(new MDD_Elementor_Quiz_QR_Widget());
        $widgets_manager->register(new MDD_Elementor_Drink_Showcase_Widget());
    }
}
