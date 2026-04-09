<?php
if (!defined('ABSPATH')) exit;

/**
 * Adiciona um widget no Dashboard principal do WordPress
 * com resumo de drinks, dispositivos e quizzes.
 */
class MDD_Dashboard_Widget {

    public static function init() {
        add_action('wp_dashboard_setup', [__CLASS__, 'register_widget']);
    }

    public static function register_widget() {
        wp_add_dashboard_widget(
            'mdd_dashboard_widget',
            '🍸 Drink Display',
            [__CLASS__, 'render_widget']
        );
    }

    public static function render_widget() {
        $bridge = new MDD_CPT_Bridge();
        $drinks = $bridge->get_drinks();
        $tokens = MDD_Token_Manager::get_all_tokens();
        $stats = MDD_Quiz_Engine::get_stats();
        $primary = get_option('mdd_primary_color', '#C8962E');

        $active_tokens = array_filter($tokens, function($t) { return $t->is_active; });
        $drinks_no_img = array_filter($drinks, function($d) { return empty($d['image']); });
        $drinks_no_tags = array_filter($drinks, function($d) { return empty($d['profile_tags']); });

        $event_mode = get_option('mdd_event_mode', 0);
        $event_name = get_option('mdd_event_name', '');
        ?>
        <style>
            .mdd-dw-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
            .mdd-dw-stat{text-align:center;padding:10px 6px;background:#f9f9f9;border-radius:6px}
            .mdd-dw-stat-num{display:block;font-size:24px;font-weight:700;color:<?php echo esc_attr($primary); ?>;line-height:1.2}
            .mdd-dw-stat-label{font-size:11px;color:#888}
            .mdd-dw-alerts{margin-top:10px}
            .mdd-dw-alert{display:flex;align-items:center;gap:6px;padding:6px 10px;border-radius:5px;font-size:12px;margin-bottom:4px}
            .mdd-dw-alert--warn{background:#fff8e1;color:#e65100}
            .mdd-dw-alert--ok{background:#e8f5e9;color:#2e7d32}
            .mdd-dw-alert--event{background:#faf6ed;color:#8a6d24;border:1px solid #e8d5a8}
            .mdd-dw-links{margin-top:12px;padding-top:10px;border-top:1px solid #eee;display:flex;gap:8px;flex-wrap:wrap}
            .mdd-dw-links a{font-size:12px}
        </style>

        <div class="mdd-dw-grid">
            <div class="mdd-dw-stat">
                <span class="mdd-dw-stat-num"><?php echo count($drinks); ?></span>
                <span class="mdd-dw-stat-label"><?php _e('Drinks', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-dw-stat">
                <span class="mdd-dw-stat-num"><?php echo count($active_tokens); ?></span>
                <span class="mdd-dw-stat-label"><?php _e('Dispositivos', 'madame-drink-display'); ?></span>
            </div>
            <div class="mdd-dw-stat">
                <span class="mdd-dw-stat-num"><?php echo $stats['today']; ?></span>
                <span class="mdd-dw-stat-label"><?php _e('Quizzes Hoje', 'madame-drink-display'); ?></span>
            </div>
        </div>

        <?php if ($event_mode && $event_name): ?>
            <div class="mdd-dw-alert mdd-dw-alert--event">
                🎉 <strong><?php _e('Modo Evento:', 'madame-drink-display'); ?></strong>&nbsp;<?php echo esc_html($event_name); ?>
            </div>
        <?php endif; ?>

        <div class="mdd-dw-alerts">
            <?php if (!empty($drinks_no_img)): ?>
                <div class="mdd-dw-alert mdd-dw-alert--warn">
                    ⚠️ <?php echo count($drinks_no_img); ?> <?php _e('drink(s) sem foto', 'madame-drink-display'); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($drinks_no_tags)): ?>
                <div class="mdd-dw-alert mdd-dw-alert--warn">
                    🏷️ <?php echo count($drinks_no_tags); ?> <?php _e('drink(s) sem tags de perfil (Quiz)', 'madame-drink-display'); ?>
                </div>
            <?php endif; ?>
            <?php if (empty($drinks_no_img) && empty($drinks_no_tags)): ?>
                <div class="mdd-dw-alert mdd-dw-alert--ok">
                    ✓ <?php _e('Todos os drinks com foto e tags', 'madame-drink-display'); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mdd-dw-links">
            <a href="<?php echo admin_url('admin.php?page=mdd-settings'); ?>" class="button button-small button-primary"><?php _e('Dashboard', 'madame-drink-display'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=tokens'); ?>" class="button button-small"><?php _e('Tokens', 'madame-drink-display'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=mdd-settings&tab=stats'); ?>" class="button button-small"><?php _e('Estatísticas', 'madame-drink-display'); ?></a>
            <a href="<?php echo esc_url(home_url('/display/quiz/')); ?>" class="button button-small" target="_blank"><?php _e('Quiz ↗', 'madame-drink-display'); ?></a>
        </div>
        <?php
    }
}
