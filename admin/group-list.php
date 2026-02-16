<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$groups_table = $wpdb->prefix . 'docmgr_groups';
$files_table  = $wpdb->prefix . 'docmgr_files';

if ( isset( $_GET['docmgr_notice'] ) ) {
    $notice = sanitize_key( wp_unslash( $_GET['docmgr_notice'] ) );
    if ( 'group_deleted' === $notice ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Group deleted.', 'document-manager' ) . '</p></div>';
    } elseif ( 'group_delete_failed' === $notice ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unable to delete group.', 'document-manager' ) . '</p></div>';
    } elseif ( 'group_not_found' === $notice ) {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Group no longer exists.', 'document-manager' ) . '</p></div>';
    } elseif ( 'invalid_group' === $notice ) {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Invalid group request.', 'document-manager' ) . '</p></div>';
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
            <?php esc_html_e( 'Document Manager', 'document-manager' ); ?>
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=docmgr&action=new' ) ); ?>" class="page-title-action docmgr-btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Group', 'document-manager' ); ?>
        </a>
    </div>

    <?php if ( empty( $groups ) ) : ?>
        <div class="docmgr-empty-state">
            <div class="docmgr-empty-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <h2><?php esc_html_e( 'No Document Groups Yet', 'document-manager' ); ?></h2>
            <p><?php esc_html_e( 'Create your first group to start uploading and organising documents.', 'document-manager' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=docmgr&action=new' ) ); ?>" class="docmgr-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Create Your First Group', 'document-manager' ); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="docmgr-table-wrap">
            <table class="wp-list-table widefat fixed striped docmgr-table">
                <thead>
                    <tr>
                        <th class="column-name"><?php esc_html_e( 'Group Name', 'document-manager' ); ?></th>
                        <th class="column-count"><?php esc_html_e( 'Files', 'document-manager' ); ?></th>
                        <th class="column-shortcode"><?php esc_html_e( 'Shortcode', 'document-manager' ); ?></th>
                        <th class="column-date"><?php esc_html_e( 'Created', 'document-manager' ); ?></th>
                        <th class="column-actions"><?php esc_html_e( 'Actions', 'document-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $groups as $group ) :
                        $edit_url   = admin_url( 'admin.php?page=docmgr&action=edit&group_id=' . $group->id );
                        $shortcode = '[doc_group id=' . $group->id . ']';
                    ?>
                    <tr>
                        <td class="column-name">
                            <strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $group->name ); ?></a></strong>
                        </td>
                        <td class="column-count">
                            <span class="docmgr-badge"><?php echo esc_html( absint( $group->file_count ) ); ?></span>
                        </td>
                        <td class="column-shortcode">
                            <div class="docmgr-shortcode-wrap">
                                <code id="shortcode-<?php echo esc_attr( $group->id ); ?>"><?php echo esc_html( $shortcode ); ?></code>
                                <button type="button" class="docmgr-copy-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>" title="<?php echo esc_attr__( 'Copy shortcode', 'document-manager' ); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $group->created_at ) ) ); ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="docmgr-action-btn docmgr-action-edit" title="<?php echo esc_attr__( 'Edit', 'document-manager' ); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="docmgr-action-form">
                                <?php wp_nonce_field( 'docmgr_delete_group' ); ?>
                                <input type="hidden" name="action" value="docmgr_delete_group">
                                <input type="hidden" name="group_id" value="<?php echo esc_attr( $group->id ); ?>">
                                <button type="submit"
                                        class="docmgr-action-btn docmgr-action-delete"
                                        title="<?php echo esc_attr__( 'Delete', 'document-manager' ); ?>"
                                        onclick="return confirm('<?php echo esc_js( __( 'Delete this group and remove all file associations? The files will remain in your Media Library.', 'document-manager' ) ); ?>');">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
