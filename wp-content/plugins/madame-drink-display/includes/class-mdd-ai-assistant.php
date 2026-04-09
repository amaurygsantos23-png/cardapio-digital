<?php
if (!defined('ABSPATH')) exit;

/**
 * Assistente IA de Cadastro
 *
 * Botão "Preencher com IA" na edição do drink + Importação em massa
 * Chama o servidor central (a3tecnologias.com) que verifica cache antes de chamar IA
 */
class MDD_AI_Assistant {

    const API_ENDPOINT = '/ai/fill';
    const BATCH_ENDPOINT = '/ai/batch-check';

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('wp_ajax_mdd_ai_fill_product', [$this, 'ajax_fill_product']);
        add_action('wp_ajax_mdd_ai_batch_check', [$this, 'ajax_batch_check']);
        add_action('wp_ajax_mdd_ai_fill_single', [$this, 'ajax_fill_single']);
        add_action('wp_ajax_mdd_ai_import_products', [$this, 'ajax_import_products']);
    }

    /**
     * Add metabox to drink edit screen
     */
    public function add_metabox() {
        $post_type = get_option('mdd_drink_post_type', 'drink');
        if (!post_type_exists($post_type)) return;

        add_meta_box(
            'mdd_ai_assistant',
            '🤖 Assistente IA — Preencher Automaticamente',
            [$this, 'render_metabox'],
            $post_type,
            'side',
            'high'
        );
    }

    /**
     * Render metabox content
     */
    public function render_metabox($post) {
        ?>
        <div id="mdd-ai-box">
            <p style="font-size:12px;color:#777;margin:0 0 10px">
                Clique para buscar dados deste drink automaticamente. O sistema verifica o cache central e, se necessário, consulta a IA.
            </p>
            <button type="button" id="mdd-ai-fill-btn" class="button button-primary" style="width:100%;text-align:center;padding:8px;font-size:13px" onclick="mddAiFill()">
                🤖 Preencher com IA
            </button>
            <div id="mdd-ai-status" style="margin-top:8px;font-size:12px;display:none"></div>
            <div id="mdd-ai-result" style="margin-top:10px;display:none">
                <div style="font-size:11px;font-weight:600;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px;color:#888">Dados retornados:</div>
                <div id="mdd-ai-fields" style="font-size:12px;line-height:1.8;max-height:300px;overflow-y:auto"></div>
                <button type="button" class="button" style="width:100%;margin-top:8px" onclick="mddAiApply()">✅ Aplicar aos campos</button>
            </div>
        </div>
        <script>
        var mddAiData = null;
        function mddAiFill() {
            var title = document.getElementById('title') ? document.getElementById('title').value : '';
            if (!title) { alert('Preencha o título do drink primeiro.'); return; }

            var btn = document.getElementById('mdd-ai-fill-btn');
            var status = document.getElementById('mdd-ai-status');
            btn.disabled = true;
            btn.textContent = '⏳ Consultando...';
            status.style.display = 'block';
            status.innerHTML = '<span style="color:#888">Verificando cache central...</span>';

            jQuery.post(ajaxurl, {
                action: 'mdd_ai_fill_product',
                nonce: '<?php echo wp_create_nonce('mdd_ai_nonce'); ?>',
                product_name: title,
                product_type: 'drink'
            }, function(res) {
                btn.disabled = false;
                btn.textContent = '🤖 Preencher com IA';
                if (res.success && res.data.data) {
                    mddAiData = res.data.data;
                    var src = res.data.source === 'cache' ? '📦 Cache (custo zero)' : '🤖 IA (processado agora)';
                    status.innerHTML = '<span style="color:#22c55e">✅ Dados encontrados! Fonte: ' + src + '</span>';
                    mddAiShowResult(mddAiData);
                } else {
                    status.innerHTML = '<span style="color:#e03c3c">❌ ' + (res.data && res.data.message ? res.data.message : 'Erro ao consultar.') + '</span>';
                }
            }).fail(function() {
                btn.disabled = false;
                btn.textContent = '🤖 Preencher com IA';
                status.innerHTML = '<span style="color:#e03c3c">❌ Erro de conexão.</span>';
            });
        }

        function mddAiShowResult(data) {
            var fields = document.getElementById('mdd-ai-fields');
            var html = '';
            var display = {
                'short_description': 'Descrição curta',
                'description': 'Descrição',
                'has_alcohol': 'Com álcool',
                'alcohol_percentage': 'Teor alcoólico',
                'strength': 'Força',
                'flavor_profile': 'Perfil de sabor',
                'base_spirit': 'Base',
                'category': 'Categoria',
                'ingredients': 'Ingredientes',
                'glass_type': 'Copo',
                'garnish': 'Guarnição',
                'preparation': 'Preparo',
                'tags': 'Tags de perfil',
                'pairing_foods': 'Harmoniza com'
            };
            for (var key in display) {
                if (data[key] !== undefined && data[key] !== null) {
                    var val = Array.isArray(data[key]) ? data[key].join(', ') : String(data[key]);
                    if (val === 'true') val = 'Sim';
                    if (val === 'false') val = 'Não';
                    html += '<div><strong>' + display[key] + ':</strong> ' + val + '</div>';
                }
            }
            fields.innerHTML = html;
            document.getElementById('mdd-ai-result').style.display = 'block';
        }

        function mddAiApply() {
            if (!mddAiData) return;

            // Try to fill common fields
            var fieldMap = <?php echo wp_json_encode(MDD_Settings::get_field_map()); ?>;

            // Short description
            var descField = fieldMap.short_desc || '_mdd_short_description';
            var descEl = document.querySelector('[name="' + descField + '"], [name="_mdd_short_description"]');
            if (descEl && mddAiData.short_description) descEl.value = mddAiData.short_description;

            // Ingredients
            var ingField = fieldMap.ingredients || '';
            if (ingField) {
                var ingEl = document.querySelector('[name="' + ingField + '"]');
                if (ingEl && mddAiData.ingredients) ingEl.value = mddAiData.ingredients.join(', ');
            }

            // Content (WordPress editor)
            if (mddAiData.description) {
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    tinymce.get('content').setContent(mddAiData.description);
                } else {
                    var contentEl = document.getElementById('content');
                    if (contentEl) contentEl.value = mddAiData.description;
                }
            }

            document.getElementById('mdd-ai-status').innerHTML = '<span style="color:#22c55e">✅ Campos preenchidos! Revise e salve o post.</span>';
            alert('Campos preenchidos! Revise os dados e clique "Publicar" quando estiver satisfeito.');
        }
        </script>
        <?php
    }

    /**
     * Get server API URL
     */
    private static function get_server_url($endpoint) {
        $server = get_option('mdd_license_server', 'https://a3tecnologias.com');
        return trailingslashit($server) . 'wp-json/mdd-license/v1' . $endpoint;
    }

    /**
     * Call server API
     */
    private static function call_server($endpoint, $body = []) {
        $license_key = get_option('mdd_license_key', '');
        $body['license_key'] = $license_key;

        $response = wp_remote_post(self::get_server_url($endpoint), [
            'timeout' => 60,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'Erro de conexão: ' . $response->get_error_message()];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $data ?: ['success' => false, 'message' => 'Resposta inválida do servidor.'];
    }

    /**
     * AJAX: Fill single product
     */
    public function ajax_fill_product() {
        check_ajax_referer('mdd_ai_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Sem permissão.');

        $name = sanitize_text_field($_POST['product_name'] ?? '');
        $type = sanitize_text_field($_POST['product_type'] ?? 'drink');
        if (empty($name)) wp_send_json_error(['message' => 'Nome obrigatório.']);

        // Check DEV MODE — skip server call
        if (defined('MDD_DEV_MODE') && MDD_DEV_MODE) {
            wp_send_json_error(['message' => 'Em DEV_MODE: configure o servidor de licenças para usar o Assistente IA.']);
            return;
        }

        $result = self::call_server(self::API_ENDPOINT, [
            'product_name' => $name,
            'product_type' => $type,
        ]);

        if (!empty($result['success'])) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Batch check (which products are cached)
     */
    public function ajax_batch_check() {
        check_ajax_referer('mdd_ai_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Sem permissão.');

        $products_raw = sanitize_textarea_field($_POST['products'] ?? '');
        $type = sanitize_text_field($_POST['product_type'] ?? 'drink');

        $names = array_filter(array_map('trim', explode("\n", $products_raw)));
        if (empty($names)) wp_send_json_error(['message' => 'Lista de produtos vazia.']);

        $result = self::call_server(self::BATCH_ENDPOINT, [
            'products'     => $names,
            'product_type' => $type,
        ]);

        if (!empty($result['success'])) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Fill a single missing product (from batch flow)
     */
    public function ajax_fill_single() {
        check_ajax_referer('mdd_ai_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Sem permissão.');

        $name = sanitize_text_field($_POST['product_name'] ?? '');
        $type = sanitize_text_field($_POST['product_type'] ?? 'drink');
        if (empty($name)) wp_send_json_error(['message' => 'Nome obrigatório.']);

        $result = self::call_server(self::API_ENDPOINT, [
            'product_name' => $name,
            'product_type' => $type,
        ]);

        if (!empty($result['success'])) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Import selected products as posts
     */
    public function ajax_import_products() {
        check_ajax_referer('mdd_ai_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error('Sem permissão.');

        $products = json_decode(stripslashes($_POST['products'] ?? '[]'), true);
        $post_type = get_option('mdd_drink_post_type', 'drink');

        if (empty($products) || !is_array($products)) {
            wp_send_json_error(['message' => 'Nenhum produto para importar.']);
        }

        $imported = 0;
        $field_map = MDD_Settings::get_field_map();

        foreach ($products as $p) {
            $name = sanitize_text_field($p['name'] ?? '');
            if (empty($name)) continue;

            // Check if drink already exists
            $existing = get_page_by_title($name, OBJECT, $post_type);
            if ($existing) continue;

            $post_id = wp_insert_post([
                'post_title'  => $name,
                'post_type'   => $post_type,
                'post_status' => 'draft',
                'post_content'=> sanitize_textarea_field($p['description'] ?? ''),
            ]);

            if (is_wp_error($post_id)) continue;

            // Fill meta fields
            $meta_map = [
                'short_description' => $field_map['short_desc'] ?: '_mdd_short_description',
                'ingredients'       => $field_map['ingredients'] ?: '',
            ];

            if (!empty($p['short_description']) && !empty($meta_map['short_description'])) {
                update_post_meta($post_id, $meta_map['short_description'], sanitize_text_field($p['short_description']));
            }
            if (!empty($p['ingredients']) && !empty($meta_map['ingredients'])) {
                $ing = is_array($p['ingredients']) ? implode(', ', $p['ingredients']) : $p['ingredients'];
                update_post_meta($post_id, $meta_map['ingredients'], sanitize_text_field($ing));
            }

            // Auto-tag with profile tags
            if (!empty($p['tags']) && is_array($p['tags'])) {
                foreach ($p['tags'] as $tag_slug) {
                    $slug = sanitize_title($tag_slug);
                    if (!term_exists($slug, 'mdd_drink_profile')) {
                        wp_insert_term(ucfirst(str_replace('-', ' ', $slug)), 'mdd_drink_profile', ['slug' => $slug]);
                    }
                }
                wp_set_object_terms($post_id, array_map('sanitize_title', $p['tags']), 'mdd_drink_profile');
            }

            $imported++;
        }

        wp_send_json_success([
            'imported' => $imported,
            'total'    => count($products),
        ]);
    }
}
