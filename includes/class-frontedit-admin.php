<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrontEdit_HTML_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu() {
		add_options_page(
			'FrontEdit HTML',
			'FrontEdit HTML',
			'manage_options',
			'frontedit-html',
			array( __CLASS__, 'render' )
		);
	}

	public static function render() {
		?>
		<div class="wrap">
			<h1>
				FrontEdit HTML
				<span style="font-size:14px;font-weight:400;color:#646970;">
					v<?php echo esc_html( FRONTEDIT_HTML_VERSION ); ?>
				</span>
			</h1>

			<p>FrontEdit HTML lets logged-in editors update text inside Custom HTML blocks directly from the front end — no block editor required.</p>

			<h2>How it works</h2>
			<ol>
				<li>Open any front-end page or post containing a Custom HTML block, while logged in as a user who can edit it.</li>
				<li>Click the <strong>✏️ Edit Page</strong> button fixed to the middle-right edge of the page.</li>
				<li>Click into any highlighted text, edit it, then click <strong>Save Changes</strong>.</li>
			</ol>

			<h2>Editable elements</h2>
			<p>By default: headings (h1&ndash;h6), paragraphs, list items, blockquotes, table cells, standalone links/buttons/spans that aren't nested inside another editable element, and images. Developers can adjust this with the <code>frontedit_html_editable_tags</code> filter.</p>

			<h2>Images</h2>
			<p>Clicking an editable image opens the WordPress media library so you can swap in a different image from this site's media, without touching layout or surrounding markup. Only available to users who can already upload media.</p>

			<h2>Links, buttons, and popups</h2>
			<p>While in edit mode, clicking a link or button that's part of the editable content won't navigate away or trigger its normal behavior (e.g. a popup script) — it just places your cursor so you can edit the text. Hold <strong>Ctrl</strong> (<strong>&#8984;</strong> on Mac) while clicking to let that click through as normal, if you need to test the actual link or popup.</p>

			<h2>Custom HTML blocks only</h2>
			<p>This only works on <strong>Custom HTML</strong> Gutenberg blocks. Pages built entirely from native Gutenberg blocks (paragraph, heading, buttons, etc.) won't show the Edit Page button — those blocks already have their own editing path through Gutenberg or other front-end block editors.</p>

			<h2>What gets changed</h2>
			<p>Only the raw HTML inside the specific Custom HTML block on the page you're editing is touched. Nothing is rewritten anywhere else, and no editing markup is ever saved to the database or shown to visitors who aren't logged in.</p>

			<p style="color:#646970;"><em>FrontEdit HTML version <?php echo esc_html( FRONTEDIT_HTML_VERSION ); ?></em></p>
		</div>
		<?php
	}
}
