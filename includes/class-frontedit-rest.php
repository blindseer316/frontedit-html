<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrontEdit_HTML_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'frontedit/v1',
			'/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
				'args'                => array(
					'post_id' => array( 'required' => true ),
					'changes' => array( 'required' => true ),
				),
			)
		);
	}

	public static function permission_check( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		if ( ! $post_id ) {
			return false;
		}
		return current_user_can( 'edit_post', $post_id );
	}

	public static function save( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$changes = $request->get_param( 'changes' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'frontedit_no_post', 'Post not found.', array( 'status' => 404 ) );
		}
		if ( ! is_array( $changes ) || empty( $changes ) ) {
			return new WP_Error( 'frontedit_no_changes', 'No changes supplied.', array( 'status' => 400 ) );
		}

		$by_block = self::group_changes_by_block( $changes );
		if ( empty( $by_block ) ) {
			return new WP_Error( 'frontedit_bad_changes', 'Changes could not be parsed.', array( 'status' => 400 ) );
		}

		$blocks = parse_blocks( $post->post_content );

		foreach ( $by_block as $order => $edits ) {
			$html_block = FrontEdit_HTML_Core::find_html_block( $blocks, $order );
			if ( null === $html_block ) {
				continue;
			}

			list( $dom, $root ) = FrontEdit_HTML_Core::load_fragment( $html_block['innerHTML'] );
			if ( ! $root ) {
				continue;
			}

			$nodes = FrontEdit_HTML_Core::collect_editable_nodes( $root, $order, current_user_can( 'upload_files' ) );

			foreach ( $edits as $edit ) {
				$fe_id = $order . '.' . $edit['tag'] . '.' . $edit['occurrence'];
				if ( ! isset( $nodes[ $fe_id ] ) ) {
					continue; // structure shifted since the page was loaded — skip rather than guess.
				}

				if ( 'image' === $edit['type'] ) {
					if ( ! current_user_can( 'upload_files' ) ) {
						continue;
					}
					self::replace_image( $nodes[ $fe_id ], $edit['src'], $edit['alt'] );
				} else {
					self::replace_node_contents( $dom, $nodes[ $fe_id ], $edit['html'] );
				}
			}

			$new_inner_html = FrontEdit_HTML_Core::fragment_inner_html( $dom, $root );
			$blocks         = FrontEdit_HTML_Core::set_html_block_content_by_order( $blocks, $order, $new_inner_html );
		}

		$new_content = serialize_blocks( $blocks );

		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		return array( 'success' => true );
	}

	private static function group_changes_by_block( array $changes ) {
		$by_block = array();

		foreach ( $changes as $change ) {
			if ( empty( $change['fe_id'] ) ) {
				continue;
			}

			$parts = explode( '.', $change['fe_id'] );
			if ( 3 !== count( $parts ) ) {
				continue;
			}

			list( $order, $tag, $occurrence ) = $parts;
			$type = isset( $change['type'] ) ? $change['type'] : 'text';

			if ( 'image' === $type ) {
				if ( ! isset( $change['src'] ) ) {
					continue;
				}
				$by_block[ (int) $order ][] = array(
					'tag'        => sanitize_key( $tag ),
					'occurrence' => (int) $occurrence,
					'type'       => 'image',
					'src'        => esc_url_raw( wp_unslash( $change['src'] ) ),
					'alt'        => isset( $change['alt'] ) ? sanitize_text_field( wp_unslash( $change['alt'] ) ) : '',
				);
			} else {
				if ( ! isset( $change['html'] ) ) {
					continue;
				}
				$by_block[ (int) $order ][] = array(
					'tag'        => sanitize_key( $tag ),
					'occurrence' => (int) $occurrence,
					'type'       => 'text',
					'html'       => FrontEdit_HTML_Core::sanitize_inline_html( wp_unslash( $change['html'] ) ),
				);
			}
		}

		return $by_block;
	}

	/**
	 * Only ever points an <img> at a real attachment in this site's media
	 * library — never at an arbitrary URL — and drops any srcset/sizes,
	 * which would otherwise keep referencing the old image's generated sizes.
	 */
	private static function replace_image( DOMElement $node, $src, $alt ) {
		$attachment_id = attachment_url_to_postid( $src );
		if ( ! $attachment_id ) {
			return;
		}

		$node->setAttribute( 'src', $src );

		if ( '' !== $alt ) {
			$node->setAttribute( 'alt', $alt );
		} else {
			$node->removeAttribute( 'alt' );
		}

		$node->removeAttribute( 'srcset' );
		$node->removeAttribute( 'sizes' );
	}

	private static function replace_node_contents( DOMDocument $dom, DOMElement $node, $new_html ) {
		while ( $node->firstChild ) {
			$node->removeChild( $node->firstChild );
		}

		list( $tmp_dom, $tmp_root ) = FrontEdit_HTML_Core::load_fragment( $new_html );
		if ( ! $tmp_root ) {
			return;
		}

		foreach ( $tmp_root->childNodes as $child ) {
			$node->appendChild( $dom->importNode( $child, true ) );
		}
	}
}
