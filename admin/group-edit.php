<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$groups_table = $wpdb->prefix . 'docmgr_groups';
$files_table  = $wpdb->prefix . 'docmgr_files';

$group_id = isset( $_GET['group_id'] ) ? absint( $_GET['group_id'] ) : 0;
$is_new   = ( ! $group_id );
$group    = null;
$files    = array();

if ( ! $is_new ) {
    $group = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE id = %d", $group_id ) );
    if ( ! $group ) {
        echo '<div class="notice notice-error"><p>Group not found.</p></div>';
        return;
    }

    $db_files = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$files_table} WHERE group_id = %d ORDER BY sort_order ASC",
        $group_id
    ) );

    foreach ( $db_files as $f ) {
        $file_path = get_attached_file( $f->attachment_id );
        $ext       = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
        $size      = file_exists( $file_path ) ? size_format( filesize( $file_path ) ) : '';
        $url       = wp_get_attachment_url( $f->attachment_id );

        $files[] = array(
            'id'            => $f->id,
            'attachment_id' => $f->attachment_id,
            'display_name'  => $f->display_name,
            'sort_order'    => $f->sort_order,
            'url'           => $url,
            'size'          => $size,
            'ext'           => $ext,
        );
    }
}

$shortcode = $group_id ? '[doc_group id=' . $group_id . ']' : '';
?>

<div class="wrap docmgr-wrap">
    <div class="docmgr-header">
        <h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=docmgr' ) ); ?>" class="docmgr-back-link">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>
            <?php echo $is_new ? 'Create New Group' : 'Edit Group'; ?>
        </h1>
        <?php if ( ! $is_new ) : ?>
            <div class="docmgr-shortcode-wrap docmgr-header-shortcode">
                <code><?php echo esc_html( $shortcode ); ?></code>
                <button type="button" class="docmgr-copy-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>" title="Copy shortcode">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Group Name -->
    <div class="docmgr-card">
        <div class="docmgr-field-row">
            <label for="docmgr-group-name" class="docmgr-label">Group Name</label>
            <div class="docmgr-field-input-wrap">
                <input type="text"
                       id="docmgr-group-name"
                       class="regular-text"
                       value="<?php echo $group ? esc_attr( $group->name ) : ''; ?>"
                       placeholder="Enter group name..."
                       autofocus>
                <button type="button" id="docmgr-save-group" class="docmgr-btn-primary" data-group-id="<?php echo $group_id; ?>">
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo $is_new ? 'Create Group' : 'Save Name'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Upload + File List (only show for existing groups) -->
    <div id="docmgr-files-section" class="<?php echo $is_new ? 'docmgr-hidden' : ''; ?>">

        <!-- Dropzone -->
        <div class="docmgr-card">
            <h2 class="docmgr-card-title">
                <span class="dashicons dashicons-upload"></span> Upload Documents
            </h2>
            <div id="docmgr-dropzone" class="docmgr-dropzone">
                <div class="docmgr-dropzone-content">
                    <span class="dashicons dashicons-cloud-upload docmgr-dropzone-icon"></span>
                    <p class="docmgr-dropzone-text">Drag &amp; drop files here</p>
                    <p class="docmgr-dropzone-hint">or</p>
                    <div class="docmgr-dropzone-buttons">
                        <button type="button" id="docmgr-browse-btn" class="docmgr-btn-secondary">
                            <span class="dashicons dashicons-media-default"></span> Browse Files
                        </button>
                        <button type="button" id="docmgr-media-btn" class="docmgr-btn-secondary">
                            <span class="dashicons dashicons-admin-media"></span> Media Library
                        </button>
                    </div>
                </div>
                <div id="docmgr-upload-progress" class="docmgr-upload-progress docmgr-hidden">
                    <div class="docmgr-progress-bar">
                        <div class="docmgr-progress-fill" id="docmgr-progress-fill"></div>
                    </div>
                    <p class="docmgr-progress-text" id="docmgr-progress-text">Uploading...</p>
                </div>
            </div>
            <input type="file" id="docmgr-file-input" multiple style="display:none;">
        </div>

        <!-- File list -->
        <div class="docmgr-card">
            <h2 class="docmgr-card-title">
                <span class="dashicons dashicons-list-view"></span>
                Documents
                <span class="docmgr-file-count" id="docmgr-file-count"><?php echo count( $files ); ?></span>
            </h2>

            <div id="docmgr-file-list" class="docmgr-file-list" data-group-id="<?php echo $group_id; ?>">
                <?php if ( empty( $files ) ) : ?>
                    <div class="docmgr-empty-files" id="docmgr-empty-files">
                        <span class="dashicons dashicons-media-document"></span>
                        <p>No documents yet. Upload some files above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="tmpl-docmgr-file-row">
    <div class="docmgr-file-row" data-file-id="{{data.id}}">
        <div class="docmgr-file-handle" title="Drag to reorder">
            <span class="dashicons dashicons-menu"></span>
        </div>
        <div class="docmgr-file-icon-wrap">
            <span class="docmgr-file-type-icon" data-ext="{{data.ext}}"></span>
        </div>
        <div class="docmgr-file-info">
            <div class="docmgr-file-name-wrap">
                <span class="docmgr-file-name-text">{{data.display_name}}</span>
                <input type="text" class="docmgr-file-name-input docmgr-hidden" value="{{data.display_name}}">
                <button type="button" class="docmgr-rename-btn" title="Rename">
                    <span class="dashicons dashicons-edit-page"></span>
                </button>
                <button type="button" class="docmgr-rename-save docmgr-hidden" title="Save name">
                    <span class="dashicons dashicons-yes-alt"></span>
                </button>
                <button type="button" class="docmgr-rename-cancel docmgr-hidden" title="Cancel">
                    <span class="dashicons dashicons-dismiss"></span>
                </button>
            </div>
            <div class="docmgr-file-meta">
                <span class="docmgr-file-ext-badge">{{data.ext}}</span>
                <span class="docmgr-file-size">{{data.size}}</span>
            </div>
        </div>
        <div class="docmgr-file-actions">
            <a href="{{data.url}}" class="docmgr-action-btn" title="Download" target="_blank" download>
                <span class="dashicons dashicons-download"></span>
            </a>
            <button type="button" class="docmgr-action-btn docmgr-remove-btn" title="Remove from group" data-file-id="{{data.id}}">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    </div>
</script>

<script>
    // Pass file data to JS
    var docmgrFiles = <?php echo json_encode( $files ); ?>;
</script>
