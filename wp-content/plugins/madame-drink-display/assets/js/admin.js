jQuery(function($) {

    // ─── Radio Button Visual Feedback (layouts) ───
    $(document).on('change', 'input[type="radio"]', function() {
        var name = $(this).attr('name');
        $('input[type="radio"][name="' + name + '"]').each(function() {
            var label = $(this).closest('label');
            if (label.length) {
                if ($(this).is(':checked')) {
                    label.css('border-color', 'var(--mdd-accent)').css('box-shadow', '0 0 0 2px rgba(249,115,22,.15)');
                } else {
                    label.css('border-color', 'var(--mdd-border)').css('box-shadow', 'none');
                }
            }
        });
    });

    // ─── Slide Day Checkbox Toggle Visual ───
    $(document).on('change', 'input[name*="mdd_slide_days"]', function() {
        var label = $(this).closest('label');
        if ($(this).is(':checked')) {
            label.css({'background': 'rgba(249,115,22,.15)', 'border-color': 'var(--mdd-accent)'});
        } else {
            label.css({'background': 'rgba(255,255,255,.04)', 'border-color': 'var(--mdd-border)'});
        }
    });

    // ─── Color Picker ───
    $('.mdd-color-picker').wpColorPicker();

    // ─── Media Upload (unified: logos save ID, others save URL) ───
    $(document).on('click', '.mdd-upload-btn', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var saveUrl = $(this).data('save-url'); // if set, save URL instead of ID
        var frame = wp.media({
            title: mddAdmin.strings.selectImage,
            button: { text: mddAdmin.strings.useImage },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            // FN11: Validate logo file size (500KB max for logo fields)
            if (!saveUrl && attachment.filesizeInBytes && attachment.filesizeInBytes > 512000) {
                var sizeKB = Math.round(attachment.filesizeInBytes / 1024);
                alert('⚠️ Arquivo muito grande (' + sizeKB + 'KB). O logo deve ter no máximo 500KB.\n\nDica: Use PNG com fundo transparente e dimensões 400×150px.');
                return;
            }
            var val = saveUrl ? attachment.url : attachment.id;
            $('#' + target).val(val);
            $('#' + target + '_preview').html('<img src="' + attachment.url + '" alt="Preview">');
            $('.mdd-remove-btn[data-target="' + target + '"]').show();
        });

        frame.open();
    });

    $(document).on('click', '.mdd-remove-btn', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        $('#' + target).val('');
        $('#' + target + '_preview').html(
            '<span class="dashicons dashicons-format-image" style="font-size:28px;color:#444"></span>'
        );
        $(this).hide();
    });

    // ─── Event Mode Toggle ───
    $('#mdd_event_mode_toggle').on('change', function() {
        $('#mdd_event_fields').toggle(this.checked);
    });

    // ─── Create Token ───
    $('#mdd_create_token_btn').on('click', function() {
        var name = $('#mdd_new_device_name').val().trim();
        var type = $('#mdd_new_device_type').val();
        var logo = $('#mdd_new_logo_override').val().trim();
        var categoryFilter = $('#mdd_new_category_filter').val() || '';
        var layoutOverride = $('#mdd_new_layout_override').val() || '';
        var cptFilter = ($('#mdd_new_cpt_filter').val() || []).join(',');

        if (!name) {
            alert('Informe o nome do dispositivo.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('Criando...');

        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_create_token',
            nonce: mddAdmin.nonce,
            device_name: name,
            device_type: type,
            logo_override: logo,
            cpt_filter: cptFilter,
            category_filter: categoryFilter,
            layout_override: layoutOverride
        }, function(res) {
            btn.prop('disabled', false).text('Gerar Token');
            if (res.success) {
                alert(mddAdmin.strings.tokenCreated + '\n\nURL: ' + res.data.url);
                location.reload();
            } else {
                alert(res.data || 'Erro ao criar token.');
            }
        }).fail(function() {
            btn.prop('disabled', false).text('Gerar Token');
            alert('Erro de conexão.');
        });
    });

    // ─── Revoke Token ───
    $(document).on('click', '.mdd-revoke-btn', function() {
        if (!confirm(mddAdmin.strings.confirmRevoke)) return;
        var id = $(this).data('id');
        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_revoke_token',
            nonce: mddAdmin.nonce,
            token_id: id
        }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ─── Reactivate Token ───
    $(document).on('click', '.mdd-reactivate-btn', function() {
        var id = $(this).data('id');
        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_reactivate_token',
            nonce: mddAdmin.nonce,
            token_id: id
        }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ─── Delete Token ───
    $(document).on('click', '.mdd-delete-btn', function() {
        if (!confirm(mddAdmin.strings.confirmDelete)) return;
        var id = $(this).data('id');
        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_delete_token',
            nonce: mddAdmin.nonce,
            token_id: id
        }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ─── Edit Token ───
    $(document).on('click', '.mdd-edit-token-btn', function() {
        var btn = $(this);
        var id = btn.data('id');
        var name = prompt('Nome do dispositivo:', btn.data('name'));
        if (name === null) return;
        var cat = prompt('Filtro categoria (vazio = todas):', btn.data('cat') || '');
        if (cat === null) return;
        var layout = prompt('Layout override (vazio/fullscreen/split/grid):', btn.data('layout') || '');
        if (layout === null) return;

        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_edit_token',
            nonce: mddAdmin.nonce,
            token_id: id,
            device_name: name,
            category_filter: cat,
            layout_override: layout
        }, function(res) {
            if (res.success) location.reload();
            else alert('Erro ao salvar.');
        });
    });

    // ─── Reset Stats ───
    $(document).on('click', '#mdd_reset_stats_btn', function() {
        var period = $('#mdd_reset_period').val();
        var label = period === 'all' ? 'TODOS os dados' : 'dados com mais de ' + period + ' dias';
        if (!confirm('⚠️ Tem certeza que deseja apagar ' + label + '?\n\nEssa ação NÃO pode ser desfeita.')) return;

        var btn = $(this);
        btn.prop('disabled', true).text('Resetando...');
        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_reset_stats',
            nonce: mddAdmin.nonce,
            period: period
        }, function(res) {
            if (res.success) location.reload();
            else { btn.prop('disabled', false).text('Resetar'); alert('Erro.'); }
        });
    });

    // ─── Copy URL on click ───
    $(document).on('click', '.mdd-url-field', function() {
        this.select();
        document.execCommand('copy');
    });

    // ─── License: Activate ───
    $(document).on('click', '#mdd-license-activate', function() {
        var btn = $(this);
        var key = $('#mdd-license-key').val().trim();
        var msg = $('#mdd-license-msg');

        if (!key) {
            msg.html('<span style="color:var(--mdd-danger)">Informe a chave de licença.</span>');
            return;
        }

        btn.prop('disabled', true).text('Ativando...');
        msg.html('<span style="color:var(--mdd-muted)">Conectando ao servidor...</span>');

        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_license_activate',
            nonce: mddAdmin.nonce,
            license_key: key
        }, function(res) {
            if (res.success) {
                msg.html('<span style="color:var(--mdd-success)">✅ ' + (res.data.message || 'Licença ativada!') + '</span>');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                msg.html('<span style="color:var(--mdd-danger)">❌ ' + (res.data || 'Erro ao ativar.') + '</span>');
                btn.prop('disabled', false).text('Ativar Licença');
            }
        }).fail(function() {
            msg.html('<span style="color:var(--mdd-danger)">Erro de conexão. Tente novamente.</span>');
            btn.prop('disabled', false).text('Ativar Licença');
        });
    });

    // ─── License: Deactivate ───
    $(document).on('click', '#mdd-license-deactivate', function() {
        if (!confirm('Desativar a licença? Os displays (TV, Tablet, Quiz) ficarão offline.')) return;

        var btn = $(this);
        var msg = $('#mdd-license-msg');
        btn.prop('disabled', true).text('Desativando...');

        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_license_deactivate',
            nonce: mddAdmin.nonce
        }, function(res) {
            msg.html('<span style="color:var(--mdd-muted)">Licença desativada.</span>');
            setTimeout(function() { location.reload(); }, 1500);
        }).fail(function() {
            msg.html('<span style="color:var(--mdd-danger)">Erro de conexão.</span>');
            btn.prop('disabled', false).text('Desativar Licença');
        });
    });

    // ─── License: Check Now ───
    $(document).on('click', '#mdd-license-check', function() {
        var btn = $(this);
        btn.prop('disabled', true).find('.dashicons').addClass('spin');

        $.post(mddAdmin.ajaxUrl, {
            action: 'mdd_license_check',
            nonce: mddAdmin.nonce
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || 'Erro na verificação.');
                btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            }
        }).fail(function() {
            alert('Erro de conexão com o servidor.');
            btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        });
    });

    // Enter key on license input triggers activate
    $(document).on('keypress', '#mdd-license-key', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#mdd-license-activate').click();
        }
    });
});
