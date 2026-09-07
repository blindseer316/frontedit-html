# Changelog

All notable changes to FrontEdit HTML are documented here.

## [1.1.0] - 2026-09-07

### Fixed
- Edit-mode hover/focus highlighting no longer paints a background tint over elements, which was washing out buttons that use their own background color (e.g. a ghost-style CTA turning white on hover). Replaced with a non-destructive `box-shadow` ring that never touches the element's own colors.

### Added
- While in edit mode, clicking a link or button that's part of the editable content no longer triggers its normal behavior (page navigation, or a popup/lightbox script bound to that click) — the click is suppressed in the capture phase so it can't reach the site's own handlers, letting you safely click in to edit the text. Holding Ctrl/Cmd while clicking lets the click through normally, for testing the real link or popup on purpose.

### Documentation
- Settings > FrontEdit HTML now explains the Ctrl/Cmd-click behavior and clarifies that only Custom HTML blocks are supported (not native Gutenberg blocks).

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
