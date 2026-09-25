document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var config = window.cinderwellMail || {};
    var body = document.querySelector('[data-mail-log-body]');
    var pagination = document.querySelector('[data-mail-pagination]');
    var filters = document.querySelector('[data-mail-filters]');
    var recipient = document.getElementById('cinderwell-mail-recipient');
    var result = document.querySelector('[data-mail-test-result]');
    var currentPage = 1;

    function request(path, options) {
        options = options || {};
        options.headers = Object.assign({ 'X-WP-Nonce': config.nonce }, options.headers || {});
        return fetch(config.restUrl + path, options).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) {
                    var message = data.message || data.error_message || (config.strings && config.strings.requestError) || 'Request failed.';
                    throw new Error(message);
                }
                return data;
            });
        });
    }

    function makeCell(text) {
        var cell = document.createElement('td');
        cell.textContent = text || '—';
        return cell;
    }

    function renderLog(data) {
        body.textContent = '';
        if (!data.items || !data.items.length) {
            var emptyRow = document.createElement('tr');
            var emptyCell = makeCell((config.strings && config.strings.empty) || 'No mail has been logged yet.');
            emptyCell.colSpan = 6;
            emptyRow.appendChild(emptyCell);
            body.appendChild(emptyRow);
        } else {
            data.items.forEach(function (item) {
                var row = document.createElement('tr');
                var statusCell = document.createElement('td');
                var badge = document.createElement('span');
                badge.className = 'cinderwell-mail-status cinderwell-mail-status--' + item.status;
                badge.textContent = item.status.charAt(0).toUpperCase() + item.status.slice(1);
                statusCell.appendChild(badge);

                var responseCell = document.createElement('td');
                if (item.response_code) {
                    var code = document.createElement('strong');
                    code.textContent = 'HTTP ' + item.response_code;
                    responseCell.appendChild(code);
                }
                if (item.message_id) {
                    var id = document.createElement('code');
                    id.textContent = item.message_id;
                    responseCell.appendChild(id);
                }
                if (item.error_message) {
                    var details = document.createElement('details');
                    var summary = document.createElement('summary');
                    summary.textContent = item.error_code || 'Error details';
                    var message = document.createElement('p');
                    message.textContent = item.error_message;
                    details.appendChild(summary);
                    details.appendChild(message);
                    responseCell.appendChild(details);
                }
                if (!responseCell.childNodes.length) {
                    responseCell.textContent = '—';
                }

                row.appendChild(makeCell(item.created_at + ' UTC'));
                row.appendChild(statusCell);
                row.appendChild(makeCell((item.to_addresses || []).join(', ')));
                row.appendChild(makeCell(item.subject));
                row.appendChild(makeCell(item.source));
                row.appendChild(responseCell);
                body.appendChild(row);
            });
        }

        pagination.textContent = '';
        if (data.total_pages > 1) {
            var previous = document.createElement('button');
            previous.type = 'button';
            previous.className = 'button';
            previous.textContent = 'Previous';
            previous.disabled = data.page <= 1;
            previous.addEventListener('click', function () { loadLog(data.page - 1); });
            var label = document.createElement('span');
            label.textContent = 'Page ' + data.page + ' of ' + data.total_pages;
            var next = document.createElement('button');
            next.type = 'button';
            next.className = 'button';
            next.textContent = 'Next';
            next.disabled = data.page >= data.total_pages;
            next.addEventListener('click', function () { loadLog(data.page + 1); });
            pagination.appendChild(previous);
            pagination.appendChild(label);
            pagination.appendChild(next);
        }
    }

    function loadLog(page) {
        if (!body) return;
        currentPage = page || 1;
        var search = document.getElementById('cinderwell-mail-search');
        var status = document.getElementById('cinderwell-mail-status');
        var params = new URLSearchParams({
            page: currentPage,
            per_page: 20,
            search: search ? search.value : '',
            status: status ? status.value : ''
        });
        request('log?' + params.toString()).then(renderLog).catch(function (error) {
            body.textContent = '';
            var row = document.createElement('tr');
            var cell = makeCell(error.message);
            cell.colSpan = 6;
            row.appendChild(cell);
            body.appendChild(row);
        });
    }

    document.querySelectorAll('[data-mail-test]').forEach(function (button) {
        button.addEventListener('click', function () {
            var email = recipient ? recipient.value.trim() : '';
            if (!email) {
                result.textContent = 'Enter a valid test recipient.';
                result.className = 'cinderwell-mail-result is-error';
                return;
            }
            document.querySelectorAll('[data-mail-test]').forEach(function (item) { item.disabled = true; });
            result.textContent = button.dataset.mailTest === 'send' ? 'Sending test email…' : 'Validating configuration…';
            result.className = 'cinderwell-mail-result is-pending';
            request('test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mode: button.dataset.mailTest, recipient: email })
            }).then(function (data) {
                result.textContent = data.status === 'validated' ? 'Configuration validated. No email was sent.' : 'Test accepted by SendGrid.';
                result.className = 'cinderwell-mail-result is-success';
                loadLog(1);
            }).catch(function (error) {
                result.textContent = error.message;
                result.className = 'cinderwell-mail-result is-error';
                loadLog(1);
            }).finally(function () {
                document.querySelectorAll('[data-mail-test]').forEach(function (item) { item.disabled = false; });
            });
        });
    });

    if (filters) {
        filters.addEventListener('submit', function (event) {
            event.preventDefault();
            loadLog(1);
        });
    }

    var clear = document.querySelector('[data-mail-clear]');
    if (clear) {
        clear.addEventListener('click', function () {
            if (!window.confirm((config.strings && config.strings.confirmClear) || 'Clear the mail log?')) return;
            clear.disabled = true;
            request('log', { method: 'DELETE' }).then(function () {
                loadLog(1);
            }).catch(function (error) {
                window.alert(error.message);
            }).finally(function () {
                clear.disabled = false;
            });
        });
    }

    loadLog(1);
});
