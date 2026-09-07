<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared logic used by both the front-end renderer and the REST save handler.
 * Both sides MUST walk the same fragment the same way, or fe_id numbering
 * drifts between what the browser sees and what gets saved.
 */
class FrontEdit_HTML_Core {

	// Elements that behave as text "containers" — never nest one inside another.
	const BLOCK_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'li', 'blockquote', 'figcaption', 'td', 'th', 'dt', 'dd' );

	// Only made editable on their own when NOT nested inside one of the block tags above
	// (e.g. a standalone "button-style" link, but not a link inside a paragraph).
	const STANDALONE_TAGS = array( 'a', 'span', 'button' );

	// Swapped via the media library rather than made contenteditable — always
	// eligible regardless of nesting, since an <img> can't contain other
	// editable elements and has no text content to check.
	const MEDIA_TAGS = array( 'img' );

	public static function editable_tags( $include_media = true ) {
		$tags = array_merge( self::BLOCK_TAGS, self::STANDALONE_TAGS );
		if ( $include_media ) {
			$tags = array_merge( $tags, self::MEDIA_TAGS );
		}
		return apply_filters( 'frontedit_html_editable_tags', $tags, $include_media );
	}

	public static function allowed_inline_html() {
		return apply_filters(
			'frontedit_html_allowed_inline_html',
			array(
				'a'      => array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
					'title'  => true,
				),
				'b'      => array(),
				'strong' => array(),
				'i'      => array(),
				'em'     => array(),
				'br'     => array(),
				'span'   => array( 'class' => true ),
			)
		);
	}

	public static function sanitize_inline_html( $html ) {
		return wp_kses( $html, self::allowed_inline_html() );
	}

	/**
	 * Cheap cached check so the admin bar button / asset loader don't parse
	 * blocks on every single request for pages that never use core/html.
	 */
	public static function post_has_html_block( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$cached = get_post_meta( $post->ID, '_frontedit_has_html_block', true );
		if ( '' !== $cached ) {
			return (bool) $cached;
		}

		$has = self::contains_html_block( parse_blocks( $post->post_content ) );
		update_post_meta( $post->ID, '_frontedit_has_html_block', $has ? '1' : '0' );

		return $has;
	}

	private static function contains_html_block( array $blocks ) {
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && 'core/html' === $block['blockName'] ) {
				return true;
			}
			if ( ! empty( $block['innerBlocks'] ) && self::contains_html_block( $block['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Parse an HTML fragment into a DOMDocument wrapped in a throwaway container,
	 * preserving UTF-8 and avoiding DOMDocument's implied <html><body> wrapper
	 * (the #1 cause of mangled markup on save).
	 *
	 * @return array [ DOMDocument $dom, DOMElement|null $root ]
	 */
	public static function load_fragment( $html ) {
		$dom     = new DOMDocument( '1.0', 'UTF-8' );
		$wrapped = '<div id="frontedit-fragment-root">' . $html . '</div>';

		$prev_state = libxml_use_internal_errors( true );
		$dom->loadHTML(
			'<?xml encoding="UTF-8">' . $wrapped,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_state );

		$root = $dom->getElementById( 'frontedit-fragment-root' );

		return array( $dom, $root );
	}

	public static function fragment_inner_html( DOMDocument $dom, DOMElement $node ) {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}
		return $html;
	}

	/**
	 * Walk the fragment root in document order and return an ordered map of
	 * fe_id => DOMElement for every eligible editable element.
	 *
	 * fe_id format: "{block_order}.{tag}.{occurrence}" — purely positional,
	 * computed identically on render and on save. Nothing is persisted to
	 * post_content or post meta to produce it.
	 *
	 * Eligibility is structural only (tag + nesting) and deliberately does
	 * NOT depend on whether the element currently has text content: skipping
	 * emptied elements would both make them permanently un-re-editable and
	 * shift the occurrence numbering of every later sibling with the same
	 * tag, silently corrupting fe_id matching on the next save.
	 */
	public static function collect_editable_nodes( DOMElement $root, $block_order, $include_media = true ) {
		$tags   = self::editable_tags( $include_media );
		$counts = array();
		$result = array();

		$xpath = new DOMXPath( $root->ownerDocument );
		$nodes = $xpath->query( './/*', $root );

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$tag = strtolower( $node->tagName );
			if ( ! in_array( $tag, $tags, true ) ) {
				continue;
			}

			if ( ! in_array( $tag, self::MEDIA_TAGS, true ) ) {
				if ( in_array( $tag, self::STANDALONE_TAGS, true ) && self::has_block_ancestor( $node, $root ) ) {
					continue; // e.g. a link inside a <p> — the <p> owns editing, not the link.
				}

				if ( in_array( $tag, self::BLOCK_TAGS, true ) && self::has_editable_descendant( $node ) ) {
					continue; // avoid nested block containers both being editable at once.
				}
			}

			if ( ! isset( $counts[ $tag ] ) ) {
				$counts[ $tag ] = 0;
			}
			$occurrence = $counts[ $tag ]++;

			$result[ $block_order . '.' . $tag . '.' . $occurrence ] = $node;
		}

		return $result;
	}

	private static function has_block_ancestor( DOMElement $node, DOMElement $root ) {
		$parent = $node->parentNode;
		while ( $parent instanceof DOMElement && $parent !== $root ) {
			if ( in_array( strtolower( $parent->tagName ), self::BLOCK_TAGS, true ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	private static function has_editable_descendant( DOMElement $node ) {
		foreach ( $node->childNodes as $child ) {
			if ( $child instanceof DOMElement ) {
				if ( in_array( strtolower( $child->tagName ), self::BLOCK_TAGS, true ) ) {
					return true;
				}
				if ( self::has_editable_descendant( $child ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Find the Nth core/html block (in document order, recursing into
	 * innerBlocks) within a parsed block tree.
	 */
	public static function find_html_block( array $blocks, $target_order ) {
		$counter = -1;
		return self::walk_find( $blocks, $target_order, $counter );
	}

	private static function walk_find( array $blocks, $target_order, &$counter ) {
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && 'core/html' === $block['blockName'] ) {
				$counter++;
				if ( $counter === $target_order ) {
					return $block;
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = self::walk_find( $block['innerBlocks'], $target_order, $counter );
				if ( null !== $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * Replace the raw HTML of the Nth core/html block (same ordering as
	 * find_html_block) and return the updated block tree.
	 */
	public static function set_html_block_content_by_order( array $blocks, $target_order, $new_inner_html ) {
		$counter = -1;
		return self::walk_replace( $blocks, $target_order, $new_inner_html, $counter );
	}

	private static function walk_replace( array $blocks, $target_order, $new_inner_html, &$counter ) {
		foreach ( $blocks as &$block ) {
			if ( isset( $block['blockName'] ) && 'core/html' === $block['blockName'] ) {
				$counter++;
				if ( $counter === $target_order ) {
					$block['innerHTML']    = $new_inner_html;
					$block['innerContent'] = array( $new_inner_html );
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::walk_replace( $block['innerBlocks'], $target_order, $new_inner_html, $counter );
			}
		}
		unset( $block );
		return $blocks;
	}
}
