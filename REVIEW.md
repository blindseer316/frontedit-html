# Review Notes — FrontEdit HTML

This file is for human review of what changed and why. See CHANGELOG.md for the version-by-version technical log.

## 2026-09-07 — Cursor and toolbar visibility polish (v1.2.1)

**Changed**
- Editable images now show a pointer cursor on hover instead of a text caret, matching their actual click behavior (opens the media picker).
- The floating Save/Exit toolbar now has a visible border and stronger shadow so it doesn't visually melt into the page behind it.

**Why:** Direct feedback while testing the image feature and general edit-mode UI.

## 2026-09-07 — Fix silent ID drift on emptied elements, add image editing (v1.2.0)

**Changed**
- Editable-element eligibility is now purely structural (tag + nesting position), never based on whether the element currently has text. The old "skip if empty" check was meant to avoid tagging decorative empty elements, but it meant deleting all of an element's text and saving made it permanently un-clickable afterward, and — more seriously — shifted the occurrence-based numbering of every later sibling of the same tag in that block, so a save could silently land on the wrong element. This was reported directly: "I deleted an entire text, saved it, then visited that deleted element again... it's not editable anymore."

**Added**
- Image editing. An eligible `<img>` inside a Custom HTML block can now be clicked in edit mode to open the WordPress media library and swap in a different image. Restricted to users who can already upload media (`upload_files`), checked both when deciding what to mark editable and again on save. The server also verifies the submitted image URL resolves to a real attachment in this site's library before writing it into the page — an arbitrary external URL is rejected.

**Reverted / removed**
- N/A.

**Known limitation:** swapping an image doesn't currently update `width`/`height` attributes to match the new image's actual dimensions, and drops `srcset`/`sizes` outright rather than regenerating them for the new attachment. In most modern layouts (flexible containers, `object-fit: cover`, etc.) this is invisible, but a page relying on exact fixed image dimensions could see a shape mismatch until that's addressed in a future version.

**Why:** Direct feedback from continued front-end testing — the emptied-element bug was a correctness issue worth fixing immediately; image editing was a feature request to extend the plugin beyond text.

## 2026-09-07 — First real-site feedback: contrast fix + popup/navigation guard (v1.1.0)

**Changed**
- Removed the `background-color` tint from the hover/focus highlight and replaced it with a `box-shadow` ring. The tint was washing out buttons that already had their own background/ghost styling (reported: a phone-number CTA button turned white on hover).

**Added**
- A capture-phase click guard, active only while edit mode is on: clicking an editable link or button no longer navigates the page or fires a bound popup/lightbox script — it just lets you place your cursor to edit the text. Ctrl/Cmd-click bypasses the guard so the real link/popup can still be triggered on purpose.

**Reverted / removed**
- N/A.

**Known limitation confirmed by testing:** this plugin only understands Custom HTML (`core/html`) blocks. A page built entirely from native Gutenberg blocks shows no Edit Page button at all, by design — those blocks have their own editing paths elsewhere. Extending this plugin to also cover simple native blocks (paragraph, heading) is possible later since their rendered output is plain HTML too, but block attribute round-tripping is a different code path than the Custom HTML block case and hasn't been scoped yet.

**Why:** Direct feedback from testing the plugin live for the first time — no crash, but the hover/focus overlay clashed with an existing button's ghost styling, and a separate concern was raised about editable buttons wired to a popup script firing unintentionally while trying to edit their text.

## 2026-09-07 — Moved edit button off the admin bar (v1.0.1)

**Changed**
- The "Edit Page" trigger is no longer a WordPress admin bar item. It's now a standalone button the plugin injects via `wp_footer`, fixed to the vertical middle of the right edge of the page (`position: fixed; top: 50%; right: 0;`).

**Why:** Requested directly — the admin bar sits at the very top of the page and can be covered or crowded by floating widgets themes/plugins commonly place in page corners (chat bubbles, cookie banners, back-to-top buttons). Middle-right is out of the way of virtually all of those by convention.

## 2026-09-07 — Initial build (v1.0.0)

**Added**
- A full working plugin (`frontedit-html/`) implementing the architecture discussed in chat: front-end `contenteditable` editing of text inside Gutenberg Custom HTML blocks, saved back into the same block's raw HTML in `post_content`.
- Positional element IDs (`data-fe-id="{block}.{tag}.{occurrence}"`) computed identically at render time and save time — no ID mapping table, nothing persisted to the database. This directly addresses the "don't want permanent editor markup baked into the DOM" concern raised in chat: the attribute only appears in the HTML response sent to a logged-in user who can edit that specific post. Logged-out visitors and view-source always see the original, untouched HTML.
- Fragment-level `DOMDocument` parsing (isolate just the one Custom HTML block's raw HTML, operate on it in a throwaway wrapper `<div>`, with `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD` flags and a UTF-8 encoding shim) rather than parsing the whole post — this is the fix for the save errors mentioned from the earlier prototype, which were almost certainly DOMDocument re-wrapping content in `<html><body>` and/or mangling UTF-8 on save.
- Save-side sanitization via `wp_kses` with a small inline-tag whitelist (a/b/strong/i/em/br/span), so pasted or malformed markup from a client's contenteditable session can't inject arbitrary HTML into the page.
- Settings > FrontEdit HTML admin page displaying the version number and a short usage guide, and a versioned plugin header per house standards.

**Changed**
- N/A (first build).

**Reverted / removed**
- N/A (first build).

**Known limitations (MVP scope, worth revisiting later)**
- Editability rule for standalone tags (a/span/button) vs. block containers (p/li/h1-h6/etc.) is a heuristic: a link inside a paragraph is not separately editable (the paragraph owns it), but a standalone "button-style" link is. This covers the common case but may need tuning per real page structures.
- If the underlying HTML structure inside a Custom HTML block changes between when a client loads the edit view and when they save (e.g. someone else edits the page in the meantime), an individual changed element may be silently skipped rather than mis-saved — safer than guessing, but worth surfacing to the user in a future version.
- Only tested logically against `core/html` blocks at both top level and nested inside container blocks (e.g. Group blocks); real-world testing against an actual site is still needed before rolling this out to a client.
- No revision/undo history yet beyond WordPress's normal post revisions (which `wp_update_post` still triggers).

**Why:** This addresses the recurring problem across your client sites — pages built by pasting AI-generated HTML into the Gutenberg Custom HTML block aren't reachable by existing front-end block editors, since those only understand native Gutenberg blocks. This plugin gives clients a safe, contained way to edit that text themselves without needing access to Gutenberg or raw HTML.
