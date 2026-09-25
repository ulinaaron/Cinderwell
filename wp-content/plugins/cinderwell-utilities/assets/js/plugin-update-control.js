document.addEventListener('DOMContentLoaded', function () {
    var config = window.cinderwellPluginUpdateControl || {};
    var locked = Array.isArray(config.lockedPlugins) ? config.lockedPlugins : [];

    locked.forEach(function (plugin) {
        document.querySelectorAll('input[name="checked[]"]').forEach(function (checkbox) {
            if (checkbox.value !== plugin) {
                return;
            }

            checkbox.checked = false;
            checkbox.disabled = true;

            var row = checkbox.closest('tr');
            var title = row ? row.querySelector('.plugin-title p') : null;
            if (title && !title.querySelector('.cinderwell-plugin-lock-badge')) {
                var badge = document.createElement('span');
                badge.className = 'cinderwell-plugin-lock-badge';
                badge.innerHTML = '<span class="dashicons dashicons-lock" aria-hidden="true"></span>' + (config.label || 'Updates locked');
                title.appendChild(document.createTextNode(' '));
                title.appendChild(badge);
            }
        });
    });
});
