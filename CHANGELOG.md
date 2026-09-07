# Changelog

All notable changes to FrontEdit HTML are documented here.

## [1.2.1] - 2026-09-07

### Fixed
- Hovering an editable image showed a text-caret cursor (misleading, since clicking it opens the media picker, not text editing). Now shows a pointer cursor instead.
- The floating Save/Exit toolbar had no border or accent, so on some pages it visually blended into the page behind it. Added a visible border and a stronger, tinted shadow so it stands out regardless of the underlying page's colors.

## [1.2.0] - 2026-09-07

### Fixed
- Element eligibility no longer depends on whether the element currently has text content. Previously, emptying an element's text (e.g. deleting it entirely and saving) would exclude it from the next render's editable set — making it permanently un-editable — and silently shift the position-based numbering of every later sibling with the same tag, corrupting which element a subsequent save would target.

### Added
- Image editing: an `<img>` inside the Custom HTML block is now editable. Clicking it in edit mode opens the WordPress media library so a different image can be selected; saving updates only that image's `src`/`alt` and clears any stale `srcset`/`sizes` left over from the previous image. Gated behind the `upload_files` capability on both the render and save paths — users who can't upload media never see images marked as editable, and the server rejects an image edit from anyone lacking that capability even if attempted directly against the REST endpoint.
- Image swaps are restricted to attachments that actually exist in the site's media library (verified server-side via `attachment_url_to_postid`); an arbitrary external URL cannot be written into the page this way.

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
