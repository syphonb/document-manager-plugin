<?php
/**
 * Plugin Name: Document Manager
 * Plugin URI:
 * Description: Create document groups, bulk upload files via drag-and-drop, rename/reorder/remove files, and embed download lists via shortcode.
 * Version: 1.0.0
 * Author: Custom
 * License: GPL v2 or later
 * Text Domain: document-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DOCMGR_VERSION', '1.0.0' );
define( 'DOCMGR_PATH', plugin_dir_path( __FILE__ ) );
define( 'DOCMGR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Get one document group row by ID.
 *
 * @param int $group_id Group ID.
 * @return object|null
 */
function docmgr_get_group( $group_id ) {
    global $wpdb;
    $group_id = absint( $group_id );
    if ( ! $group_id ) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}docmgr_groups WHERE id = %d",
            $group_id
        )
    );
}

/**
 * Get one document file row by ID.
 *
 * @param int $file_id File record ID.
 * @return object|null
 */
function docmgr_get_file( $file_id ) {
    global $wpdb;
    $file_id = absint( $file_id );
    if ( ! $file_id ) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}docmgr_files WHERE id = %d",
            $file_id
        )
    );
}

/**
 * ─── Activation: create DB tables ───
 */
function docmgr_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $groups_table = $wpdb->prefix . 'docmgr_groups';
    $files_table  = $wpdb->prefix . 'docmgr_files';

    $sql = "CREATE TABLE {$groups_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL DEFAULT '',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset};

    CREATE TABLE {$files_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        group_id bigint(20) unsigned NOT NULL,
        attachment_id bigint(20) unsigned NOT NULL,
        display_name varchar(255) NOT NULL DEFAULT '',
        sort_order int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY group_id (group_id),
        KEY sort_order (sort_order)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'docmgr_db_version', DOCMGR_VERSION );
}
register_activation_hook( __FILE__, 'docmgr_activate' );

/**
 * ─── Admin menu ───
 */
function docmgr_admin_menu() {
    add_menu_page(
        'Document Manager',
        'Doc Manager',
        'manage_options',
        'docmgr',
        'docmgr_render_admin_page',
        'dashicons-media-document',
        30
    );
}
add_action( 'admin_menu', 'docmgr_admin_menu' );

/**
 * Route to the correct admin template.
 */
function docmgr_render_admin_page() {
    $action   = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
    $group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;

    if ( $action === 'edit' && $group_id > 0 ) {
        include DOCMGR_PATH . 'admin/group-edit.php';
    } elseif ( $action === 'new' ) {
        include DOCMGR_PATH . 'admin/group-edit.php';
    } else {
        include DOCMGR_PATH . 'admin/group-list.php';
    }
}

/**
 * Handle group deletion via admin-post endpoint.
 */
function docmgr_handle_admin_group_delete() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized', 403 );
    }

    check_admin_referer( 'docmgr_delete_group' );

    global $wpdb;
    $group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
    $notice   = 'group_delete_failed';

    if ( ! $group_id ) {
        $notice = 'invalid_group';
    } elseif ( ! docmgr_get_group( $group_id ) ) {
        $notice = 'group_not_found';
    } else {
        $deleted_files = $wpdb->delete( $wpdb->prefix . 'docmgr_files', array( 'group_id' => $group_id ), array( '%d' ) );
        $deleted_group = $wpdb->delete( $wpdb->prefix . 'docmgr_groups', array( 'id' => $group_id ), array( '%d' ) );

        if ( false !== $deleted_files && 1 === (int) $deleted_group ) {
            $notice = 'group_deleted';
        }
    }

    $redirect = add_query_arg(
        'docmgr_notice',
        $notice,
        admin_url( 'admin.php?page=docmgr' )
    );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_docmgr_delete_group', 'docmgr_handle_admin_group_delete' );

/**
 * ─── Enqueue admin assets ───
 */
function docmgr_admin_enqueue( $hook ) {
    if ( strpos( $hook, 'docmgr' ) === false ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
        'docmgr-admin',
        DOCMGR_URL . 'assets/css/admin.css',
        array(),
        DOCMGR_VERSION
    );

    wp_enqueue_script(
        'docmgr-sortable',
        DOCMGR_URL . 'assets/js/Sortable.min.js',
        array(),
        '1.15.6',
        true
    );

    wp_enqueue_script(
        'docmgr-admin',
        DOCMGR_URL . 'assets/js/admin.js',
        array( 'jquery', 'docmgr-sortable' ),
        DOCMGR_VERSION,
        true
    );

    wp_localize_script( 'docmgr-admin', 'docmgr', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'docmgr_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'docmgr_admin_enqueue' );

/**
 * ─── Enqueue frontend assets ───
 */
function docmgr_frontend_enqueue() {
    wp_enqueue_style(
        'docmgr-frontend',
        DOCMGR_URL . 'assets/css/frontend.css',
        array(),
        DOCMGR_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'docmgr_frontend_enqueue' );

/* ════════════════════════════════════════════════════════════════════════════
   AJAX HANDLERS
   ════════════════════════════════════════════════════════════════════════════ */

/**
 * Verify request & permissions for every AJAX handler.
 */
function docmgr_verify_ajax() {
    check_ajax_referer( 'docmgr_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }
}

/* ── Create Group ─────────────────────────────── */
add_action( 'wp_ajax_docmgr_create_group', function () {
    docmgr_verify_ajax();
    global $wpdb;
    $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
    if ( empty( $name ) ) {
        wp_send_json_error( 'Group name is required.' );
    }

    $created = $wpdb->insert( $wpdb->prefix . 'docmgr_groups', array(
        'name' => $name,
    ), array( '%s' ) );

    if ( false === $created || empty( $wpdb->insert_id ) ) {
        wp_send_json_error( 'Failed to create group.' );
    }

    wp_send_json_success( array( 'id' => $wpdb->insert_id, 'name' => $name ) );
});

/* ── Update Group ─────────────────────────────── */
add_action( 'wp_ajax_docmgr_update_group', function () {
    docmgr_verify_ajax();
    global $wpdb;
    $id   = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
    if ( ! $id || empty( $name ) ) {
        wp_send_json_error( 'Invalid data.' );
    }
    $group = docmgr_get_group( $id );
    if ( ! $group ) {
        wp_send_json_error( 'Group not found.' );
    }

    $updated = $wpdb->update(
        $wpdb->prefix . 'docmgr_groups',
        array( 'name' => $name ),
        array( 'id' => $id ),
        array( '%s' ),
        array( '%d' )
    );

    if ( false === $updated ) {
        wp_send_json_error( 'Failed to update group.' );
    }

    wp_send_json_success();
});

/* ── Delete Group ─────────────────────────────── */
add_action( 'wp_ajax_docmgr_delete_group', function () {
    docmgr_verify_ajax();
    global $wpdb;
    $id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    if ( ! $id ) {
        wp_send_json_error( 'Invalid group.' );
    }

    $group = docmgr_get_group( $id );
    if ( ! $group ) {
        wp_send_json_error( 'Group not found.' );
    }

    $deleted_files = $wpdb->delete( $wpdb->prefix . 'docmgr_files', array( 'group_id' => $id ), array( '%d' ) );
    if ( false === $deleted_files ) {
        wp_send_json_error( 'Failed to delete group files.' );
    }

    $deleted_group = $wpdb->delete( $wpdb->prefix . 'docmgr_groups', array( 'id' => $id ), array( '%d' ) );
    if ( false === $deleted_group || 0 === $deleted_group ) {
        wp_send_json_error( 'Failed to delete group.' );
    }

    wp_send_json_success();
});

/* ── Upload Files ─────────────────────────────── */
add_action( 'wp_ajax_docmgr_upload_files', function () {
    docmgr_verify_ajax();
    global $wpdb;

    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    if ( ! $group_id ) {
        wp_send_json_error( 'No group specified.' );
    }

    if ( ! docmgr_get_group( $group_id ) ) {
        wp_send_json_error( 'Group not found.' );
    }

    if ( empty( $_FILES['files'] ) ) {
        wp_send_json_error( 'No files uploaded.' );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $files_table = $wpdb->prefix . 'docmgr_files';
    $max_order   = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT MAX(sort_order) FROM {$files_table} WHERE group_id = %d", $group_id
    ) );

    $uploaded = array();
    $file_arr = $_FILES['files'];
    $count    = is_array( $file_arr['name'] ) ? count( $file_arr['name'] ) : 1;

    for ( $i = 0; $i < $count; $i++ ) {
        $single = array(
            'name'     => is_array( $file_arr['name'] ) ? $file_arr['name'][ $i ] : $file_arr['name'],
            'type'     => is_array( $file_arr['type'] ) ? $file_arr['type'][ $i ] : $file_arr['type'],
            'tmp_name' => is_array( $file_arr['tmp_name'] ) ? $file_arr['tmp_name'][ $i ] : $file_arr['tmp_name'],
            'error'    => is_array( $file_arr['error'] ) ? $file_arr['error'][ $i ] : $file_arr['error'],
            'size'     => is_array( $file_arr['size'] ) ? $file_arr['size'][ $i ] : $file_arr['size'],
        );

        $_FILES['upload'] = $single;
        $attach_id = media_handle_upload( 'upload', 0 );

        if ( is_wp_error( $attach_id ) ) {
            continue;
        }

        $max_order++;
        $display_name = pathinfo( $single['name'], PATHINFO_FILENAME );

        $inserted = $wpdb->insert( $files_table, array(
            'group_id'      => $group_id,
            'attachment_id'  => $attach_id,
            'display_name'   => sanitize_text_field( $display_name ),
            'sort_order'     => $max_order,
        ), array( '%d', '%d', '%s', '%d' ) );

        if ( false === $inserted || empty( $wpdb->insert_id ) ) {
            wp_delete_attachment( $attach_id, true );
            continue;
        }

        $file_url = wp_get_attachment_url( $attach_id );
        $file_path = get_attached_file( $attach_id );
        $file_size = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : '';
        $file_ext  = pathinfo( $single['name'], PATHINFO_EXTENSION );

        $uploaded[] = array(
            'id'            => $wpdb->insert_id,
            'attachment_id' => $attach_id,
            'display_name'  => sanitize_text_field( $display_name ),
            'sort_order'    => $max_order,
            'url'           => $file_url,
            'size'          => $file_size,
            'ext'           => strtolower( $file_ext ),
        );
    }

    if ( empty( $uploaded ) ) {
        wp_send_json_error( 'No files were uploaded.' );
    }

    wp_send_json_success( $uploaded );
});

/* ── Add Files from Media Library ─────────────── */
add_action( 'wp_ajax_docmgr_add_media', function () {
    docmgr_verify_ajax();
    global $wpdb;

    $group_id       = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'absint', (array) $_POST['attachment_ids'] ) : array();

    if ( ! $group_id || empty( $attachment_ids ) ) {
        wp_send_json_error( 'Invalid data.' );
    }

    if ( ! docmgr_get_group( $group_id ) ) {
        wp_send_json_error( 'Group not found.' );
    }

    $files_table = $wpdb->prefix . 'docmgr_files';
    $max_order   = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT MAX(sort_order) FROM {$files_table} WHERE group_id = %d", $group_id
    ) );

    $added = array();

    foreach ( $attachment_ids as $attach_id ) {
        if ( 'attachment' !== get_post_type( $attach_id ) ) {
            continue;
        }

        // Skip if already in this group
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$files_table} WHERE group_id = %d AND attachment_id = %d",
            $group_id, $attach_id
        ) );
        if ( $existing ) {
            continue;
        }

        $max_order++;
        $filename     = get_the_title( $attach_id ) ?: basename( get_attached_file( $attach_id ) );
        $display_name = pathinfo( $filename, PATHINFO_FILENAME );

        $inserted = $wpdb->insert( $files_table, array(
            'group_id'      => $group_id,
            'attachment_id'  => $attach_id,
            'display_name'   => sanitize_text_field( $display_name ),
            'sort_order'     => $max_order,
        ), array( '%d', '%d', '%s', '%d' ) );

        if ( false === $inserted || empty( $wpdb->insert_id ) ) {
            continue;
        }

        $file_url  = wp_get_attachment_url( $attach_id );
        $file_path = get_attached_file( $attach_id );
        $file_size = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : '';
        $file_ext  = pathinfo( $file_path, PATHINFO_EXTENSION );

        $added[] = array(
            'id'            => $wpdb->insert_id,
            'attachment_id' => $attach_id,
            'display_name'  => sanitize_text_field( $display_name ),
            'sort_order'    => $max_order,
            'url'           => $file_url,
            'size'          => $file_size,
            'ext'           => strtolower( $file_ext ),
        );
    }

    wp_send_json_success( $added );
});

/* ── Rename File ──────────────────────────────── */
add_action( 'wp_ajax_docmgr_rename_file', function () {
    docmgr_verify_ajax();
    global $wpdb;
    $file_id = isset( $_POST['file_id'] ) ? absint( $_POST['file_id'] ) : 0;
    $name    = isset( $_POST['display_name'] ) ? sanitize_text_field( $_POST['display_name'] ) : '';
    if ( ! $file_id || empty( $name ) ) {
        wp_send_json_error( 'Invalid data.' );
    }

    $file = docmgr_get_file( $file_id );
    if ( ! $file ) {
        wp_send_json_error( 'File not found.' );
    }

    $updated = $wpdb->update(
        $wpdb->prefix . 'docmgr_files',
        array( 'display_name' => $name ),
        array( 'id' => $file_id ),
        array( '%s' ),
        array( '%d' )
    );

    if ( false === $updated ) {
        wp_send_json_error( 'Failed to rename file.' );
    }

    wp_send_json_success();
});

/* ── Remove File ──────────────────────────────── */
add_action( 'wp_ajax_docmgr_remove_file', function () {
    docmgr_verify_ajax();
    global $wpdb;
    $file_id = isset( $_POST['file_id'] ) ? absint( $_POST['file_id'] ) : 0;
    if ( ! $file_id ) {
        wp_send_json_error( 'Invalid file.' );
    }

    $file = docmgr_get_file( $file_id );
    if ( ! $file ) {
        wp_send_json_error( 'File not found.' );
    }

    $deleted = $wpdb->delete( $wpdb->prefix . 'docmgr_files', array( 'id' => $file_id ), array( '%d' ) );
    if ( false === $deleted || 0 === $deleted ) {
        wp_send_json_error( 'Failed to remove file.' );
    }

    wp_send_json_success();
});

/* ── Reorder Files ────────────────────────────── */
add_action( 'wp_ajax_docmgr_reorder_files', function () {
    docmgr_verify_ajax();
    global $wpdb;
    $order = isset( $_POST['order'] ) ? array_map( 'absint', (array) $_POST['order'] ) : array();
    if ( empty( $order ) ) {
        wp_send_json_error( 'No order data.' );
    }

    if ( count( array_unique( $order ) ) !== count( $order ) ) {
        wp_send_json_error( 'Duplicate file IDs in order data.' );
    }

    foreach ( $order as $position => $file_id ) {
        if ( ! docmgr_get_file( $file_id ) ) {
            wp_send_json_error( 'One or more files no longer exist.' );
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'docmgr_files',
            array( 'sort_order' => $position ),
            array( 'id' => $file_id ),
            array( '%d' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            wp_send_json_error( 'Failed to reorder files.' );
        }
    }
    wp_send_json_success();
});

/* ════════════════════════════════════════════════════════════════════════════
   SHORTCODE
   ════════════════════════════════════════════════════════════════════════════ */

add_shortcode( 'doc_group', function ( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'doc_group' );
    $group_id = absint( $atts['id'] );
    if ( ! $group_id ) {
        return '';
    }

    global $wpdb;
    $files_table  = $wpdb->prefix . 'docmgr_files';
    $groups_table = $wpdb->prefix . 'docmgr_groups';

    $group = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$groups_table} WHERE id = %d", $group_id
    ) );
    if ( ! $group ) {
        return '';
    }

    $files = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$files_table} WHERE group_id = %d ORDER BY sort_order ASC",
        $group_id
    ) );

    if ( empty( $files ) ) {
        return '<div class="docmgr-group"><p>No documents available.</p></div>';
    }

    $icon_map = array(
        'pdf'  => '📕',
        'doc'  => '📘',
        'docx' => '📘',
        'xls'  => '📗',
        'xlsx' => '📗',
        'ppt'  => '📙',
        'pptx' => '📙',
        'zip'  => '🗜️',
        'rar'  => '🗜️',
        'jpg'  => '🖼️',
        'jpeg' => '🖼️',
        'png'  => '🖼️',
        'gif'  => '🖼️',
        'svg'  => '🖼️',
        'mp4'  => '🎬',
        'mp3'  => '🎵',
        'txt'  => '📄',
        'csv'  => '📊',
    );

    $html = '<div class="docmgr-group">';
    $html .= '<ul class="docmgr-file-list">';

    foreach ( $files as $file ) {
        $url       = wp_get_attachment_url( $file->attachment_id );
        $file_path = get_attached_file( $file->attachment_id );
        $ext       = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
        $size      = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : '';
        $icon      = isset( $icon_map[ $ext ] ) ? $icon_map[ $ext ] : '📄';
        $name      = esc_html( $file->display_name );

        $html .= '<li class="docmgr-file-item">';
        $html .= '<span class="docmgr-file-icon">' . $icon . '</span>';
        $html .= '<a class="docmgr-file-link" href="' . esc_url( $url ) . '" target="_blank" download>';
        $html .= $name;
        $html .= '</a>';
        if ( $size ) {
            $html .= '<span class="docmgr-file-size">' . esc_html( $size ) . '</span>';
        }
        $html .= '<span class="docmgr-file-ext">' . strtoupper( $ext ) . '</span>';
        $html .= '</li>';
    }

    $html .= '</ul>';
    $html .= '</div>';

    return $html;
});
