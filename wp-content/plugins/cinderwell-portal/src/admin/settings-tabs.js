/**
 * Admin settings tab enhancements.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-cw-portal-access]').forEach(function (panel) {
        var toggle = panel.querySelector('[data-cw-portal-restricted]');
        var settings = panel.querySelector('[data-cw-portal-restricted-settings]');
        if (!toggle || !settings) return;

        var syncSettings = function () {
            settings.hidden = !toggle.checked;
        };

        toggle.addEventListener('change', syncSettings);
        syncSettings();
    });

    var adminForm = document.getElementById('cinderwell-admin-create-member');
    if (!adminForm) return;

    adminForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(adminForm);
        var msg = adminForm.querySelector('.cinderwell-admin-message');
        if (msg) msg.textContent = 'Creating...';

        fd.append('action', 'cinderwell_portal_admin_create_member');
        fd.append('nonce', cinderwell_portal_admin.nonce);

        fetch(cinderwell_portal_admin.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (result) {
            if (result.success) {
                if (msg) {
                    msg.textContent = result.data.message;
                    msg.style.color = '#065f46';
                }
                adminForm.reset();
            } else {
                if (msg) {
                    msg.textContent = (result.data && result.data.message) || 'Error';
                    msg.style.color = '#991b1b';
                }
            }
        })
        .catch(function () {
            if (msg) {
                msg.textContent = 'Network error';
                msg.style.color = '#991b1b';
            }
        });
    });
});
