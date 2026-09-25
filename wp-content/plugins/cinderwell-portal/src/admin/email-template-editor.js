/**
 * Email template test-send buttons.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cinderwell-send-test').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var template = btn.dataset.template;
            var statusEl = document.querySelector('.cinderwell-test-status[data-template="' + template + '"]');

            btn.disabled = true;
            btn.textContent = 'Sending...';
            if (statusEl) statusEl.textContent = '';

            var fd = new URLSearchParams({
                action: 'cinderwell_portal_admin_send_test_email',
                nonce: cinderwell_portal_admin.nonce,
                template: template,
            });

            fetch(cinderwell_portal_admin.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: fd,
            })
            .then(function (r) { return r.json(); })
            .then(function (result) {
                var msg = (result.data && result.data.message) || 'Done';
                if (statusEl) {
                    statusEl.textContent = msg;
                    statusEl.style.color = result.success ? '#065f46' : '#991b1b';
                } else {
                    alert(msg);
                }
            })
            .catch(function () {
                if (statusEl) {
                    statusEl.textContent = 'Network error';
                    statusEl.style.color = '#991b1b';
                }
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = 'Send test to ' + (cinderwell_portal_admin.email || 'admin');
            });
        });
    });
});
