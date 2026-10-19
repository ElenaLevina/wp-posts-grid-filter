<?php
/**
 * Seeds demo content on plugin activation.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates demo terms, posts (with featured images) and a demo page.
 *
 * Seeding is idempotent: everything it creates is marked with the
 * `_elvpg_demo` meta key, and a second activation does not duplicate content.
 */
class Elvpg_Seeder {

	const OPTION_PAGE_ID = 'elvpg_demo_page_id';
	const DEMO_META_KEY  = '_elvpg_demo';

	/**
	 * Activation callback.
	 */
	public static function activate() {
		// Post types are registered on `init`, which already ran before activation.
		Elvpg_Content_Types::register();

		$term_ids = self::seed_terms();

		if ( ! self::has_demo_posts() ) {
			self::seed_posts( $term_ids );
		}

		self::seed_page();

		flush_rewrite_rules();
	}

	/**
	 * Demo categories and tags.
	 *
	 * @return array<string, array<string, string>> Taxonomy => slug => name.
	 */
	private static function terms() {
		return array(
			Elvpg_Content_Types::TAX_CATEGORY => array(
				'development'   => 'Development',
				'design'        => 'Design',
				'performance'   => 'Performance',
				'accessibility' => 'Accessibility',
			),
			Elvpg_Content_Types::TAX_TAG      => array(
				'react'     => 'React',
				'php'       => 'PHP',
				'gutenberg' => 'Gutenberg',
				'css'       => 'CSS',
				'seo'       => 'SEO',
				'ai-tools'  => 'AI Tools',
			),
		);
	}

	/**
	 * Demo posts. Each post has several categories and tags, so filter
	 * combinations return different, meaningful result sets.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function posts() {
		return array(
			array( 'Building Your First Dynamic Gutenberg Block', 'From block.json to render.php: a dynamic block that always shows fresh data.', array( 'development' ), array( 'gutenberg', 'php', 'react' ) ),
			array( 'Designing Blocks Authors Actually Enjoy', 'Good blocks hide complexity: clear controls, sensible defaults and a consistent look.', array( 'design', 'development' ), array( 'gutenberg', 'css' ) ),
			array( 'Core Web Vitals for WordPress Sites', 'LCP, CLS and INP explained with practical fixes for content-heavy sites.', array( 'performance' ), array( 'seo', 'css' ) ),
			array( 'Accessible Color Systems with theme.json', 'Define a palette once and keep contrast accessible across every block.', array( 'accessibility', 'design' ), array( 'css', 'gutenberg' ) ),
			array( 'React Patterns Inside the Block Editor', 'useSelect, useDispatch and InspectorControls in real editor code.', array( 'development' ), array( 'react', 'gutenberg' ) ),
			array( 'Server-Side Rendering and SEO', 'Why server-rendered markup still matters for search engines and users.', array( 'performance', 'development' ), array( 'seo', 'php' ) ),
			array( 'AI-Assisted WordPress Development', 'Using Claude Code and Cursor with clear context, small tasks and careful review.', array( 'development' ), array( 'ai-tools', 'php', 'react' ) ),
			array( 'Keyboard-Friendly Carousels', 'Focus management, reduced motion and ARIA for sliders that everyone can use.', array( 'accessibility', 'development' ), array( 'react', 'css' ) ),
			array( 'Lazy Loading Images the Right Way', 'Explicit sizes, responsive srcset and loading hints that avoid layout shift.', array( 'performance', 'design' ), array( 'css', 'seo' ) ),
			array( 'Custom Post Types and Taxonomies', 'Modelling content so editors and queries both stay simple.', array( 'development' ), array( 'php', 'gutenberg' ) ),
			array( 'Prompting for Better Block Code', 'How to brief an AI assistant so the generated block follows your conventions.', array( 'development', 'design' ), array( 'ai-tools', 'gutenberg' ) ),
			array( 'Semantic HTML for Screen Readers', 'Headings, landmarks and lists: the free accessibility wins in every template.', array( 'accessibility' ), array( 'seo', 'css' ) ),
		);
	}

	/**
	 * Creates the terms if they do not exist yet.
	 *
	 * @return array<string, array<string, int>> Taxonomy => slug => term ID.
	 */
	private static function seed_terms() {
		$ids = array();

		foreach ( self::terms() as $taxonomy => $terms ) {
			foreach ( $terms as $slug => $name ) {
				$existing = get_term_by( 'slug', $slug, $taxonomy );

				if ( $existing ) {
					$ids[ $taxonomy ][ $slug ] = (int) $existing->term_id;
					continue;
				}

				$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

				if ( ! is_wp_error( $created ) ) {
					$ids[ $taxonomy ][ $slug ] = (int) $created['term_id'];
				}
			}
		}

		return $ids;
	}

	/**
	 * Whether demo posts already exist (prevents duplicates on re-activation).
	 *
	 * @return bool
	 */
	private static function has_demo_posts() {
		$found = get_posts(
			array(
				'post_type'      => Elvpg_Content_Types::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::DEMO_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-time check on activation.
			)
		);

		return ! empty( $found );
	}

	/**
	 * Creates the demo posts with terms and featured images.
	 *
	 * @param array<string, array<string, int>> $term_ids Term IDs from seed_terms().
	 */
	private static function seed_posts( array $term_ids ) {
		foreach ( self::posts() as $index => $post ) {
			list( $title, $excerpt, $categories, $tags ) = $post;

			$post_id = wp_insert_post(
				array(
					'post_type'    => Elvpg_Content_Types::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => $excerpt,
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $excerpt ) . '</p><!-- /wp:paragraph -->',
					// Spread dates so the default ordering (newest first) is stable.
					'post_date'    => gmdate( 'Y-m-d H:i:s', time() - ( $index * DAY_IN_SECONDS ) ),
					'meta_input'   => array( self::DEMO_META_KEY => 1 ),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			wp_set_object_terms( $post_id, self::map_ids( $categories, $term_ids[ Elvpg_Content_Types::TAX_CATEGORY ] ), Elvpg_Content_Types::TAX_CATEGORY );
			wp_set_object_terms( $post_id, self::map_ids( $tags, $term_ids[ Elvpg_Content_Types::TAX_TAG ] ), Elvpg_Content_Types::TAX_TAG );

			$image_id = self::import_image( sprintf( 'demo-%02d.jpg', $index + 1 ), $post_id, $title );

			if ( $image_id ) {
				set_post_thumbnail( $post_id, $image_id );
			}
		}
	}

	/**
	 * Maps term slugs to term IDs.
	 *
	 * @param string[]           $slugs Term slugs.
	 * @param array<string, int> $map   Slug => term ID.
	 * @return int[]
	 */
	private static function map_ids( array $slugs, array $map ) {
		return array_values( array_intersect_key( $map, array_flip( $slugs ) ) );
	}

	/**
	 * Copies a bundled image into the uploads folder and creates an attachment.
	 *
	 * @param string $filename File name inside assets/seed.
	 * @param int    $post_id  Parent post ID.
	 * @param string $title    Used for the alt text.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private static function import_image( $filename, $post_id, $title ) {
		$source = ELVPG_DIR . 'assets/seed/' . $filename;

		if ( ! file_exists( $source ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled file.
		$upload = wp_upload_bits( 'elvpg-' . $filename, null, file_get_contents( $source ) );

		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $title,
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$post_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );
		update_post_meta( $attachment_id, self::DEMO_META_KEY, 1 );

		return (int) $attachment_id;
	}

	/**
	 * Creates the demo page with both blocks already placed.
	 * The filter and the grid are siblings, not nested, to show that
	 * they work together from anywhere on the page.
	 */
	private static function seed_page() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID );

		if ( $page_id && get_post( $page_id ) ) {
			return;
		}

		$content = implode(
			"\n\n",
			array(
				'<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html__( 'Explore the posts', 'elvpg-posts-grid-filter' ) . '</h2><!-- /wp:heading -->',
				'<!-- wp:elvpg/posts-filter /-->',
				'<!-- wp:paragraph --><p>' . esc_html__( 'The filter above and the grid below are independent blocks. Move either one anywhere on the page and they still stay in sync.', 'elvpg-posts-grid-filter' ) . '</p><!-- /wp:paragraph -->',
				'<!-- wp:elvpg/posts-grid {"columns":3,"postsPerPage":6} -->' . "\n" . '<!-- wp:elvpg/posts-pagination /-->' . "\n" . '<!-- /wp:elvpg/posts-grid -->',
			)
		);

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Posts Grid & Filter Demo', 'elvpg-posts-grid-filter' ),
				'post_name'    => 'elvpg-demo',
				'post_content' => $content,
				'meta_input'   => array( self::DEMO_META_KEY => 1 ),
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( self::OPTION_PAGE_ID, $page_id, false );
		}
	}
}
