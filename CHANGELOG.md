# Changelog

All notable changes to FrontEdit HTML are documented here.

## [1.0.1] - 2026-09-07

### Changed
- Replaced the WordPress admin bar toggle with a standalone floating "Edit Page" launcher button, fixed to the vertical middle of the right edge of the viewport instead of the top of the page. Avoids collisions with the WP admin bar and with theme/plugin floating widgets (chat bubbles, back-to-top buttons, etc.) that tend to live in page corners.
- Launcher hides itself while the edit toolbar is open, since the toolbar already provides an Exit control.

## [1.0.0] - 2026-09-07

### Added
- Initial release.
- Admin bar "Edit Page Text" toggle for users who can edit the current post.
- Render-time injection of `data-fe-id` attributes into Custom HTML (`core/html`) block output, scoped to the current request and only for users who can edit the post — never persisted to `post_content`, never shown to logged-out visitors.
- Front-end `contenteditable` editing layer (`assets/js/frontedit-editor.js`) with plain-text paste sanitization and a floating Save/Exit toolbar.
- REST endpoint `POST /wp-json/frontedit/v1/save` that patches only the changed elements inside the relevant Custom HTML block, using PHP `DOMDocument` fragment parsing (isolated per-block, not whole-post) to avoid the encoding/wrapper corruption issues that plagued earlier prototypes.
- Settings > FrontEdit HTML admin page showing current version and usage instructions.
- `frontedit_html_editable_tags` and `frontedit_html_allowed_inline_html` filters for customizing which elements are editable and what inline markup survives sanitization on save.
