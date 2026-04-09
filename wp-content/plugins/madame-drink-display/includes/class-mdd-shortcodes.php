<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcodes para uso no site WordPress:
 * [mdd_quiz_qr]      - Exibe QR Code do Quiz
 * [mdd_quiz_button]   - Botão estilizado para o Quiz
 * [mdd_drink_showcase] - Vitrine de drinks destaque
 */
class MDD_Shortcodes {

    public static function init() {
        add_shortcode('mdd_quiz_qr', [__CLASS__, 'quiz_qr']);
        add_shortcode('mdd_quiz_button', [__CLASS__, 'quiz_button']);
        add_shortcode('mdd_drink_showcase', [__CLASS__, 'drink_showcase']);
    }

    /**
     * [mdd_quiz_qr size="200" title="Faça o Quiz!" show_text="1"]
     */
    public static function quiz_qr($atts) {
        $atts = shortcode_atts([
            'size'      => 200,
            'title'     => 'Descubra seu drink ideal!',
            'show_text' => '1',
            'align'     => 'center',
        ], $atts);

        $qr_url = MDD_QR_Generator::get_quiz_qr_url(intval($atts['size']));
        $primary = get_option('mdd_primary_color', '#C8962E');

        ob_start();
        ?>
        <div class="mdd-qr-shortcode" style="text-align:<?php echo esc_attr($atts['align']); ?>;padding:20px 0">
            <?php if ($atts['show_text'] === '1'): ?>
                <p style="font-size:18px;font-weight:600;color:<?php echo esc_attr($primary); ?>;margin-bottom:12px">
                    <?php echo esc_html($atts['title']); ?>
                </p>
            <?php endif; ?>
            <a href="<?php echo esc_url(MDD_QR_Generator::get_quiz_url()); ?>" target="_blank" style="display:inline-block">
                <img src="<?php echo esc_url($qr_url); ?>" alt="Quiz QR Code"
                     width="<?php echo intval($atts['size']); ?>"
                     height="<?php echo intval($atts['size']); ?>"
                     style="border-radius:12px;border:2px solid #eee">
            </a>
            <?php if ($atts['show_text'] === '1'): ?>
                <p style="font-size:13px;color:#999;margin-top:8px">Escaneie com a câmera do celular</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [mdd_quiz_button text="Descubra seu Drink!" style="primary"]
     */
    public static function quiz_button($atts) {
        $atts = shortcode_atts([
            'text'  => 'Descubra seu Drink! 🍸',
            'style' => 'primary', // primary, accent, outline
            'size'  => 'medium',  // small, medium, large
            'align' => 'center',
        ], $atts);

        $primary = get_option('mdd_primary_color', '#C8962E');
        $accent  = get_option('mdd_accent_color', '#E8593C');
        $url = MDD_QR_Generator::get_quiz_url();

        $sizes = [
            'small'  => 'padding:8px 20px;font-size:13px',
            'medium' => 'padding:12px 32px;font-size:15px',
            'large'  => 'padding:16px 40px;font-size:17px',
        ];

        $styles = [
            'primary' => "background:{$primary};color:#fff;border:none",
            'accent'  => "background:{$accent};color:#fff;border:none",
            'outline' => "background:transparent;color:{$primary};border:2px solid {$primary}",
        ];

        $btn_style = ($styles[$atts['style']] ?? $styles['primary']) . ';' .
                     ($sizes[$atts['size']] ?? $sizes['medium']) .
                     ';border-radius:30px;text-decoration:none;display:inline-block;font-weight:600;letter-spacing:.5px;transition:opacity .2s;cursor:pointer';

        return sprintf(
            '<div style="text-align:%s;padding:16px 0"><a href="%s" target="_blank" style="%s" onmouseover="this.style.opacity=0.85" onmouseout="this.style.opacity=1">%s</a></div>',
            esc_attr($atts['align']),
            esc_url($url),
            esc_attr($btn_style),
            esc_html($atts['text'])
        );
    }

    /**
     * [mdd_drink_showcase count="4" category="" columns="2"]
     */
    public static function drink_showcase($atts) {
        $atts = shortcode_atts([
            'count'    => 4,
            'category' => '',
            'columns'  => 2,
            'style'    => 'cards', // cards, minimal
        ], $atts);

        $bridge = new MDD_CPT_Bridge();
        $args = [];
        if (!empty($atts['category'])) {
            $args['category'] = $atts['category'];
        }

        $drinks = $bridge->get_drinks($args);
        $drinks = array_slice($drinks, 0, intval($atts['count']));

        if (empty($drinks)) return '<p>Nenhum drink encontrado.</p>';

        $primary = get_option('mdd_primary_color', '#C8962E');
        $cols = intval($atts['columns']);

        ob_start();
        ?>
        <div class="mdd-showcase" style="display:grid;grid-template-columns:repeat(<?php echo $cols; ?>,1fr);gap:16px;padding:16px 0">
            <?php foreach ($drinks as $d): ?>
                <div class="mdd-showcase-card" style="background:#f9f9f9;border-radius:12px;overflow:hidden;border:1px solid #eee">
                    <?php if ($d['image']): ?>
                        <div style="aspect-ratio:1;overflow:hidden;background:#eee">
                            <img src="<?php echo esc_url($d['image']); ?>" alt="<?php echo esc_attr($d['title']); ?>"
                                 style="width:100%;height:100%;object-fit:cover" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div style="padding:12px 14px">
                        <h4 style="margin:0 0 4px;font-size:16px"><?php echo esc_html($d['title']); ?></h4>
                        <?php if ($d['short_desc']): ?>
                            <p style="margin:0 0 6px;font-size:13px;color:#777;line-height:1.4"><?php echo esc_html($d['short_desc']); ?></p>
                        <?php endif; ?>
                        <?php if ($d['price_formatted']): ?>
                            <span style="font-size:17px;font-weight:700;color:<?php echo esc_attr($primary); ?>"><?php echo esc_html($d['price_formatted']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
