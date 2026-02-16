/* ═══════════════════════════════════════════════════════════════
   Document Manager – Admin JavaScript
   ═══════════════════════════════════════════════════════════════ */

(function ($) {
    'use strict';

    /* ── Helpers ─────────────────────────────────── */
    function showToast(message, type) {
        var $existing = $('.docmgr-toast');
        if ($existing.length) $existing.remove();

        var $toast = $('<div class="docmgr-toast ' + (type || '') + '">' + message + '</div>');
        $('body').append($toast);
        setTimeout(function () { $toast.addClass('show'); }, 10);
        setTimeout(function () {
            $toast.removeClass('show');
            setTimeout(function () { $toast.remove(); }, 300);
        }, 3000);
    }

    function getFileTypeLabel(ext) {
        return ext ? ext.toUpperCase() : 'FILE';
    }

    function updateFileCount() {
        var count = $('#docmgr-file-list .docmgr-file-row').length;
        $('#docmgr-file-count').text(count);
        if (count === 0) {
            if (!$('#docmgr-empty-files').length) {
                $('#docmgr-file-list').html(
                    '<div class="docmgr-empty-files" id="docmgr-empty-files">' +
                    '<span class="dashicons dashicons-media-document"></span>' +
                    '<p>No documents yet. Upload some files above!</p>' +
                    '</div>'
                );
            }
        } else {
            $('#docmgr-empty-files').remove();
        }
    }

    /* ── Render a file row from data ─────────────── */
    function renderFileRow(file) {
        var html = '<div class="docmgr-file-row" data-file-id="' + file.id + '">';
        html += '<div class="docmgr-file-handle" title="Drag to reorder"><span class="dashicons dashicons-menu"></span></div>';
        html += '<div class="docmgr-file-icon-wrap"><span class="docmgr-file-type-icon" data-ext="' + file.ext + '">' + getFileTypeLabel(file.ext) + '</span></div>';
        html += '<div class="docmgr-file-info">';
        html += '<div class="docmgr-file-name-wrap">';
        html += '<span class="docmgr-file-name-text">' + $('<span>').text(file.display_name).html() + '</span>';
        html += '<input type="text" class="docmgr-file-name-input docmgr-hidden" value="' + $('<span>').text(file.display_name).html() + '">';
        html += '<button type="button" class="docmgr-rename-btn" title="Rename"><span class="dashicons dashicons-edit-page"></span></button>';
        html += '<button type="button" class="docmgr-rename-save docmgr-hidden" title="Save name"><span class="dashicons dashicons-yes-alt"></span></button>';
        html += '<button type="button" class="docmgr-rename-cancel docmgr-hidden" title="Cancel"><span class="dashicons dashicons-dismiss"></span></button>';
        html += '</div>';
        html += '<div class="docmgr-file-meta">';
        html += '<span class="docmgr-file-ext-badge">' + (file.ext || 'file') + '</span>';
        html += '<span class="docmgr-file-size">' + (file.size || '') + '</span>';
        html += '</div></div>';
        html += '<div class="docmgr-file-actions">';
        html += '<a href="' + file.url + '" class="docmgr-action-btn" title="Download" target="_blank" download><span class="dashicons dashicons-download"></span></a>';
        html += '<button type="button" class="docmgr-action-btn docmgr-remove-btn" title="Remove from group" data-file-id="' + file.id + '"><span class="dashicons dashicons-no-alt"></span></button>';
        html += '</div></div>';
        return html;
    }

    /* ── Initialise SortableJS ──────────────────── */
    var sortableInstance = null;

    function initSortable() {
        var el = document.getElementById('docmgr-file-list');
        if (!el) return;
        if (sortableInstance) sortableInstance.destroy();

        sortableInstance = Sortable.create(el, {
            handle: '.docmgr-file-handle',
            animation: 200,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function () {
                var order = [];
                $('#docmgr-file-list .docmgr-file-row').each(function () {
                    order.push($(this).data('file-id'));
                });

                $.post(docmgr.ajax_url, {
                    action: 'docmgr_reorder_files',
                    nonce: docmgr.nonce,
                    order: order
                }).done(function (res) {
                    if (res.success) showToast('Order saved', 'success');
                });
            }
        });
    }

    /* ── Load initial files ─────────────────────── */
    function loadFiles() {
        if (typeof docmgrFiles === 'undefined' || !docmgrFiles.length) return;
        $('#docmgr-empty-files').remove();
        var html = '';
        docmgrFiles.forEach(function (file) {
            html += renderFileRow(file);
        });
        $('#docmgr-file-list').html(html);
        initSortable();
    }

    /* ═══════════════════════════════════════════════
       Group Save / Create
       ═══════════════════════════════════════════════ */
    $(document).on('click', '#docmgr-save-group', function () {
        var $btn     = $(this);
        var groupId  = $btn.data('group-id');
        var name     = $('#docmgr-group-name').val().trim();
        var isNew    = !groupId;

        if (!name) {
            showToast('Please enter a group name.', 'error');
            return;
        }

        $btn.prop('disabled', true);

        $.post(docmgr.ajax_url, {
            action: isNew ? 'docmgr_create_group' : 'docmgr_update_group',
            nonce: docmgr.nonce,
            group_id: groupId,
            name: name
        }).done(function (res) {
            if (res.success) {
                if (isNew) {
                    showToast('Group created!', 'success');
                    // Redirect to edit page
                    window.location.href = docmgr.ajax_url.replace('/wp-admin/admin-ajax.php', '/wp-admin/admin.php?page=docmgr&action=edit&group_id=' + res.data.id);
                } else {
                    showToast('Group name saved.', 'success');
                }
            } else {
                showToast(res.data || 'Error', 'error');
            }
        }).fail(function () {
            showToast('Request failed.', 'error');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ═══════════════════════════════════════════════
       Copy Shortcode
       ═══════════════════════════════════════════════ */
    $(document).on('click', '.docmgr-copy-btn', function () {
        var shortcode = $(this).data('shortcode');
        var $btn = $(this);
        navigator.clipboard.writeText(shortcode).then(function () {
            $btn.addClass('copied');
            showToast('Shortcode copied!', 'success');
            setTimeout(function () { $btn.removeClass('copied'); }, 2000);
        });
    });

    /* ═══════════════════════════════════════════════
       Drag & Drop Upload
       ═══════════════════════════════════════════════ */
    var $dropzone = $('#docmgr-dropzone');

    $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    }).on('drop', function (e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files.length) uploadFiles(files);
    });

    /* ── Browse button ─────────────────────────── */
    $('#docmgr-browse-btn').on('click', function () {
        $('#docmgr-file-input').trigger('click');
    });

    $('#docmgr-file-input').on('change', function () {
        if (this.files.length) {
            uploadFiles(this.files);
            this.value = '';
        }
    });

    /* ── Media Library button ─────────────────── */
    $('#docmgr-media-btn').on('click', function () {
        var frame = wp.media({
            title: 'Select Documents',
            button: { text: 'Add to Group' },
            multiple: true
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var ids = [];
            selection.each(function (attachment) {
                ids.push(attachment.id);
            });

            if (!ids.length) return;

            var groupId = $('#docmgr-file-list').data('group-id');

            $.post(docmgr.ajax_url, {
                action: 'docmgr_add_media',
                nonce: docmgr.nonce,
                group_id: groupId,
                attachment_ids: ids
            }).done(function (res) {
                if (res.success && res.data.length) {
                    $('#docmgr-empty-files').remove();
                    res.data.forEach(function (file) {
                        $('#docmgr-file-list').append(renderFileRow(file));
                    });
                    initSortable();
                    updateFileCount();
                    showToast(res.data.length + ' file(s) added.', 'success');
                } else if (res.success) {
                    showToast('Files already in this group.', '');
                }
            });
        });

        frame.open();
    });

    /* ── Upload function ───────────────────────── */
    function uploadFiles(fileList) {
        var groupId = $('#docmgr-file-list').data('group-id');
        if (!groupId) {
            showToast('Save the group first.', 'error');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'docmgr_upload_files');
        formData.append('nonce', docmgr.nonce);
        formData.append('group_id', groupId);

        for (var i = 0; i < fileList.length; i++) {
            formData.append('files[]', fileList[i]);
        }

        var $progress = $('#docmgr-upload-progress');
        var $fill     = $('#docmgr-progress-fill');
        var $text     = $('#docmgr-progress-text');
        $progress.removeClass('docmgr-hidden');
        $fill.css('width', '0%');
        $text.text('Uploading ' + fileList.length + ' file(s)...');

        $.ajax({
            url: docmgr.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        $fill.css('width', pct + '%');
                        $text.text('Uploading... ' + pct + '%');
                    }
                });
                return xhr;
            }
        }).done(function (res) {
            if (res.success && res.data.length) {
                $('#docmgr-empty-files').remove();
                res.data.forEach(function (file) {
                    $('#docmgr-file-list').append(renderFileRow(file));
                });
                initSortable();
                updateFileCount();
                showToast(res.data.length + ' file(s) uploaded!', 'success');
            } else {
                showToast('Upload failed.', 'error');
            }
        }).fail(function () {
            showToast('Upload request failed.', 'error');
        }).always(function () {
            setTimeout(function () {
                $progress.addClass('docmgr-hidden');
                $fill.css('width', '0%');
            }, 1000);
        });
    }

    /* ═══════════════════════════════════════════════
       Rename File
       ═══════════════════════════════════════════════ */
    $(document).on('click', '.docmgr-rename-btn', function () {
        var $row = $(this).closest('.docmgr-file-row');
        $row.find('.docmgr-file-name-text').addClass('docmgr-hidden');
        $row.find('.docmgr-rename-btn').addClass('docmgr-hidden');
        $row.find('.docmgr-file-name-input').removeClass('docmgr-hidden').focus().select();
        $row.find('.docmgr-rename-save, .docmgr-rename-cancel').removeClass('docmgr-hidden');
    });

    $(document).on('click', '.docmgr-rename-cancel', function () {
        var $row = $(this).closest('.docmgr-file-row');
        var original = $row.find('.docmgr-file-name-text').text();
        $row.find('.docmgr-file-name-input').val(original).addClass('docmgr-hidden');
        $row.find('.docmgr-file-name-text').removeClass('docmgr-hidden');
        $row.find('.docmgr-rename-btn').removeClass('docmgr-hidden');
        $row.find('.docmgr-rename-save, .docmgr-rename-cancel').addClass('docmgr-hidden');
    });

    $(document).on('click', '.docmgr-rename-save', function () {
        var $row   = $(this).closest('.docmgr-file-row');
        var fileId = $row.data('file-id');
        var newName = $row.find('.docmgr-file-name-input').val().trim();

        if (!newName) {
            showToast('Name cannot be empty.', 'error');
            return;
        }

        $.post(docmgr.ajax_url, {
            action: 'docmgr_rename_file',
            nonce: docmgr.nonce,
            file_id: fileId,
            display_name: newName
        }).done(function (res) {
            if (res.success) {
                $row.find('.docmgr-file-name-text').text(newName).removeClass('docmgr-hidden');
                $row.find('.docmgr-file-name-input').addClass('docmgr-hidden');
                $row.find('.docmgr-rename-btn').removeClass('docmgr-hidden');
                $row.find('.docmgr-rename-save, .docmgr-rename-cancel').addClass('docmgr-hidden');
                showToast('Renamed!', 'success');
            }
        });
    });

    // Enter key to save rename
    $(document).on('keydown', '.docmgr-file-name-input', function (e) {
        if (e.key === 'Enter') {
            $(this).closest('.docmgr-file-name-wrap').find('.docmgr-rename-save').trigger('click');
        } else if (e.key === 'Escape') {
            $(this).closest('.docmgr-file-name-wrap').find('.docmgr-rename-cancel').trigger('click');
        }
    });

    /* ═══════════════════════════════════════════════
       Remove File
       ═══════════════════════════════════════════════ */
    $(document).on('click', '.docmgr-remove-btn', function () {
        if (!confirm('Remove this file from the group? (The file remains in your Media Library.)')) return;

        var $row   = $(this).closest('.docmgr-file-row');
        var fileId = $(this).data('file-id');

        $.post(docmgr.ajax_url, {
            action: 'docmgr_remove_file',
            nonce: docmgr.nonce,
            file_id: fileId
        }).done(function (res) {
            if (res.success) {
                $row.slideUp(200, function () {
                    $(this).remove();
                    updateFileCount();
                });
                showToast('File removed.', 'success');
            }
        });
    });

    /* ═══════════════════════════════════════════════
       Init
       ═══════════════════════════════════════════════ */
    $(document).ready(function () {
        loadFiles();
    });

})(jQuery);
