<?php
if (!defined('ABSPATH')) exit;

/**
 * Adiciona metabox na tela de edição do drink para gerenciar tags de perfil
 * e visualizar sugestões automáticas do Auto-Tagger.
 */
class MDD_Drink_Metabox {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'register_metabox']);
        add_action('save_post', [__CLASS__, 'save_metabox'], 10, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_metabox_assets']);
    }

    public static function register_metabox() {
        $post_type = get_option('mdd_drink_post_type', 'drink');

        add_meta_box(
            'mdd_drink_profile',
            __('🍸 Drink Display — Perfil & Tags', 'madame-drink-display'),
            [__CLASS__, 'render_metabox'],
            $post_type,
            'side',
            'default'
        );

        add_meta_box(
            'mdd_drink_display_fields',
            __('🖥️ Drink Display — Campos Extras', 'madame-drink-display'),
            [__CLASS__, 'render_fields_metabox'],
            $post_type,
            'normal',
            'default'
        );
    }

    /**
     * Profile tags metabox (sidebar)
     */
    public static function render_metabox($post) {
        wp_nonce_field('mdd_drink_metabox', 'mdd_drink_metabox_nonce');

        $current_tags = wp_get_object_terms($post->ID, 'mdd_drink_profile', ['fields' => 'slugs']);
        if (is_wp_error($current_tags)) $current_tags = [];

        // Get suggestions from auto-tagger
        $suggestions = MDD_Auto_Tagger::get_suggestions($post->ID);

        // All possible tags
        $all_tags = [
            'Sabor'    => ['citrico', 'doce', 'tropical', 'herbaceo'],
            'Textura'  => ['refrescante', 'cremoso', 'efervescente'],
            'Força'    => ['suave', 'forte', 'sem-alcool'],
            'Base'     => ['vodka', 'gin', 'rum', 'cachaca', 'whisky'],
            'Tipo'     => ['aperitivo', 'classico', 'premium'],
        ];
        ?>
        <div class="mdd-metabox-tags">
            <?php foreach ($all_tags as $group_label => $tags): ?>
                <div class="mdd-tag-group">
                    <strong class="mdd-tag-group-label"><?php echo esc_html($group_label); ?></strong>
                    <div class="mdd-tag-checkboxes">
                        <?php foreach ($tags as $tag_slug):
                            $checked = in_array($tag_slug, $current_tags);
                            $suggested = false;
                            $score = 0;
                            foreach ($suggestions as $s) {
                                if ($s['tag'] === $tag_slug) { $suggested = true; $score = $s['score']; break; }
                            }
                            $label = ucfirst(str_replace('-', ' ', $tag_slug));
                        ?>
                            <label class="mdd-tag-label <?php echo $suggested ? 'mdd-tag-suggested' : ''; ?> <?php echo $checked ? 'mdd-tag-active' : ''; ?>">
                                <input type="checkbox" name="mdd_profile_tags[]" value="<?php echo esc_attr($tag_slug); ?>" <?php checked($checked); ?>>
                                <?php echo esc_html($label); ?>
                                <?php if ($suggested && !$checked): ?>
                                    <span class="mdd-tag-score" title="Sugestão automática (score: <?php echo intval($score); ?>)">✨</span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($suggestions)): ?>
                <div class="mdd-auto-tag-notice">
                    <small>✨ = sugerido pelo Auto-Tagger com base na descrição</small>
                </div>
            <?php endif; ?>

            <button type="button" class="button button-small mdd-auto-tag-btn" data-post-id="<?php echo intval($post->ID); ?>" style="margin-top:8px;width:100%">
                ⚡ Auto-preencher Tags
            </button>
        </div>

        <style>
            .mdd-metabox-tags{padding:4px 0}
            .mdd-tag-group{margin-bottom:10px}
            .mdd-tag-group-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#666;margin-bottom:4px}
            .mdd-tag-checkboxes{display:flex;flex-wrap:wrap;gap:4px}
            .mdd-tag-label{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border:1px solid #ddd;border-radius:12px;font-size:11px;cursor:pointer;transition:all .2s;background:#f9f9f9}
            .mdd-tag-label:hover{border-color:#C8962E;background:#faf6ed}
            .mdd-tag-label.mdd-tag-active{background:#C8962E;color:#fff;border-color:#C8962E}
            .mdd-tag-label.mdd-tag-suggested{border-color:#e8d5a8;background:#fdf8ed}
            .mdd-tag-label input[type="checkbox"]{display:none}
            .mdd-tag-score{font-size:10px}
            .mdd-auto-tag-notice{font-size:10px;color:#999;margin-top:8px;padding:4px 6px;background:#f9f9f9;border-radius:4px}
        </style>

        <script>
        jQuery(function($) {
            // Toggle visual state on click
            $('.mdd-tag-label').on('click', function() {
                var cb = $(this).find('input[type="checkbox"]');
                // jQuery handles the checkbox toggle; we just toggle the class
                setTimeout(function() {
                    $('.mdd-tag-label').each(function() {
                        $(this).toggleClass('mdd-tag-active', $(this).find('input').is(':checked'));
                    });
                }, 10);
            });

            // Auto-tag button
            $('.mdd-auto-tag-btn').on('click', function() {
                var btn = $(this);
                var postId = btn.data('post-id');
                btn.prop('disabled', true).text('Analisando...');

                $.post(ajaxurl, {
                    action: 'mdd_auto_tag_single',
                    nonce: '<?php echo wp_create_nonce('mdd_auto_tag'); ?>',
                    post_id: postId
                }, function(res) {
                    btn.prop('disabled', false).text('⚡ Auto-preencher Tags');
                    if (res.success && res.data.tags) {
                        // Check the suggested tags
                        $('input[name="mdd_profile_tags[]"]').prop('checked', false);
                        $('.mdd-tag-label').removeClass('mdd-tag-active');
                        res.data.tags.forEach(function(tag) {
                            var cb = $('input[name="mdd_profile_tags[]"][value="' + tag + '"]');
                            cb.prop('checked', true);
                            cb.closest('.mdd-tag-label').addClass('mdd-tag-active');
                        });
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Extra display fields metabox (main content area)
     */
    public static function render_fields_metabox($post) {
        $video = get_post_meta($post->ID, '_mdd_video', true);
        $featured = get_post_meta($post->ID, '_mdd_featured', true);
        $short_desc = get_post_meta($post->ID, '_mdd_short_description', true);
        $hidden = get_post_meta($post->ID, '_mdd_hide_from_display', true);
        ?>
        <table class="form-table" style="margin:0">
            <tr>
                <th><label><?php _e('Visibilidade no Display', 'madame-drink-display'); ?></label></th>
                <td>
                    <label style="display:flex;align-items:center;gap:6px">
                        <input type="checkbox" name="mdd_hide_from_display" value="1" <?php checked($hidden, '1'); ?>>
                        <strong style="color:#e03c3c"><?php _e('Ocultar este produto do Display (TV, Tablet, Quiz)', 'madame-drink-display'); ?></strong>
                    </label>
                    <p class="description"><?php _e('Quando marcado, este drink NÃO aparece nas telas de exibição nem nas sugestões do Quiz.', 'madame-drink-display'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Destaque na TV', 'madame-drink-display'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="mdd_featured" value="1" <?php checked($featured, '1'); ?>>
                        <?php _e('Exibir como destaque no slideshow da TV', 'madame-drink-display'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="mdd_short_description"><?php _e('Descrição Curta (Display)', 'madame-drink-display'); ?></label></th>
                <td>
                    <textarea name="mdd_short_description" id="mdd_short_description" rows="2" class="large-text" placeholder="<?php _e('Texto curto exibido nos slides da TV e cards do Tablet', 'madame-drink-display'); ?>"><?php echo esc_textarea($short_desc); ?></textarea>
                    <p class="description"><?php _e('Se vazio, será usado um trecho do conteúdo principal.', 'madame-drink-display'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mdd_video"><?php _e('Vídeo Curto', 'madame-drink-display'); ?></label></th>
                <td>
                    <input type="url" name="mdd_video" id="mdd_video" value="<?php echo esc_url($video); ?>" class="large-text" placeholder="https://seusite.com/wp-content/uploads/video-drink.mp4">
                    <p class="description"><?php _e('URL do vídeo (MP4, 5-15 segundos). Exibido nos slides da TV e no detalhe do Tablet.', 'madame-drink-display'); ?></p>
                    <button type="button" class="button mdd-upload-video-btn" style="margin-top:4px">
                        <?php _e('Selecionar da Biblioteca', 'madame-drink-display'); ?>
                    </button>
                </td>
            </tr>
            <?php
            $food_cpt = get_option('mdd_food_post_type', '');
            if ($food_cpt && post_type_exists($food_cpt)):
                $saved_foods = get_post_meta($post->ID, '_mdd_food_pairing', true);
                $saved_ids = !empty($saved_foods) ? array_map('intval', explode(',', $saved_foods)) : [];
                $food_posts = get_posts(['post_type' => $food_cpt, 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
            ?>
            <tr>
                <th><label><?php _e('🍽️ Harmonização', 'madame-drink-display'); ?></label></th>
                <td>
                    <select name="mdd_food_pairing[]" multiple size="5" style="width:100%;min-height:100px">
                        <?php foreach ($food_posts as $fp): ?>
                            <option value="<?php echo $fp->ID; ?>" <?php echo in_array($fp->ID, $saved_ids) ? 'selected' : ''; ?>><?php echo esc_html($fp->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Ctrl+click para selecionar vários pratos que combinam com este drink. Aparece no Quiz: "Sabia que combina com..."', 'madame-drink-display'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <script>
        jQuery(function($) {
            $('.mdd-upload-video-btn').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({ title: 'Selecionar Vídeo', library: { type: 'video' }, multiple: false });
                frame.on('select', function() {
                    var url = frame.state().get('selection').first().toJSON().url;
                    $('#mdd_video').val(url);
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Save metabox data
     */
    public static function save_metabox($post_id, $post) {
        if (!isset($_POST['mdd_drink_metabox_nonce'])) return;
        if (!wp_verify_nonce($_POST['mdd_drink_metabox_nonce'], 'mdd_drink_metabox')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $post_types = get_option('mdd_drink_post_types', [get_option('mdd_drink_post_type', 'drink')]);
        if (!in_array($post->post_type, $post_types)) return;

        // Profile tags
        $tags = isset($_POST['mdd_profile_tags']) ? array_map('sanitize_text_field', $_POST['mdd_profile_tags']) : [];
        wp_set_object_terms($post_id, $tags, 'mdd_drink_profile');

        // Extra fields
        update_post_meta($post_id, '_mdd_hide_from_display', isset($_POST['mdd_hide_from_display']) ? '1' : '0');
        update_post_meta($post_id, '_mdd_featured', isset($_POST['mdd_featured']) ? '1' : '0');
        update_post_meta($post_id, '_mdd_short_description', sanitize_textarea_field($_POST['mdd_short_description'] ?? ''));
        update_post_meta($post_id, '_mdd_video', esc_url_raw($_POST['mdd_video'] ?? ''));

        // Food pairing (harmonização)
        if (isset($_POST['mdd_food_pairing'])) {
            $food_ids = array_map('intval', $_POST['mdd_food_pairing']);
            $food_ids = array_filter($food_ids, function($id) { return $id > 0; });
            update_post_meta($post_id, '_mdd_food_pairing', implode(',', $food_ids));
        } else {
            update_post_meta($post_id, '_mdd_food_pairing', '');
        }
    }

    public static function enqueue_metabox_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;

        global $post_type;
        if ($post_type !== get_option('mdd_drink_post_type', 'drink')) return;

        wp_enqueue_media();
    }
}
