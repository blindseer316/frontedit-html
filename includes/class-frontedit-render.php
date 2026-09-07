<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects data-fe-id attributes into rendered core/html block output — but
 * ONLY for the current request, and ONLY when the viewer can edit the post.
 * Nothing is ever written back to post_content here, so anonymous/public
 * visitors and view-source always see the original, untouched markup.
 */
class FrontEdit_HTML_Render {

	private static $counters = array(); // post_id => next core/html block order index for this request.

	public static function init() {
		add_filter( 'render_block_core/html', array( __CLASS__, 'maybe_tag_block' ), 10, 2 );
	}

	public static function maybe_tag_block( $block_content, $block ) {
		if ( is_admin() || ! is_singular() ) {
			return $block_content;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $block_content;
		}

		if ( ! isset( self::$counters[ $post->ID ] ) ) {
			self::$counters[ $post->ID ] = 0;
		}
		$order = self::$counters[ $post->ID ]++;

		list( $dom, $root ) = FrontEdit_HTML_Core::load_fragment( $block_content );
		if ( ! $root ) {
			return $block_content;
		}

		$nodes = FrontEdit_HTML_Core::collect_editable_nodes( $root, $order, current_user_can( 'upload_files' ) );
		if ( empty( $nodes ) ) {
			return $block_content;
		}

		foreach ( $nodes as $fe_id => $node ) {
			$node->setAttribute( 'data-fe-id', $fe_id );
		}

		return FrontEdit_HTML_Core::fragment_inner_html( $dom, $root );
	}
}
