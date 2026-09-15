document.addEventListener('DOMContentLoaded', function () {
    // Toggle module body visibility when master toggle changes
    document.querySelectorAll('.cinderwell-utilities-module-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            var body = toggle.closest('.cinderwell-utilities-module').querySelector('.cinderwell-utilities-module__body');
            if (body) {
                body.style.display = toggle.checked ? '' : 'none';
            }
        });
    });
});
