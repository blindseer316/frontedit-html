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
			<p>By default: headings (h1&ndash;h6), paragraphs, list items, blockquotes, table cells, and standalone links/buttons/spans that aren't nested inside another editable element. Developers can adjust this with the <code>frontedit_html_editable_tags</code> filter.</p>

			<h2>What gets changed</h2>
			<p>Only the raw HTML inside the specific Custom HTML block on the page you're editing is touched. Nothing is rewritten anywhere else, and no editing markup is ever saved to the database or shown to visitors who aren't logged in.</p>

			<p style="color:#646970;"><em>FrontEdit HTML version <?php echo esc_html( FRONTEDIT_HTML_VERSION ); ?></em></p>
		</div>
		<?php
	}
}
