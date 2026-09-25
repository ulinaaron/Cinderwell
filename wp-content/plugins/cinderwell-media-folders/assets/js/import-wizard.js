(function ($) {
    'use strict';

    $(document).ready(function () {
        var $result = $('#cinderwell-mf-import-result');
        var cfg = typeof cinderwellMFImport !== 'undefined' ? cinderwellMFImport : null;
        if (!cfg) return;

        $(document).on('click', '[data-cwmf-dry-run]', function () {
            runImport($(this).data('cwmf-dry-run'), true);
        });

        $(document).on('click', '[data-cwmf-import]', function () {
            var source = $(this).data('cwmf-import');
            if (!confirm('Import folders from ' + source + '? Existing matching folders will be reused.')) return;
            runImport(source, false);
        });

        function runImport(source, dryRun) {
            var $buttons = $('[data-cwmf-dry-run], [data-cwmf-import]');
            $buttons.prop('disabled', true);
            $result.html('<p><span class="spinner is-active"></span> ' + (dryRun ? 'Building import preview…' : 'Importing folders and media…') + '</p>');
            $.post(cfg.ajaxUrl, {
                action: 'cinderwell_media_folders_import_' + source,
                nonce: cfg.nonce,
                dry_run: dryRun ? '1' : '0',
            }).done(function (resp) {
                if (!resp.success) {
                    $result.html('<div class="notice notice-error inline"><p>' + escapeHtml((resp.data && resp.data.message) || 'Import failed.') + '</p></div>');
                    return;
                }
                if (dryRun) {
                    var preview = resp.data.preview || [];
                    var html = '<h3>Import preview</h3><p>Nothing has been changed yet.</p><table class="widefat striped"><thead><tr><th>Folder</th><th>Media</th><th>Result</th></tr></thead><tbody>';
                    for (var i = 0; i < preview.length; i++) {
                        var r = preview[i];
                        html += '<tr><td>' + $('<span>').text(r.source_name).html() + '</td><td>' + r.attachments + '</td><td>' + (r.will_skip ? 'Reused' : 'New folder') + '</td></tr>';
                    }
                    html += '</tbody></table>';
                    $result.html(html);
                } else {
                    var s = resp.data;
                    $result.html('<div class="notice notice-success inline"><p><strong>Import complete.</strong></p></div><ul><li>Folders created: ' + Number(s.created || 0) + '</li><li>Folders reused: ' + Number(s.reused || 0) + '</li><li>Media assigned: ' + Number(s.attachments_assigned || 0) + '</li><li>Existing assignments kept: ' + Number(s.attachments_reused || 0) + '</li><li>Errors: ' + Number(s.errors || 0) + '</li></ul>');
                }
            }).fail(function () {
                $result.html('<div class="notice notice-error inline"><p>Request failed.</p></div>');
            }).always(function () {
                $buttons.prop('disabled', false);
            });
        }

        function escapeHtml(value) {
            return $('<span>').text(String(value)).html();
        }
    });
})(jQuery);
