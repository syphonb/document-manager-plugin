=== Document Manager ===
Contributors: custom
Tags: documents, download, file manager, shortcode, groups
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Organize documents into groups with drag-and-drop upload and display them anywhere via shortcode.

== Description ==

Document Manager is built to make document publishing simple for admins and clean for visitors.

Project objectives:

* Create and manage document groups from one admin screen.
* Add files quickly through drag-and-drop upload or Media Library selection.
* Control document naming and order before publishing.
* Embed document lists with a shortcode.
* Keep frontend output lightweight and readable.

Current features:

* Unlimited groups
* Drag-and-drop bulk upload
* Browse file picker and Media Library integration
* Inline rename for group file labels
* Drag-to-reorder documents
* One-click shortcode copy
* Responsive frontend document list
* File icon, extension, and size display

Permissions and safety:

* Group/file management requires `manage_options`.
* AJAX actions are nonce-protected.
* Removing files from a group does not delete the media file itself.

== Installation ==

1. Upload the `document-manager-plugin` folder to `/wp-content/plugins/`.
2. Activate **Document Manager** in the WordPress admin.
3. Go to **Doc Manager** in the admin sidebar and create a group.
4. Upload files or add existing items from the Media Library.
5. Copy the shortcode and place it in a page or post.

== Usage ==

Use:

`[doc_group id=1]`

Replace `1` with your actual group ID.

== Frequently Asked Questions ==

= Does removing a file from a group delete it from Media Library? =

No. It only removes the file association from that document group.

= Who can manage document groups? =

Users with the `manage_options` capability (typically administrators).

== Changelog ==

= 1.0.0 =
* Initial release with group management, uploads, sorting, renaming, and shortcode rendering.
