<?php
/**
 * Plugin Name: FrontEdit HTML
 * Plugin URI:  https://earthbreakdesigns.com
 * Description: Lets logged-in editors update text inside Custom HTML (Gutenberg) blocks directly from the front end, without touching the block editor. Manage it from Settings > FrontEdit HTML.
 * Version:     1.0.1
 * Author:      Earthbreakdesigns.com
 * Author URI:  https://earthbreakdesigns.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: frontedit-html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FRONTEDIT_HTML_VERSION', '1.0.1' );
define( 'FRONTEDIT_HTML_FILE', __FILE__ );
define( 'FRONTEDIT_HTML_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRONTEDIT_HTML_URL', plugin_dir_url( __FILE__ ) );

require_once FRONTEDIT_HTML_DIR . 'includes/class-frontedit-core.php';
require_once FRONTEDIT_HTML_DIR . 'includes/class-frontedit-render.php';
require_once FRONTEDIT_HTML_DIR . 'includes/class-frontedit-rest.php';
require_once FRONTEDIT_HTML_DIR . 'includes/class-frontedit-admin.php';

final class FrontEdit_HTML {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		FrontEdit_HTML_Render::init();
		FrontEdit_HTML_REST::init();
		FrontEdit_HTML_Admin::init();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_launcher' ) );
	}

	private function current_viewer_can_edit() {
		if ( is_admin() || ! is_singular() ) {
			return false;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}

		return FrontEdit_HTML_Core::post_has_html_block( $post );
	}

	/**
	 * A fixed launcher button, not an admin bar item — kept off the top of the
	 * page (mid-right instead) so it never gets hidden behind the admin bar or
	 * a theme's own floating widgets (chat bubbles, "back to top", etc.).
	 */
	public function render_launcher() {
		if ( ! $this->current_viewer_can_edit() ) {
			return;
		}
		?>
		<button type="button" id="frontedit-launcher" aria-label="Edit page text">✏️ Edit Page</button>
		<?php
	}

	public function enqueue_assets() {
		if ( ! $this->current_viewer_can_edit() ) {
			return;
		}

		$post = get_queried_object();

		wp_enqueue_style( 'frontedit-html', FRONTEDIT_HTML_URL . 'assets/css/frontedit-editor.css', array(), FRONTEDIT_HTML_VERSION );
		wp_enqueue_script( 'frontedit-html', FRONTEDIT_HTML_URL . 'assets/js/frontedit-editor.js', array(), FRONTEDIT_HTML_VERSION, true );
		wp_localize_script(
			'frontedit-html',
			'FrontEditHTML',
			array(
				'restUrl' => esc_url_raw( rest_url( 'frontedit/v1/save' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'postId'  => $post->ID,
			)
		);
	}
}

add_action( 'plugins_loaded', array( 'FrontEdit_HTML', 'instance' ) );

register_deactivation_hook( __FILE__, function () {
	// Nothing to clean up on deactivation; per-post meta flags are harmless and self-heal on next load.
} );
