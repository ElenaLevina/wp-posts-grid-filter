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
 * `_elvpg_demo` meta key, and a second activation creates only what is missing.
 */
class Elvpg_Seeder {

	const OPTION_PAGE_ID = 'elvpg_demo_page_id';
	const DEMO_META_KEY  = '_elvpg_demo';

	/**
	 * Activation callback.
	 *
	 * @param bool $network_wide True when a super admin activates the plugin
	 *                           for the whole network on Multisite.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			self::for_each_site( array( __CLASS__, 'seed_site' ) );
			return;
		}

		self::seed_site();
	}

	/**
	 * Deactivation callback. Content stays (ADR-008); only rewrite rules are reset.
	 *
	 * @param bool $network_wide True on network deactivation.
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			self::for_each_site( array( __CLASS__, 'reset_rewrite_rules' ) );
			return;
		}

		self::reset_rewrite_rules();
	}

	/**
	 * Seeds a site created after the plugin was network-activated.
	 * Hooked to `wp_initialize_site`, which runs when the new site's tables exist.
	 *
	 * @param WP_Site $site The new site.
	 */
	public static function on_new_site( $site ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active_for_network( plugin_basename( ELVPG_FILE ) ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		self::seed_site();
		restore_current_blog();
	}

	/**
	 * Runs a callback on every site of the network.
	 *
	 * @param callable $callback Called once per site, while switched to it.
	 */
	private static function for_each_site( $callback ) {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			call_user_func( $callback );
			restore_current_blog();
		}
	}

	/**
	 * Seeds the current site: terms, posts, demo page.
	 */
	public static function seed_site() {
		// Post types are registered on `init`, which already ran before activation.
		Elvpg_Content_Types::register();

		$term_ids = self::seed_terms();

		self::seed_posts( $term_ids );

		self::seed_page();
		self::reset_rewrite_rules();
	}

	/**
	 * Makes WordPress rebuild the rewrite rules, so the new post type URLs work.
	 *
	 * After switch_to_blog() the global $wp_rewrite still holds the permalink
	 * settings of the original site, so flushing there would save wrong rules.
	 * Deleting the option is safe: WordPress rebuilds the rules on that
	 * site's next request.
	 */
	public static function reset_rewrite_rules() {
		if ( is_multisite() && ms_is_switched() ) {
			delete_option( 'rewrite_rules' );
			return;
		}

		flush_rewrite_rules();
	}

	/**
	 * Demo categories and tags.
	 *
	 * @return array<string, array<string, string>> Taxonomy => slug => name.
	 */
	private static function terms() {
		return array(
			Elvpg_Content_Types::category_taxonomy() => array(
				'development'   => 'Development',
				'design'        => 'Design',
				'performance'   => 'Performance',
				'accessibility' => 'Accessibility',
			),
			Elvpg_Content_Types::tag_taxonomy()      => array(
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
			array( 'Block Patterns for Faster Page Building', 'Ready-made layouts that editors can insert, change and reuse in seconds.', array( 'design', 'development' ), array( 'gutenberg', 'css' ) ),
			array( 'Measuring INP in the Real World', 'Field data, long tasks and the event handlers that slow down every click.', array( 'performance' ), array( 'seo', 'react' ) ),
			array( 'Accessible Forms in Custom Blocks', 'Labels, error messages and fieldsets that work with a keyboard and a screen reader.', array( 'accessibility', 'development' ), array( 'gutenberg', 'php' ) ),
			array( 'Typography Scales with theme.json', 'Fluid font sizes and a clear type scale, defined once for the whole site.', array( 'design' ), array( 'css', 'gutenberg' ) ),
			array( 'Caching Dynamic Blocks', 'Transients, object cache and when a dynamic block should not be cached at all.', array( 'performance', 'development' ), array( 'php' ) ),
			array( 'Writing Alt Text That Helps', 'Describe the purpose of an image, not every pixel of it.', array( 'accessibility' ), array( 'seo' ) ),
			array( 'Dynamic Blocks vs Client-Side Rendering', 'Where the markup is built changes speed, SEO and how much code you maintain.', array( 'development' ), array( 'react', 'php' ) ),
			array( 'Color Contrast Checks in Code Review', 'Catch low-contrast text before it ships, with tools and a short checklist.', array( 'accessibility', 'design' ), array( 'css', 'ai-tools' ) ),
			array( 'Less JavaScript in Block Themes', 'Ship interactivity only where it is needed and keep the rest as plain HTML.', array( 'performance' ), array( 'react', 'css' ) ),
			array( 'Code Review with AI Assistants', 'What an assistant catches well, what it misses, and how to keep the human in charge.', array( 'development' ), array( 'ai-tools' ) ),
			array( 'Designing Empty States', 'No results is still a screen: explain what happened and offer the next step.', array( 'design' ), array( 'css' ) ),
			array( 'Interactivity API Basics', 'Directives, stores and server-rendered state for interactive blocks.', array( 'development', 'performance' ), array( 'gutenberg', 'react' ) ),
			array( 'Skip Links and Landmarks', 'Let keyboard users jump past the header straight to the content.', array( 'accessibility' ), array( 'seo', 'css' ) ),
			array( 'Loading Web Fonts Without Layout Shift', 'Preload, font-display and fallback metrics that keep the text steady.', array( 'performance', 'design' ), array( 'css', 'seo' ) ),
			array( 'Testing PHP with PHPUnit', 'Small pure functions are the easiest code to test and to trust.', array( 'development' ), array( 'php' ) ),
			array( 'Motion Design and Reduced Motion', 'Animations that help, and a quiet version for people who turn them off.', array( 'design', 'accessibility' ), array( 'css' ) ),
			array( 'Custom REST API Endpoints', 'Routes, permission callbacks and schemas for data that blocks need.', array( 'development' ), array( 'php', 'react' ) ),
			array( 'AI-Generated Images on Your Site', 'Licensing questions, alt text and when a real photo is still better.', array( 'design' ), array( 'ai-tools', 'seo' ) ),
			array( 'Database Queries That Scale', 'Indexes, tax queries and avoiding slow meta queries on large sites.', array( 'performance', 'development' ), array( 'php' ) ),
			array( 'Focus Styles Done Right', 'Visible, high-contrast focus indicators that match the design.', array( 'accessibility', 'design' ), array( 'css' ) ),
			array( 'Structured Data for WordPress', 'Schema markup that helps search engines understand your content.', array( 'development' ), array( 'seo', 'php' ) ),
			array( 'From Design Tokens to CSS Variables', 'Keep Figma and code in sync with one set of named values.', array( 'design' ), array( 'css', 'react' ) ),
			array( 'Lighthouse Scores Are Not the Goal', 'Use lab scores as hints and real user data as the measure.', array( 'performance' ), array( 'seo' ) ),
			array( 'Working with an AI Coding Agent', 'Small tasks, clear context and reviewing every change before it lands.', array( 'development' ), array( 'ai-tools', 'react' ) ),
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
					// Only terms created here are marked, so uninstall never removes a term the site already had.
					add_term_meta( (int) $created['term_id'], self::DEMO_META_KEY, 1, true );
				}
			}
		}

		return $ids;
	}

	/**
	 * Titles of the demo posts that already exist on this site.
	 *
	 * @return array<string, true> Title => true, for fast lookups.
	 */
	private static function existing_demo_titles() {
		$ids = get_posts(
			array(
				'post_type'      => Elvpg_Content_Types::post_type(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::DEMO_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-time check on activation.
			)
		);

		$titles = array();

		foreach ( $ids as $id ) {
			$titles[ get_the_title( $id ) ] = true;
		}

		return $titles;
	}

	/**
	 * Creates the demo posts with terms and featured images.
	 *
	 * @param array<string, array<string, int>> $term_ids Term IDs from seed_terms().
	 */
	private static function seed_posts( array $term_ids ) {
		$existing = self::existing_demo_titles();

		foreach ( self::posts() as $index => $post ) {
			// Activating again creates only the posts that are missing, so a
			// site seeded by an older version gets the new posts without duplicates.
			if ( isset( $existing[ $post[0] ] ) ) {
				continue;
			}

			self::seed_post( $index, $post, $term_ids );
		}
	}

	/**
	 * Creates one demo post with its terms and featured image.
	 *
	 * @param int                               $index    Position in the list, used for the date and the image.
	 * @param array                             $post     Title, excerpt, category slugs, tag slugs.
	 * @param array<string, array<string, int>> $term_ids Term IDs from seed_terms().
	 */
	private static function seed_post( $index, array $post, array $term_ids ) {
		list( $title, $excerpt, $categories, $tags ) = $post;

		$post_id = wp_insert_post(
			array(
				'post_type'    => Elvpg_Content_Types::post_type(),
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
			return;
		}

		$category_tax = Elvpg_Content_Types::category_taxonomy();
		$tag_tax      = Elvpg_Content_Types::tag_taxonomy();

		wp_set_object_terms( $post_id, self::map_ids( $categories, $term_ids[ $category_tax ] ?? array() ), $category_tax );
		wp_set_object_terms( $post_id, self::map_ids( $tags, $term_ids[ $tag_tax ] ?? array() ), $tag_tax );

		$image_id = self::import_image( sprintf( 'demo-%02d.webp', $index + 1 ), $post_id, $title );

		if ( $image_id ) {
			set_post_thumbnail( $post_id, $image_id );
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
				'post_mime_type' => wp_check_filetype( $upload['file'] )['type'],
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

		// The plugin's page template puts the title and the content in one
		// 1280px column (see Elvpg_Demo_Template). Without it the page uses the
		// theme's default template. The content is the same in both cases.
		$template = Elvpg_Demo_Template::register() ? Elvpg_Demo_Template::SLUG : '';

		$content = implode(
			"\n\n",
			array(
				'<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html__( 'Explore the posts', 'elvpg-posts-grid-filter' ) . '</h2><!-- /wp:heading -->',
				'<!-- wp:elvpg/posts-filter /-->',
				'<!-- wp:paragraph --><p>' . esc_html__( 'The filter above and the grid below are independent blocks. Move either one anywhere on the page and they still stay in sync.', 'elvpg-posts-grid-filter' ) . '</p><!-- /wp:paragraph -->',
				'<!-- wp:elvpg/posts-grid {"columns":4,"postsPerPage":8} -->' . "\n" . '<!-- wp:elvpg/posts-pagination /-->' . "\n" . '<!-- /wp:elvpg/posts-grid -->',
			)
		);

		$page_id = wp_insert_post(
			array(
				'post_type'     => 'page',
				'post_status'   => 'publish',
				'post_title'    => __( 'Posts Grid & Filter Demo', 'elvpg-posts-grid-filter' ),
				'post_name'     => 'elvpg-demo',
				'post_content'  => $content,
				'page_template' => $template,
				'meta_input'    => array( self::DEMO_META_KEY => 1 ),
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( self::OPTION_PAGE_ID, $page_id, false );
		}
	}
}
