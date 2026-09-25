document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.cinderwell-utilities-form');
    var enabledCount = document.querySelector('[data-cinderwell-enabled-count]');
    var saveStatus = document.querySelector('[data-cinderwell-save-status]');

    function refreshCounts() {
        var enabled = document.querySelectorAll('.cinderwell-utilities-module-toggle:checked').length;
        if (enabledCount) {
            enabledCount.textContent = enabled;
        }

        document.querySelectorAll('[data-cinderwell-group-count]').forEach(function (count) {
            var group = count.dataset.group;
            var modules = document.querySelectorAll('.cinderwell-utilities-module[data-group="' + group + '"]');
            var active = document.querySelectorAll('.cinderwell-utilities-module[data-group="' + group + '"] .cinderwell-utilities-module-toggle:checked');
            count.textContent = active.length + ' of ' + modules.length + ' enabled';
        });
    }

    function markDirty() {
        if (!form) {
            return;
        }
        form.classList.add('has-unsaved-changes');
        if (saveStatus) {
            saveStatus.textContent = 'Unsaved changes';
        }
    }

    document.querySelectorAll('.cinderwell-utilities-module-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            var module = toggle.closest('.cinderwell-utilities-module');
            var body = module.querySelector('.cinderwell-utilities-module__body');
            var status = module.querySelector('[data-cinderwell-module-status]');

            module.classList.toggle('is-enabled', toggle.checked);
            toggle.setAttribute('aria-expanded', toggle.checked ? 'true' : 'false');
            if (body) {
                body.hidden = !toggle.checked;
            }
            if (status) {
                status.textContent = toggle.checked ? 'Enabled' : 'Disabled';
            }

            refreshCounts();
            markDirty();
        });
    });

    if (form) {
        form.addEventListener('change', function (event) {
            if (!event.target.classList.contains('cinderwell-utilities-module-toggle')) {
                markDirty();
            }
        });
    }
});
