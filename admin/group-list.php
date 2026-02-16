<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$groups_table = $wpdb->prefix . 'docmgr_groups';
$files_table  = $wpdb->prefix . 'docmgr_files';

// Handle inline delete via GET (with nonce)
if ( isset( $_GET['delete_group'] ) && isset( $_GET['_wpnonce'] ) ) {
    if ( wp_verify_nonce( $_GET['_wpnonce'], 'docmgr_delete_group_' . absint( $_GET['delete_group'] ) ) ) {
        $del_id = absint( $_GET['delete_group'] );
        $wpdb->delete( $files_table, array( 'group_id' => $del_id ), array( '%d' ) );
        $wpdb->delete( $groups_table, array( 'id' => $del_id ), array( '%d' ) );
        echo '<div class="notice notice-success is-dismissible"><p>Group deleted.</p></div>';
    }
}

$groups = $wpdb->get_results(
    "SELECT g.*, (SELECT COUNT(*) FROM {$files_table} f WHERE f.group_id = g.id) AS file_count
     FROM {$groups_table} g ORDER BY g.created_at DESC"
);
?>

<div class="wrap docmgr-wrap">
    <div class="docmgr-header">
        <h1>
            <span class="dashicons dashicons-media-document"></span>
            Document Manager
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=docmgr&action=new' ) ); ?>" class="page-title-action docmgr-btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span> Add New Group
        </a>
    </div>

    <?php if ( empty( $groups ) ) : ?>
        <div class="docmgr-empty-state">
            <div class="docmgr-empty-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <h2>No Document Groups Yet</h2>
            <p>Create your first group to start uploading and organising documents.</p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=docmgr&action=new' ) ); ?>" class="docmgr-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span> Create Your First Group
            </a>
        </div>
    <?php else : ?>
        <div class="docmgr-table-wrap">
            <table class="wp-list-table widefat fixed striped docmgr-table">
                <thead>
                    <tr>
                        <th class="column-name">Group Name</th>
                        <th class="column-count">Files</th>
                        <th class="column-shortcode">Shortcode</th>
                        <th class="column-date">Created</th>
                        <th class="column-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $groups as $group ) :
                        $edit_url   = admin_url( 'admin.php?page=docmgr&action=edit&group_id=' . $group->id );
                        $delete_url = wp_nonce_url(
                            admin_url( 'admin.php?page=docmgr&delete_group=' . $group->id ),
                            'docmgr_delete_group_' . $group->id
                        );
                        $shortcode = '[doc_group id=' . $group->id . ']';
                    ?>
                    <tr>
                        <td class="column-name">
                            <strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $group->name ); ?></a></strong>
                        </td>
                        <td class="column-count">
                            <span class="docmgr-badge"><?php echo intval( $group->file_count ); ?></span>
                        </td>
                        <td class="column-shortcode">
                            <div class="docmgr-shortcode-wrap">
                                <code id="shortcode-<?php echo $group->id; ?>"><?php echo esc_html( $shortcode ); ?></code>
                                <button type="button" class="docmgr-copy-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>" title="Copy shortcode">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </td>
                        <td class="column-date">
                            <?php echo date_i18n( get_option( 'date_format' ), strtotime( $group->created_at ) ); ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="docmgr-action-btn docmgr-action-edit" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <a href="<?php echo esc_url( $delete_url ); ?>"
                               class="docmgr-action-btn docmgr-action-delete"
                               title="Delete"
                               onclick="return confirm('Delete this group and remove all file associations? The files will remain in your Media Library.');">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
