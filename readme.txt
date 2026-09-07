=== FrontEdit HTML ===
Contributors: earthbreakdesigns
Tags: front-end editing, custom html, gutenberg, content editing
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let logged-in editors update text inside Custom HTML (Gutenberg) blocks directly from the front end.

== Description ==

FrontEdit HTML solves a specific problem: pages built by pasting raw HTML into Gutenberg's Custom HTML block aren't editable through normal block-based front-end editors, because there's no block structure for them to hook into.

FrontEdit HTML adds a lightweight editing layer instead. Logged-in users who can edit a page see an "Edit Page" button fixed to the middle-right edge of the page (deliberately not in the WordPress admin bar, so it never gets hidden behind it or behind a theme's own floating widgets). Clicking it makes headings, paragraphs, list items, and similar text elements editable in place, right on the live page. Saving patches only the relevant text inside the original Custom HTML block — the rest of the markup is untouched.

Manage it from **Settings > FrontEdit HTML**.

No editing markup is ever written to the database or shown to logged-out visitors — attributes used to track editable elements are injected only into the response sent to users who are permitted to edit that page.

== Installation ==

1. Upload the `frontedit-html` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Visit **Settings > FrontEdit HTML** to confirm it's active and see usage notes.

== Changelog ==

See CHANGELOG.md in the plugin's repository/source folder.
