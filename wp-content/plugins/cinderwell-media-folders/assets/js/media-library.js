(function ($) {
    'use strict';

    var CMF = {
        folders: [], current: 0, strings: {}, restUrl: '', nonce: '', uploadUrl: 'upload.php', multiFolder: false, canManage: false, createParent: 0, listMode: false,

        init: function () {
            var self = this;
            var data = typeof cinderwellMediaFolders !== 'undefined' ? cinderwellMediaFolders : null;
            if (!data && window.wp && wp.media && wp.media.view && wp.media.view.settings) data = wp.media.view.settings.cwmfFolders || null;
            if (!data) return;
            this.folders = data.folders || [];
            this.current = parseInt(data.currentFolder || 0, 10);
            this.strings = data.strings || {};
            this.restUrl = data.restUrl || '';
            this.nonce = data.nonce || '';
            this.uploadUrl = data.uploadUrl || 'upload.php';
            this.multiFolder = !!data.multiFolder;
            this.canManage = !!data.canManage;
            document.addEventListener('click', function (event) { self.handleModifiedGridClick(event); }, true);
            $(document).on('click.cinderwellMediaFolders', function (event) {
                self.closeFolderMenus();
                if (!$(event.target).closest('.cinderwell-mf-bulk-popover, .cinderwell-mf-list-bulk-trigger').length) self.closeBulkMovePopover();
            });
            $(document).on('click.cinderwellMediaFolders change.cinderwellMediaFolders', '.select-mode-toggle-button, .attachments .attachment, #the-list input[type="checkbox"]', function () {
                window.setTimeout(function () { self.updateBulkMoveState(); }, 0);
            });
            $(window).on('resize.cinderwellMediaFolders', function () { self.closeFolderMenus(); self.closeBulkMovePopover(); });
            if ($('.attachments-browser > .wp-filter').length && !$('.cinderwell-media-folders-sidebar').length) {
                this.renderGridSidebar();
                this.initGridDragDrop();
            } else if ($('#posts-filter .wp-list-table').length && !$('.cinderwell-media-folders-sidebar').length) {
                this.listMode = true;
                this.renderListSidebar();
                this.initGridDragDrop();
            }
            if (window.wp && wp.media) this.extendMediaModal();
        },

        handleModifiedGridClick: function (event) {
            if ((!event.ctrlKey && !event.metaKey) || event.button !== 0) return;
            var attachment = event.target.closest('.attachments-browser.cinderwell-mf-layout .attachment');
            if (!attachment) return;
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            if (!$('.media-frame').hasClass('mode-select')) $('.select-mode-toggle-button').trigger('click');
            window.setTimeout(function () { $(attachment).trigger('click'); }, 0);
        },

        request: function (path, method, data) {
            var self = this;
            return $.ajax({
                url: this.restUrl + path,
                method: method,
                beforeSend: function (xhr) { xhr.setRequestHeader('X-WP-Nonce', self.nonce); },
                data: data ? JSON.stringify(data) : undefined,
                contentType: data ? 'application/json' : undefined,
                dataType: 'json'
            });
        },

        renderGridSidebar: function () {
            var $filter = $('.attachments-browser > .wp-filter').first();
            var $browser = $filter.closest('.attachments-browser');
            if (!$browser.length) return;
            $browser.addClass('cinderwell-mf-layout');
            $filter.after(this.buildSidebar());
            this.renderBulkMoveControls($filter.find('.media-toolbar-secondary').first(), 'grid');
            this.renderChildFolders();
        },

        renderListSidebar: function () {
            var $form = $('#posts-filter');
            var $table = $form.children('.wp-list-table').first();
            var $topNav = $form.children('.tablenav.top').first();
            var $bottomNav = $form.children('.tablenav.bottom').first();
            if (!$table.length || !$topNav.length) return;

            var $layout = $('<div class="cinderwell-mf-list-layout"></div>');
            var $main = $('<div class="cinderwell-mf-list-main"></div>');
            $topNav.before($layout);
            $layout.append(this.buildSidebar()).append($main);
            $main.append($topNav).append($table);
            if ($bottomNav.length) $main.append($bottomNav);
            this.initListBulkMove($topNav, $bottomNav);
        },

        renderChildFolders: function () {
            $('.cinderwell-mf-child-folder').remove();
            if (this.current < 1) return;
            var currentFolder = this.findFolder(this.folders, this.current);
            var children = currentFolder && currentFolder.children ? currentFolder.children : [];
            var $attachments = $('.attachments-browser.cinderwell-mf-layout .attachments').first();
            if (!$attachments.length || !children.length) return;
            var self = this;
            var $folders = $();
            children.forEach(function (folder) {
                var href = self.uploadUrl + '?cwmf_folder=' + folder.id;
                var label = (self.strings.openFolder || 'Open folder') + ': ' + folder.name;
                var $item = $('<li class="cinderwell-mf-child-folder"></li>');
                var $link = $('<a class="cinderwell-mf-child-folder-link"></a>').attr({ href: href, 'aria-label': label });
                var $icon = $('<span class="cinderwell-mf-child-folder-icon"><span class="dashicons dashicons-category" aria-hidden="true"></span></span>');
                if (folder.color) $icon.css('--cwmf-folder-color', folder.color).addClass('has-color');
                $link.append($icon);
                $link.append($('<span class="cinderwell-mf-child-folder-name"></span>').text(folder.name));
                $link.append($('<span class="cinderwell-mf-child-folder-count"></span>').text(folder.count));
                $item.append($link);
                self.makeDropTarget($item, folder.id);
                $folders = $folders.add($item);
            });
            $attachments.prepend($folders);
        },

        initListBulkMove: function ($topNav, $bottomNav) {
            var self = this;
            $topNav.add($bottomNav).each(function () {
                var $nav = $(this);
                var $actions = $nav.find('select[name="action"], select[name="action2"]').first();
                var $apply = $nav.find('#doaction, #doaction2').first();
                if (!$actions.length || !$apply.length) return;
                if (!$actions.find('option[value="cinderwell_move_folder"]').length) {
                    $actions.append($('<option value="cinderwell_move_folder"></option>').text(self.strings.moveToFolder || 'Move to folder…'));
                }
                $apply.addClass('cinderwell-mf-list-bulk-trigger').on('click.cinderwellMediaFolders', function (event) {
                    if ($actions.val() !== 'cinderwell_move_folder') return;
                    event.preventDefault();
                    event.stopPropagation();
                    self.openListBulkMove(this, $actions);
                });
            });
            $('#posts-filter').on('submit.cinderwellMediaFolders', function (event) {
                var $actionSelect = $(this).find('select[name="action"], select[name="action2"]').filter(function () {
                    return $(this).val() === 'cinderwell_move_folder';
                }).first();
                if (!$actionSelect.length) return;
                event.preventDefault();
                var $trigger = $actionSelect.closest('.bulkactions').find('.button').first();
                self.openListBulkMove($trigger[0] || this, $actionSelect);
            });
        },

        openListBulkMove: function (trigger, $actionSelect) {
            var self = this;
            var ids = this.getBulkSelectedIds('list');
            this.closeBulkMovePopover();
            if (!ids.length) {
                this.setStatus(this.strings.selectMediaFirst || 'Select at least one media item first.', true);
                window.alert(this.strings.selectMediaFirst || 'Select at least one media item first.');
                return;
            }
            var popoverId = 'cinderwell-mf-bulk-popover';
            var selectId = 'cinderwell-mf-list-folder';
            var titleId = popoverId + '-title';
            var $popover = $('<div class="cinderwell-mf-bulk-popover" role="dialog"></div>').attr({ id: popoverId, 'aria-labelledby': titleId });
            var mediaLabel = ids.length === 1 ? (this.strings.mediaItem || 'media item') : (this.strings.mediaItems || 'media items');
            var $title = $('<strong class="cinderwell-mf-bulk-popover-title"></strong>').attr('id', titleId).text((this.strings.moveSelected || 'Move') + ' ' + ids.length + ' ' + mediaLabel);
            var $label = $('<label></label>').attr('for', selectId).text(this.strings.chooseDestination || 'Destination folder');
            var $select = $('<select></select>').attr('id', selectId);
            $select.append($('<option value=""></option>').text(this.strings.chooseFolder || 'Choose folder…'));
            $select.append($('<option value="-1"></option>').text(this.strings.uncategorized || 'Unfiled'));
            this.appendFolderOptions($select, this.folders, 0);
            var $actions = $('<div class="cinderwell-mf-bulk-popover-actions"></div>');
            var $move = $('<button type="button" class="button button-primary" disabled></button>').text(this.strings.moveSelected || 'Move');
            var $cancel = $('<button type="button" class="button"></button>').text(this.strings.cancel || 'Cancel');
            $select.on('change', function () { $move.prop('disabled', !$(this).val()); });
            $move.on('click', function () {
                var folderId = parseInt($select.val(), 10);
                if (isNaN(folderId)) return;
                $move.prop('disabled', true).text(self.strings.moving || 'Moving…');
                self.moveAttachments(ids, folderId, $move, true).done(function () {
                    $actionSelect.val('-1');
                    self.closeBulkMovePopover();
                    window.location.reload();
                }).fail(function () { $move.prop('disabled', false).text(self.strings.moveSelected || 'Move'); });
            });
            $cancel.on('click', function () { $actionSelect.val('-1'); self.closeBulkMovePopover(); });
            $popover.on('click', function (event) { event.stopPropagation(); });
            $popover.on('keydown', function (event) { if (event.key === 'Escape') { $actionSelect.val('-1'); self.closeBulkMovePopover(); $(trigger).trigger('focus'); } });
            $popover.append($title).append($label).append($select).append($actions.append($move).append($cancel));
            $('body').append($popover);
            this.positionPopover($popover, trigger);
            $select.trigger('focus');
        },

        closeBulkMovePopover: function () {
            var hadPopover = $('.cinderwell-mf-bulk-popover').length > 0;
            $('.cinderwell-mf-bulk-popover').remove();
            if (hadPopover) {
                $('select[name="action"], select[name="action2"]').filter(function () { return $(this).val() === 'cinderwell_move_folder'; }).val('-1');
            }
        },

        positionPopover: function ($popover, trigger) {
            var rect = trigger.getBoundingClientRect();
            var width = $popover.outerWidth();
            var height = $popover.outerHeight();
            var left = Math.max(8, Math.min(window.innerWidth - width - 8, rect.left));
            var top = rect.bottom + 6;
            if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 6);
            $popover.css({ left: left, top: top });
        },

        renderBulkMoveControls: function ($host, context) {
            var self = this;
            if (!$host.length || $('.cinderwell-mf-bulk-move[data-context="' + context + '"]').length) return;
            var $wrap = $('<div class="cinderwell-mf-bulk-move"></div>').attr('data-context', context);
            var selectId = 'cinderwell-mf-bulk-folder-' + context;
            var $label = $('<label class="screen-reader-text"></label>').attr('for', selectId).text(this.strings.moveSelectedTo || 'Move selected media to folder');
            var $select = $('<select class="cinderwell-mf-bulk-folder"></select>').attr('id', selectId);
            $select.append($('<option value=""></option>').text(this.strings.chooseFolder || 'Choose folder…'));
            $select.append($('<option value="-1"></option>').text(this.strings.uncategorized || 'Unfiled'));
            this.appendFolderOptions($select, this.folders, 0);
            var $button = $('<button type="button" class="button cinderwell-mf-bulk-move-button" disabled></button>').text(this.strings.moveSelected || 'Move');
            $select.on('change', function () { self.updateBulkMoveState(); });
            $button.on('click', function () { self.moveBulkSelection($wrap); });
            $wrap.append($label).append($select).append($button);
            if (context === 'grid') {
                var $spinner = $host.find('.spinner').first();
                if ($spinner.length) $spinner.before($wrap); else $host.append($wrap);
            } else {
                $host.after($wrap);
            }
            this.updateBulkMoveState();
        },

        getBulkSelectedIds: function (context) {
            var ids = [];
            if (context === 'list') {
                $('#the-list .check-column input[type="checkbox"]:checked').closest('tr').each(function () {
                    var id = parseInt(String(this.id || '').replace('post-', ''), 10);
                    if (id) ids.push(id);
                });
            } else {
                $('.attachments .attachment.selected').each(function () {
                    var id = parseInt($(this).attr('data-id') || $(this).data('id'), 10);
                    if (id) ids.push(id);
                });
            }
            return ids;
        },

        updateBulkMoveState: function () {
            var self = this;
            $('.cinderwell-mf-bulk-move').each(function () {
                var $wrap = $(this);
                var context = $wrap.attr('data-context');
                var count = self.getBulkSelectedIds(context).length;
                var hasFolder = $wrap.find('.cinderwell-mf-bulk-folder').val() !== '';
                var label = self.strings.moveSelected || 'Move';
                if (context === 'grid') $wrap.toggleClass('is-active', $('.media-frame').hasClass('mode-select'));
                $wrap.find('.cinderwell-mf-bulk-move-button').prop('disabled', !count || !hasFolder).text(count ? label + ' ' + count : label);
            });
        },

        moveBulkSelection: function ($wrap) {
            var self = this;
            var context = $wrap.attr('data-context');
            var ids = this.getBulkSelectedIds(context);
            var folderId = parseInt($wrap.find('.cinderwell-mf-bulk-folder').val(), 10);
            if (!ids.length || isNaN(folderId)) return;
            var $button = $wrap.find('.cinderwell-mf-bulk-move-button');
            $button.prop('disabled', true).text(this.strings.moving || 'Moving…');
            this.moveAttachments(ids, folderId, $button, true).done(function () {
                $wrap.find('.cinderwell-mf-bulk-folder').val('');
                if (context === 'grid' && $('.media-frame').hasClass('mode-select')) {
                    $('.select-mode-toggle-button').trigger('click');
                } else if (context === 'list') {
                    $('#the-list .check-column input[type="checkbox"]:checked').prop('checked', false).trigger('change');
                }
                window.setTimeout(function () { self.updateBulkMoveState(); }, 200);
            }).fail(function () { self.updateBulkMoveState(); });
        },

        buildSidebar: function () {
            var self = this;
            var $sidebar = $('<aside class="cinderwell-media-folders-sidebar"></aside>');
            $sidebar.attr('aria-label', this.strings.mediaFolders || 'Media folders');
            var $header = $('<div class="cinderwell-mf-header"></div>');
            var $title = $('<div class="cinderwell-mf-title"><span class="dashicons dashicons-category" aria-hidden="true"></span><strong></strong></div>');
            $title.find('strong').text(this.strings.folders || 'Folders');
            $header.append($title);
            if (this.canManage) {
                $header.append($('<button type="button" class="cinderwell-mf-icon-button cinderwell-mf-new-folder-toggle" aria-expanded="false" aria-controls="cinderwell-mf-new-folder" aria-label="' + (this.strings.newFolder || 'New folder') + '"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span></button>').on('click', function () { self.showCreateForm(0); }));
            }
            var $search = $('<label class="cinderwell-mf-search"><span class="screen-reader-text"></span><span class="dashicons dashicons-search" aria-hidden="true"></span><input type="search" /></label>');
            $search.find('.screen-reader-text').text(this.strings.searchFolders || 'Search folders');
            $search.find('input').attr('placeholder', this.strings.searchFolders || 'Search folders').on('input', function () { self.filterTree($(this).val()); });
            var $tree = $('<ul class="cinderwell-folder-tree"></ul>');
            $tree.append(this.folderItem({ id: 0, name: this.strings.allMedia || 'All Media', children: [], count: null, system: true }));
            $tree.append(this.folderItem({ id: -1, name: this.strings.uncategorized || 'Unfiled', children: [], count: null, system: true }));
            this.folders.forEach(function (folder) { $tree.append(self.folderItem(folder)); });
            $sidebar.append($header).append($search);
            if (this.canManage) $sidebar.append(this.newFolderForm());
            $sidebar.append($tree);
            $sidebar.append($('<div class="cinderwell-mf-status" role="status" aria-live="polite"></div>'));
            return $sidebar;
        },

        folderItem: function (folder) {
            var self = this;
            var hasChildren = folder.children && folder.children.length;
            var $item = $('<li class="cinderwell-folder-item"></li>').attr({ 'data-folder-id': folder.id, 'data-folder-name': String(folder.name).toLowerCase() });
            var $row = $('<div class="cinderwell-folder-row"></div>');
            var $toggle = $('<span class="cinderwell-folder-toggle-spacer"></span>');
            if (hasChildren) {
                $toggle = $('<button type="button" class="cinderwell-folder-toggle" aria-expanded="true"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span><span class="screen-reader-text"></span></button>');
                $toggle.find('.screen-reader-text').text(this.strings.toggleFolders || 'Toggle subfolders');
                $toggle.on('click', function () {
                    var expanded = $(this).attr('aria-expanded') === 'true';
                    $(this).attr('aria-expanded', expanded ? 'false' : 'true');
                    $(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2', !expanded).toggleClass('dashicons-arrow-right-alt2', expanded);
                    $item.children('.children').toggle(!expanded);
                });
            }
            var query = this.listMode ? 'mode=list&' : '';
            var href = folder.id > 0 ? this.uploadUrl + '?' + query + 'cwmf_folder=' + folder.id : (folder.id === -1 ? this.uploadUrl + '?' + query + 'cwmf_folder=-1' : this.uploadUrl + (this.listMode ? '?mode=list' : ''));
            var $link = $('<a class="cinderwell-folder-link"></a>').attr('href', href);
            var $visual = $('<span class="cinderwell-folder-visual"><span class="dashicons dashicons-category" aria-hidden="true"></span></span>');
            if (folder.color) $visual.css('--cwmf-folder-color', folder.color).addClass('has-color');
            $link.append($visual).append($('<span class="cinderwell-folder-name"></span>').text(folder.name));
            if (this.current === folder.id) $item.addClass('is-current');
            $row.append($toggle).append($link);
            if (folder.count !== null && folder.count !== undefined) $row.append($('<span class="cinderwell-folder-count"></span>').text(folder.count));
            if (this.canManage && folder.id > 0) $row.append(this.folderActions(folder, $item));
            $item.append($row);
            if (folder.id !== 0) this.makeDropTarget($row, folder.id);
            if (hasChildren) {
                var $children = $('<ul class="children"></ul>');
                folder.children.forEach(function (child) { $children.append(self.folderItem(child)); });
                $item.append($children);
            }
            return $item;
        },

        folderActions: function (folder, $item) {
            var self = this;
            var $wrap = $('<div class="cinderwell-folder-actions"></div>');
            var $button = $('<button type="button" class="cinderwell-folder-actions-toggle" aria-expanded="false"><span class="dashicons dashicons-ellipsis" aria-hidden="true"></span><span class="screen-reader-text"></span></button>');
            $button.find('.screen-reader-text').text(this.strings.moreActions || 'Folder actions');
            var $menu = $('<div class="cinderwell-folder-menu" role="menu" hidden></div>');
            function action(label, icon, handler, destructive) {
                var $action = $('<button type="button" role="menuitem"></button>').toggleClass('is-destructive', !!destructive);
                $action.append($('<span class="dashicons" aria-hidden="true"></span>').addClass(icon)).append($('<span></span>').text(label));
                $action.on('click', function () { $menu.prop('hidden', true); $button.attr('aria-expanded', 'false'); handler(); });
                return $action;
            }
            $menu.append(action(this.strings.newSubfolder || 'New subfolder', 'dashicons-plus-alt2', function () { self.showCreateForm(folder.id, folder.name); }));
            $menu.append(action(this.strings.rename || 'Rename', 'dashicons-edit', function () { self.startRename(folder, $item); }));
            $menu.append(action(this.strings.delete || 'Delete', 'dashicons-trash', function () { self.deleteFolder(folder); }, true));
            $button.on('click', function (event) {
                event.preventDefault(); event.stopPropagation();
                var willOpen = $menu.prop('hidden');
                self.closeFolderMenus();
                if (willOpen) self.openFolderMenu($menu, this);
                $button.attr('aria-expanded', willOpen ? 'true' : 'false');
            });
            return $wrap.append($button).append($menu);
        },

        openFolderMenu: function ($menu, trigger) {
            var rect = trigger.getBoundingClientRect();
            $('body').append($menu);
            $menu.css({ position: 'fixed', top: 0, left: 0 }).prop('hidden', false);
            var width = $menu.outerWidth();
            var height = $menu.outerHeight();
            var left = Math.max(8, Math.min(window.innerWidth - width - 8, rect.right - width));
            var top = rect.bottom + 4;
            if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 4);
            $menu.css({ left: left, top: top });
        },

        closeFolderMenus: function () {
            $('.cinderwell-folder-menu').prop('hidden', true);
            $('.cinderwell-folder-actions-toggle').attr('aria-expanded', 'false');
        },

        newFolderForm: function () {
            var self = this;
            var $form = $('<form id="cinderwell-mf-new-folder" class="cinderwell-new-folder" hidden></form>');
            var $context = $('<div class="cinderwell-new-folder-context"></div>');
            var $input = $('<input type="text" required />').attr('placeholder', this.strings.folderName || 'Folder name');
            var $actions = $('<div class="cinderwell-new-folder-actions"></div>');
            var $submit = $('<button type="submit" class="button button-primary"></button>').text(this.strings.createFolder || 'Create');
            var $cancel = $('<button type="button" class="button"></button>').text(this.strings.cancel || 'Cancel');
            $cancel.on('click', function () { self.hideCreateForm(); });
            $form.on('submit', function (event) {
                event.preventDefault();
                var name = $input.val().trim();
                if (name) self.createFolder(name, self.createParent);
            });
            $input.on('keydown', function (event) { if (event.key === 'Escape') self.hideCreateForm(); });
            return $form.append($context).append($input).append($actions.append($submit).append($cancel));
        },

        showCreateForm: function (parentId, parentName) {
            this.createParent = parentId || 0;
            var $form = $('.cinderwell-new-folder').first();
            var label = parentId ? (this.strings.newSubfolder || 'New subfolder') + ': ' + parentName : (this.strings.newFolder || 'New folder');
            $form.find('.cinderwell-new-folder-context').text(label);
            $('.cinderwell-mf-new-folder-toggle').attr('aria-expanded', 'true');
            $form.prop('hidden', false).find('input').val('').trigger('focus');
        },

        hideCreateForm: function () { this.createParent = 0; $('.cinderwell-mf-new-folder-toggle').attr('aria-expanded', 'false'); $('.cinderwell-new-folder').prop('hidden', true).find('input').val(''); },

        startRename: function (folder, $item) {
            var self = this;
            var $row = $item.children('.cinderwell-folder-row');
            if ($row.find('.cinderwell-folder-rename').length) return;
            $row.children().hide();
            var $form = $('<form class="cinderwell-folder-rename"></form>');
            var $input = $('<input type="text" required />').val(folder.name);
            var $save = $('<button type="submit" class="button button-small button-primary"></button>').text(this.strings.save || 'Save');
            var $cancel = $('<button type="button" class="button button-small"></button>').text(this.strings.cancel || 'Cancel');
            function close() { $form.remove(); $row.children().show(); }
            $cancel.on('click', close);
            $form.on('submit', function (event) {
                event.preventDefault();
                var name = $input.val().trim();
                if (!name || name === folder.name) return close();
                self.updateFolder(folder.id, { name: name }).done(function () { window.location.reload(); }).fail(function (xhr) { self.showError(xhr); });
            });
            $row.append($form.append($input).append($save).append($cancel));
            $input.trigger('focus').trigger('select');
        },

        makeDropTarget: function ($target, folderId) {
            var self = this;
            if ($.fn.droppable) {
                $target.droppable({
                    accept: '.attachment, .cinderwell-mf-list-media-row',
                    tolerance: 'pointer',
                    hoverClass: 'drag-over',
                    drop: function (event, ui) {
                        var ids = self.getDraggedAttachmentIds(ui.draggable);
                        if (ids.length) self.moveAttachments(ids, folderId, $target);
                    }
                });
            }
        },

        initGridDragDrop: function () {
            var self = this;
            this.enableMediaDraggables();
            $(document).ajaxComplete(function () { self.enableMediaDraggables(); self.renderChildFolders(); });
            window.setTimeout(function () { self.enableMediaDraggables(); self.renderChildFolders(); }, 500);
            var target = document.querySelector('.attachments-wrapper') || document.querySelector('#the-list');
            if (target && window.MutationObserver) {
                new MutationObserver(function () { self.enableMediaDraggables(); }).observe(target, { childList: true, subtree: true });
            }
        },

        enableMediaDraggables: function () {
            if (!$.fn.draggable) return;
            var self = this;
            var $items = $('.attachments .attachment, #the-list tr[id^="post-"]').not('.cinderwell-mf-drag-ready');
            $items.each(function () {
                var $item = $(this).addClass('cinderwell-mf-drag-ready');
                if ($item.is('tr')) $item.addClass('cinderwell-mf-list-media-row');
                $item.draggable({
                    appendTo: 'body',
                    distance: 8,
                    helper: function () {
                        var count = self.getDraggedAttachmentIds($item).length;
                        return $('<div class="cinderwell-mf-drag-helper"><span class="dashicons dashicons-format-image" aria-hidden="true"></span></div>').append($('<span></span>').text(count === 1 ? '1 media item' : count + ' media items'));
                    },
                    revert: 'invalid',
                    revertDuration: 140,
                    scroll: true,
                    zIndex: 100000
                });
            });
        },

        getDraggedAttachmentIds: function ($dragged) {
            var ids = [];
            if ($dragged.is('tr')) {
                var $checkbox = $dragged.find('.check-column input[type="checkbox"]');
                var $rows = $checkbox.prop('checked') ? $('#the-list .check-column input[type="checkbox"]:checked').closest('tr') : $dragged;
                $rows.each(function () {
                    var id = parseInt(String(this.id || '').replace('post-', ''), 10);
                    if (id) ids.push(id);
                });
            } else {
                var $selected = $dragged.hasClass('selected') ? $('.attachments .attachment.selected') : $dragged;
                $selected.each(function () {
                    var id = parseInt($(this).attr('data-id') || $(this).data('id'), 10);
                    if (id) ids.push(id);
                });
            }
            return ids;
        },

        moveAttachments: function (attachmentIds, folderId, $target, forceReplace) {
            var self = this;
            var replace = typeof forceReplace === 'boolean' ? forceReplace : folderId < 1 || !this.multiFolder;
            return this.request('folders/' + folderId + '/media', 'POST', { attachment_ids: attachmentIds, replace: replace }).done(function (response) {
                $target.addClass('drop-success'); setTimeout(function () { $target.removeClass('drop-success'); }, 650);
                self.updateCounts(response.counts || {});
                self.setStatus(replace ? (self.strings.moveSuccess || 'Media moved.') : (self.strings.addSuccess || 'Media added to folder.'));
                if (self.current && self.current !== folderId) attachmentIds.forEach(function (id) { $('.attachment[data-id="' + id + '"], #post-' + id).fadeOut(180, function () { $(this).remove(); }); });
            }).fail(function (xhr) { self.showError(xhr); });
        },

        createFolder: function (name, parent) { var self = this; this.request('folders', 'POST', { name: name, parent: parent || 0 }).done(function () { window.location.reload(); }).fail(function (xhr) { self.showError(xhr); }); },
        updateFolder: function (id, changes) { return this.request('folders/' + id, 'PATCH', changes); },
        deleteFolder: function (folder) {
            var self = this;
            if (!window.confirm(this.strings.confirmDelete || 'Delete this folder?')) return;
            this.request('folders/' + folder.id, 'DELETE').done(function () { if (self.current === folder.id) window.location.href = self.uploadUrl; else window.location.reload(); }).fail(function (xhr) { self.showError(xhr); });
        },

        filterTree: function (query) {
            query = String(query || '').trim().toLowerCase();
            $('.cinderwell-folder-tree .cinderwell-folder-item').removeClass('is-filter-match is-filter-parent').show();
            if (!query) return;
            $('.cinderwell-folder-tree .cinderwell-folder-item').each(function () {
                var $item = $(this);
                if (($item.attr('data-folder-name') || '').indexOf(query) !== -1) $item.addClass('is-filter-match').show().parents('.cinderwell-folder-item').addClass('is-filter-parent').show();
            });
            $('.cinderwell-folder-tree .cinderwell-folder-item').not('.is-filter-match, .is-filter-parent').hide();
        },

        updateCounts: function (counts) {
            $('.cinderwell-folder-item').each(function () {
                var id = parseInt($(this).attr('data-folder-id'), 10);
                if (id > 0 && counts[id] !== undefined) $(this).children('.cinderwell-folder-row').find('> .cinderwell-folder-count').text(counts[id]);
            });
        },

        setStatus: function (message, error) { $('.cinderwell-mf-status').text(message).toggleClass('is-error', !!error); },
        showError: function (xhr) { var message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : (this.strings.requestFailed || 'That change could not be saved.'); this.setStatus(message, true); },
        findFolder: function (folders, id) {
            for (var i = 0; i < folders.length; i++) {
                if (parseInt(folders[i].id, 10) === parseInt(id, 10)) return folders[i];
                var found = this.findFolder(folders[i].children || [], id);
                if (found) return found;
            }
            return null;
        },

        appendFolderOptions: function ($select, folders, depth) {
            var self = this;
            folders.forEach(function (folder) { $select.append($('<option></option>').val(folder.id).text(new Array(depth + 1).join('— ') + folder.name)); self.appendFolderOptions($select, folder.children || [], depth + 1); });
        },

        extendMediaModal: function () {
            var self = this;
            new MutationObserver(function () { self.injectModalFilter(); }).observe(document.body, { childList: true, subtree: true });
            this.injectModalFilter();
        },

        injectModalFilter: function () {
            var self = this;
            $('.media-modal .attachments-browser:visible .media-toolbar-secondary').first().each(function () {
                var $toolbar = $(this);
                if ($toolbar.find('.cinderwell-mf-modal-filter').length) return;
                var $label = $('<label class="cinderwell-mf-modal-filter"><span class="cinderwell-mf-modal-filter-label"></span></label>');
                var $select = $('<select></select>');
                $label.find('.cinderwell-mf-modal-filter-label').text(self.strings.filterByFolder || 'Filter by folder');
                $select.append($('<option></option>').val(0).text(self.strings.allMedia || 'All Media'));
                $select.append($('<option></option>').val(-1).text(self.strings.uncategorized || 'Unfiled'));
                self.appendFolderOptions($select, self.folders, 0);
                $select.on('change', function () {
                    var folderId = parseInt($(this).val(), 10);
                    var frame = wp.media.frame; var state = frame && frame.state ? frame.state() : null; var library = state && state.get ? state.get('library') : null;
                    if (!library || !library.props) return;
                    if (folderId) library.props.set('cwmf_folder', folderId);
                    else library.props.unset('cwmf_folder');
                    if (wp.Uploader && wp.Uploader.defaults && wp.Uploader.defaults.multipart_params) {
                        if (folderId > 0) wp.Uploader.defaults.multipart_params.cwmf_folder = folderId; else delete wp.Uploader.defaults.multipart_params.cwmf_folder;
                    }
                });
                $toolbar.prepend($label.append($select));
            });
        }
    };

    var booted = false;
    var observer = null;

    function boot() {
        if (booted) return;
        booted = true;
        if (observer) observer.disconnect();
        CMF.init();
    }

    function mediaLibraryMarkupReady() {
        var listReady = $('#posts-filter .tablenav.top').length && $('#posts-filter .wp-list-table').length && $('#posts-filter .tablenav.bottom').length;
        var gridReady = $('.attachments-browser > .wp-filter').length && $('.attachments-browser .attachments').length;
        var requestedMode = typeof cinderwellMediaFolders !== 'undefined' ? cinderwellMediaFolders.libraryMode : new URLSearchParams(window.location.search).get('mode');
        if (requestedMode === 'grid') return gridReady;
        if (requestedMode === 'list') return listReady;
        return listReady || gridReady;
    }

    if (document.body && mediaLibraryMarkupReady()) {
        boot();
    } else if (window.MutationObserver && document.documentElement) {
        observer = new MutationObserver(function () {
            if (mediaLibraryMarkupReady()) boot();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    $(document).ready(function () {
        if (mediaLibraryMarkupReady() || !document.body.classList.contains('upload-php')) boot();
    });
})(jQuery);
