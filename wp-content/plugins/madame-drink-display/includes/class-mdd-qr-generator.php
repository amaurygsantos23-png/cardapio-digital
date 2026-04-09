<?php
if (!defined('ABSPATH')) exit;

/**
 * Gera QR Codes para uso em materiais impressos, mesas e displays.
 * Suporta geração via Google Charts API (sem dependência de lib PHP).
 */
class MDD_QR_Generator {

    /**
     * Get QR Code image URL via Google Charts API
     */
    public static function get_qr_url($data, $size = 300, $color = null, $bgcolor = null) {
        $size = intval($size);
        $qr_color = $color ?: get_option('mdd_qr_fg_color', '1A1A2E');
        $qr_bg    = $bgcolor ?: get_option('mdd_qr_bg_color', 'FFFFFF');
        // Remove # if present
        $qr_color = ltrim($qr_color, '#');
        $qr_bg    = ltrim($qr_bg, '#');

        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'data'   => $data,
            'size'   => "{$size}x{$size}",
            'format' => 'png',
            'margin' => 10,
            'color'  => $qr_color,
            'bgcolor'=> $qr_bg,
        ]);
    }

    /**
     * Get Quiz URL for the current site
     */
    public static function get_quiz_url() {
        return home_url('/display/quiz/');
    }

    /**
     * Get QR Code for quiz with custom parameters
     */
    public static function get_quiz_qr_url($size = 400) {
        return self::get_qr_url(self::get_quiz_url(), $size);
    }

    /**
     * Generate printable QR card HTML (for table tents, flyers, etc.)
     */
    public static function get_printable_card($options = []) {
        $defaults = [
            'size'         => 400,
            'title'        => 'Descubra seu drink ideal!',
            'subtitle'     => 'Escaneie o QR Code e faça o Quiz',
            'logo'         => MDD_Settings::get_active_logo(),
            'show_url'     => true,
            'primary_color'=> get_option('mdd_primary_color', '#C8962E'),
            'bg_color'     => get_option('mdd_secondary_color', '#1A1A2E'),
            'format'       => 'card', // card, minimal, banner
        ];
        $o = wp_parse_args($options, $defaults);

        $qr_url = self::get_quiz_qr_url($o['size']);
        $quiz_url = self::get_quiz_url();

        ob_start();
        ?>
        <div class="mdd-qr-card mdd-qr-card--<?php echo esc_attr($o['format']); ?>" style="--qr-primary:<?php echo esc_attr($o['primary_color']); ?>;--qr-bg:<?php echo esc_attr($o['bg_color']); ?>">
            <?php if ($o['logo']): ?>
                <img class="mdd-qr-card__logo" src="<?php echo esc_url($o['logo']); ?>" alt="Logo">
            <?php endif; ?>

            <h3 class="mdd-qr-card__title"><?php echo esc_html($o['title']); ?></h3>

            <div class="mdd-qr-card__qr-wrap">
                <img class="mdd-qr-card__qr" src="<?php echo esc_url($qr_url); ?>" alt="QR Code Quiz" width="<?php echo intval($o['size']); ?>" height="<?php echo intval($o['size']); ?>">
            </div>

            <p class="mdd-qr-card__subtitle"><?php echo esc_html($o['subtitle']); ?></p>

            <?php if ($o['show_url']): ?>
                <span class="mdd-qr-card__url"><?php echo esc_html(str_replace(['https://', 'http://'], '', $quiz_url)); ?></span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render QR Codes admin section
     */
    public static function render_admin_section() {
        // Save QR settings
        if (isset($_POST['mdd_save_qr']) && check_admin_referer('mdd_qr_nonce')) {
            update_option('mdd_qr_fg_color', sanitize_hex_color($_POST['mdd_qr_fg_color'] ?? '#1A1A2E'));
            update_option('mdd_qr_bg_color', sanitize_hex_color($_POST['mdd_qr_bg_color'] ?? '#FFFFFF'));
            echo '<div class="notice notice-success"><p>✅ Configurações do QR Code salvas!</p></div>';
        }

        $quiz_url = self::get_quiz_url();
        $fg_color = get_option('mdd_qr_fg_color', '#1A1A2E');
        $bg_color = get_option('mdd_qr_bg_color', '#FFFFFF');
        $qr_url_sm = self::get_quiz_qr_url(200);
        $qr_url_md = self::get_quiz_qr_url(400);
        $qr_url_lg = self::get_quiz_qr_url(800);
        $logo = MDD_Settings::get_active_logo();
        $primary = get_option('mdd_primary_color', '#C8962E');
        $bg = get_option('mdd_secondary_color', '#1A1A2E');
        $site_domain = wp_parse_url(home_url(), PHP_URL_HOST);
        ?>
        <form method="post">
        <?php wp_nonce_field('mdd_qr_nonce'); ?>

        <!-- QR Colors -->
        <div class="mdd-card">
            <h2>🎨 Cores do QR Code</h2>
            <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                Personalize as cores dos QR Codes gerados. As cores são aplicadas em todos os QR Codes (quiz, personalizado, impressão).
            </p>
            <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-end">
                <div class="mdd-field" style="width:120px">
                    <label>Cor do QR</label>
                    <input type="color" name="mdd_qr_fg_color" value="<?php echo esc_attr($fg_color); ?>" style="height:42px;width:100%;border-radius:8px;border:1px solid var(--mdd-border);background:#111;cursor:pointer;padding:3px">
                    <small style="color:var(--mdd-muted);font-size:.72rem">Pontos do QR</small>
                </div>
                <div class="mdd-field" style="width:120px">
                    <label>Fundo do QR</label>
                    <input type="color" name="mdd_qr_bg_color" value="<?php echo esc_attr($bg_color); ?>" style="height:42px;width:100%;border-radius:8px;border:1px solid var(--mdd-border);background:#111;cursor:pointer;padding:3px">
                    <small style="color:var(--mdd-muted);font-size:.72rem">Fundo do QR</small>
                </div>
                <button type="submit" name="mdd_save_qr" class="button button-primary" style="height:38px">Salvar Cores</button>
            </div>
        </div>

        </form>

        <!-- QR Code Previews -->
        <div class="mdd-card">
            <h2>📱 QR Code do Quiz</h2>
            <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                Imprima estes QR Codes em mesas, cardápios ou flyers. O cliente escaneia com a câmera do celular e acessa o Quiz automaticamente.
            </p>
            <div class="mdd-qr-sizes">
                <?php
                $sizes = [
                    ['url' => $qr_url_sm, 'px' => '200×200px', 'use' => 'Cardápio digital, posts', 'file' => 'qr-quiz-200.png'],
                    ['url' => $qr_url_md, 'px' => '400×400px', 'use' => 'Mesas, flyers (ideal)', 'file' => 'qr-quiz-400.png'],
                    ['url' => $qr_url_lg, 'px' => '800×800px', 'use' => 'Banners, painéis', 'file' => 'qr-quiz-800.png'],
                ];
                foreach ($sizes as $s): ?>
                <div class="mdd-qr-size-option">
                    <img src="<?php echo esc_url($s['url']); ?>" alt="QR" width="120" height="120" style="border-radius:8px">
                    <div class="mdd-qr-size-info">
                        <strong><?php echo $s['px']; ?></strong>
                        <span><?php echo $s['use']; ?></span>
                        <a href="#" onclick="mddForceDownload('<?php echo esc_url($s['url']); ?>','<?php echo $s['file']; ?>');return false;" class="button button-small" style="margin-top:4px">⬇ Download</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quiz URL -->
        <div class="mdd-card">
            <h2>🔗 URL do Quiz</h2>
            <div class="mdd-url-copy-box">
                <input type="text" readonly value="<?php echo esc_attr($quiz_url); ?>" id="mdd_quiz_url_field" style="flex:1;max-width:500px;font-family:monospace">
                <button type="button" class="button" onclick="document.getElementById('mdd_quiz_url_field').select();document.execCommand('copy');this.textContent='✅ Copiado!';setTimeout(()=>this.textContent='Copiar',2000)">Copiar</button>
            </div>
            <p style="font-size:.82rem;color:var(--mdd-muted);margin-top:8px">O Quiz é público — qualquer pessoa com o link ou QR Code acessa sem precisar de token.</p>
        </div>

        <!-- Printable Card Preview -->
        <div class="mdd-card">
            <h2>🖨️ Card para Impressão em Mesas</h2>
            <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                Card pronto para imprimir e plastificar. Use como display de mesa convidando o cliente para o Quiz.
            </p>
            <div class="mdd-print-preview">
                <div style="background:<?php echo esc_attr($bg); ?>;color:#fff;max-width:300px;padding:28px;border-radius:14px;text-align:center;font-family:'Outfit',sans-serif">
                    <?php if ($logo): ?>
                        <img src="<?php echo esc_url($logo); ?>" alt="" style="max-height:36px;max-width:60%;margin:0 auto 14px;display:block;opacity:.8">
                    <?php endif; ?>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:3px;color:<?php echo esc_attr($primary); ?>;margin-bottom:6px">Quiz de Drinks</div>
                    <div style="font-family:'Playfair Display',Georgia,serif;font-size:20px;font-weight:600;margin-bottom:16px;line-height:1.3">Descubra seu<br>drink ideal!</div>
                    <div style="background:#fff;padding:10px;border-radius:10px;display:inline-block;margin-bottom:12px">
                        <img src="<?php echo esc_url($qr_url_md); ?>" alt="QR" width="160" height="160" style="display:block">
                    </div>
                    <div style="font-size:11px;color:rgba(255,255,255,.5);line-height:1.5">Escaneie com a câmera<br>do seu celular</div>
                </div>
            </div>
            <button type="button" class="button button-primary" onclick="window.open('<?php echo esc_url(admin_url('admin-ajax.php?action=mdd_print_qr_card&nonce=' . wp_create_nonce('mdd_print_qr'))); ?>','_blank')" style="margin-top:12px">
                🖨️ Abrir Versão para Impressão (A6)
            </button>
        </div>

        <!-- Custom QR Code (domain-based) -->
        <div class="mdd-card">
            <h2>🔧 QR Code Personalizado</h2>
            <p style="font-size:.85rem;color:var(--mdd-muted);margin-bottom:16px;line-height:1.6">
                Gere QR Codes para outras páginas do seu site. O domínio é fixo — você edita apenas o caminho.
            </p>
            <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
                <span style="font-family:monospace;font-size:.9rem;color:var(--mdd-muted);padding:9px 12px;background:#111;border:1px solid var(--mdd-border);border-radius:8px 0 0 8px;white-space:nowrap"><?php echo esc_html(home_url('/')); ?></span>
                <input type="text" id="mdd_custom_qr_path" placeholder="comidas" style="flex:1;min-width:120px;border-radius:0!important;border-left:none!important">
                <select id="mdd_custom_qr_size" style="width:80px;border-radius:0 8px 8px 0!important">
                    <option value="200">200px</option>
                    <option value="400" selected>400px</option>
                    <option value="800">800px</option>
                </select>
                <button type="button" class="button" id="mdd_gen_custom_qr" style="margin-left:8px">Gerar QR</button>
            </div>
            <div id="mdd_custom_qr_result" style="display:none;padding:16px;background:#111;border:1px solid var(--mdd-border);border-radius:10px;text-align:center">
                <img id="mdd_custom_qr_img" src="" alt="QR" style="max-width:180px;border-radius:8px;margin-bottom:10px">
                <br>
                <a id="mdd_custom_qr_download" href="#" class="button button-small" onclick="mddForceDownload(document.getElementById('mdd_custom_qr_img').src,'qr-custom.png');return false;">⬇ Download</a>
                <div id="mdd_custom_qr_url_display" style="margin-top:8px;font-family:monospace;font-size:.78rem;color:var(--mdd-muted)"></div>
            </div>
        </div>

        <script>
        // Force download (works with cross-origin images)
        function mddForceDownload(url, filename) {
            fetch(url)
                .then(function(r) { return r.blob(); })
                .then(function(blob) {
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = filename;
                    a.click();
                    URL.revokeObjectURL(a.href);
                })
                .catch(function() {
                    // Fallback: open in new tab
                    window.open(url, '_blank');
                });
        }

        jQuery(function($) {
            $('#mdd_gen_custom_qr').on('click', function() {
                var path = $('#mdd_custom_qr_path').val().trim();
                var size = $('#mdd_custom_qr_size').val();
                var fullUrl = '<?php echo esc_js(home_url('/')); ?>' + path;
                var fg = '<?php echo esc_js(ltrim($fg_color, '#')); ?>';
                var bg = '<?php echo esc_js(ltrim($bg_color, '#')); ?>';
                var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=' + encodeURIComponent(fullUrl) + '&size=' + size + 'x' + size + '&format=png&margin=10&color=' + fg + '&bgcolor=' + bg;
                $('#mdd_custom_qr_img').attr('src', qrUrl);
                $('#mdd_custom_qr_download').attr('href', qrUrl);
                $('#mdd_custom_qr_url_display').text(fullUrl);
                $('#mdd_custom_qr_result').show();
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler - printable QR card page
     */
    public static function ajax_print_qr_card() {
        if (!check_ajax_referer('mdd_print_qr', 'nonce', false)) {
            wp_die('Nonce inválido');
        }

        $logo = MDD_Settings::get_active_logo();
        $primary = get_option('mdd_primary_color', '#C8962E');
        $bg = get_option('mdd_secondary_color', '#1A1A2E');
        $event_mode = get_option('mdd_event_mode', 0);
        $event_name = get_option('mdd_event_name', '');
        $qr_url = self::get_quiz_qr_url(600);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>QR Code Quiz — Impressão</title>
            <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
            <style>
                @page{size:A6 portrait;margin:0}
                *{margin:0;padding:0;box-sizing:border-box}
                body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f0f0f0;font-family:'Outfit',sans-serif}
                .card{width:105mm;min-height:148mm;background:<?php echo esc_attr($bg); ?>;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;text-align:center;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,.2)}
                .card__logo{max-height:48px;max-width:65%;margin-bottom:20px;opacity:.8}
                .card__badge{font-size:10px;text-transform:uppercase;letter-spacing:4px;color:<?php echo esc_attr($primary); ?>;margin-bottom:10px}
                .card__title{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;line-height:1.25;margin-bottom:24px}
                .card__title span{color:<?php echo esc_attr($primary); ?>}
                .card__qr-wrap{background:#fff;padding:14px;border-radius:14px;margin-bottom:20px}
                .card__qr-wrap img{display:block;width:200px;height:200px}
                .card__instructions{font-size:13px;color:rgba(255,255,255,.5);line-height:1.6}
                .card__event{font-size:11px;color:<?php echo esc_attr($primary); ?>;margin-top:16px;letter-spacing:2px;text-transform:uppercase}
                @media print{body{background:#fff}.card{box-shadow:none;border-radius:0;width:100%;min-height:100vh}}
                .no-print{margin-top:20px}
                @media print{.no-print{display:none}}
            </style>
        </head>
        <body>
            <div>
                <div class="card">
                    <?php if ($logo): ?>
                        <img class="card__logo" src="<?php echo esc_url($logo); ?>" alt="Logo">
                    <?php endif; ?>
                    <div class="card__badge">Quiz de Drinks</div>
                    <h1 class="card__title">Descubra seu<br><span>drink ideal!</span></h1>
                    <div class="card__qr-wrap">
                        <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code">
                    </div>
                    <p class="card__instructions">Escaneie o QR Code com a<br>câmera do seu celular</p>
                    <?php if ($event_mode && $event_name): ?>
                        <div class="card__event"><?php echo esc_html($event_name); ?></div>
                    <?php endif; ?>
                </div>
                <div class="no-print" style="text-align:center">
                    <button onclick="window.print()" style="padding:10px 24px;font-size:14px;cursor:pointer;border:none;background:<?php echo esc_attr($primary); ?>;color:#fff;border-radius:8px;margin-top:20px">
                        Imprimir
                    </button>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
