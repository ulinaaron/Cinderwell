/**
 * Frontend authentication handlers for Cinderwell Portal blocks.
 */

document.addEventListener('DOMContentLoaded', function () {
    const ajaxUrl = (typeof cinderwell_portal !== 'undefined') ? cinderwell_portal.ajax_url : '';

    function postAjax(action, data) {
        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
        }).then(function (r) { return r.json(); });
    }

    function showMessage(el, text, type) {
        if (!el) return;
        el.textContent = text;
        el.className = 'cinderwell-portal-message is-' + type;
    }

    // Login form
    document.querySelectorAll('.cinderwell-portal-login').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            var msg = form.querySelector('.cinderwell-portal-message');
            var data = {
                action: 'cinderwell_portal_login',
                nonce: fd.get('nonce'),
                email: fd.get('email'),
                password: fd.get('password'),
                remember: fd.get('remember') ? '1' : '',
            };
            postAjax('cinderwell_portal_login', data).then(function (result) {
                if (result.success) {
                    showMessage(msg, 'Logging in...', 'success');
                    window.location.href = result.data.redirect || '/';
                } else {
                    if (result.data && result.data.redirect) {
                        window.location.href = result.data.redirect;
                        return;
                    }
                    showMessage(msg, (result.data && result.data.message) || 'Login failed.', 'error');
                }
            }).catch(function () {
                showMessage(msg, 'Network error. Please try again.', 'error');
            });
        });
    });

    // Register form
    document.querySelectorAll('.cinderwell-portal-register').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            var msg = form.querySelector('.cinderwell-portal-message');
            var data = {
                action: 'cinderwell_portal_register',
                nonce: fd.get('nonce'),
                email: fd.get('email'),
                first_name: fd.get('first_name'),
                last_name: fd.get('last_name'),
                terms: fd.get('terms') ? '1' : '',
                privacy: fd.get('privacy') ? '1' : '',
            };
            postAjax('cinderwell_portal_register', data).then(function (result) {
                if (result.success) {
                    showMessage(msg, result.data.message, 'success');
                    form.reset();
                } else {
                    showMessage(msg, (result.data && result.data.message) || 'Registration failed.', 'error');
                }
            }).catch(function () {
                showMessage(msg, 'Network error. Please try again.', 'error');
            });
        });
    });

    // Lost password toggle
    document.querySelectorAll('.cinderwell-portal-lost-password-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var loginForm = link.closest('.cinderwell-portal-login');
            if (loginForm) loginForm.style.display = 'none';
            var lostForm = document.querySelector('.cinderwell-portal-lost-password-form');
            if (lostForm) lostForm.style.display = 'block';
        });
    });

    document.querySelectorAll('.cinderwell-portal-back-to-login').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var lostWrap = document.querySelector('.cinderwell-portal-lost-password-form');
            if (lostWrap) lostWrap.style.display = 'none';
            var loginForm = document.querySelector('.cinderwell-portal-login');
            if (loginForm) loginForm.style.display = '';
        });
    });

    // Lost password form submit
    document.querySelectorAll('.cinderwell-portal-lost-pw-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            var msg = form.querySelector('.cinderwell-portal-message');
            var data = {
                action: 'cinderwell_portal_lost_password',
                nonce: fd.get('nonce'),
                email: fd.get('email'),
            };
            postAjax('cinderwell_portal_lost_password', data).then(function (result) {
                showMessage(msg, (result.data && result.data.message) || 'Done.', 'success');
            }).catch(function () {
                showMessage(msg, 'Network error.', 'error');
            });
        });
    });

    // Profile update
    var profileForm = document.querySelector('.cinderwell-portal-profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(profileForm);
            var msg = profileForm.querySelector('.cinderwell-portal-message');
            var data = {
                action: 'cinderwell_portal_profile_update',
                nonce: fd.get('nonce'),
                email: fd.get('email'),
                first_name: fd.get('first_name'),
                last_name: fd.get('last_name'),
            };
            postAjax('cinderwell_portal_profile_update', data).then(function (result) {
                if (result.success) {
                    showMessage(msg, 'Profile updated.', 'success');
                } else {
                    showMessage(msg, (result.data && result.data.message) || 'Update failed.', 'error');
                }
            }).catch(function () {
                showMessage(msg, 'Network error.', 'error');
            });
        });
    }

    // Change password
    var pwForm = document.querySelector('.cinderwell-portal-change-password');
    if (pwForm) {
        pwForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(pwForm);
            var msg = pwForm.querySelector('.cinderwell-portal-message');
            var newPw = fd.get('new_password');
            if (newPw && newPw.length < 8) {
                showMessage(msg, 'Password must be at least 8 characters.', 'error');
                return;
            }
            var data = {
                action: 'cinderwell_portal_change_password',
                nonce: fd.get('nonce'),
                current_password: fd.get('current_password'),
                new_password: newPw,
            };
            postAjax('cinderwell_portal_change_password', data).then(function (result) {
                if (result.success) {
                    showMessage(msg, 'Password changed.', 'success');
                    pwForm.reset();
                } else {
                    showMessage(msg, (result.data && result.data.message) || 'Failed.', 'error');
                }
            }).catch(function () {
                showMessage(msg, 'Network error.', 'error');
            });
        });
    }
});
