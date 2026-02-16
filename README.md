# Document Manager Plugin

WordPress plugin for organizing downloadable files into groups and rendering them on the frontend with a shortcode.

## Project Objectives

1. Provide a simple admin workflow to create and manage document groups.
2. Support bulk document onboarding through drag-and-drop and Media Library selection.
3. Let admins rename, reorder, and remove file associations without deleting Media Library assets.
4. Make group embedding straightforward via shortcode copy/paste.
5. Output a clean, responsive document list for site visitors.

## What It Does Today

- Adds a **Doc Manager** admin menu.
- Creates and manages unlimited document groups.
- Uploads files directly in the group editor (drag-and-drop + browse).
- Adds existing files from the WordPress Media Library.
- Supports inline rename and drag-to-reorder.
- Generates shortcode: `[doc_group id=X]`.
- Renders frontend document lists with icon, file size, and extension badge.

## Technical Scope

- Plugin bootstrap: `document-manager.php`
- Admin UI templates:
  - `admin/group-list.php`
  - `admin/group-edit.php`
- Admin assets:
  - `assets/js/admin.js`
  - `assets/css/admin.css`
- Frontend styles:
  - `assets/css/frontend.css`

### Data Model

On activation, the plugin creates:

- `{wp_prefix}docmgr_groups`
- `{wp_prefix}docmgr_files`

`docmgr_files` stores group-to-attachment relationships and sort order.  
Files remain in the WordPress Media Library even when removed from a group.

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Installation

1. Copy this plugin directory into `wp-content/plugins/`.
2. Activate **Document Manager** in the WordPress admin.
3. Open **Doc Manager** to create a group and add files.

## Usage

1. Create or edit a group from **Doc Manager**.
2. Upload files or add files from the Media Library.
3. Copy the generated shortcode.
4. Paste into a post/page:

```text
[doc_group id=1]
```

## Notes

- Group and file management actions are restricted to users with `manage_options`.
- AJAX actions are nonce-protected.
- Plugin metadata and WordPress.org-style plugin description are also available in `readme.txt`.
