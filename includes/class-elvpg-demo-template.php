<?php
/**
 * Page template for the demo page.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers a block template for the demo page: the theme's header, the page
 * title and the content in one 1280px column, then the theme's footer.
 *
 * Why a template: themes usually print the page title in a narrow column,
 * while the demo needs a wide one. Putting an H1 into the page content would
 * make the editor show the title twice (its own title field and the H1). With
 * the title in the template, the editor shows it once, in the right place.
 *
 * Block templates from plugins need WordPress 6.7+ and a block theme with
 * "header" and "footer" template parts. Otherwise nothing is registered and the
 * demo page uses the theme's default page template.
 */
class Elvpg_Demo_Template {

	const SLUG = 'elvpg-demo-page';

	/**
	 * Full template name: plugin text domain and template slug.
	 *
	 * @return string
	 */
	private static function name() {
		return 'elvpg-posts-grid-filter//' . self::SLUG;
	}

	/**
	 * Whether the template can be used with the active theme.
	 *
	 * @return bool
	 */
	public static function is_supported() {
		return function_exists( 'register_block_template' )
			&& wp_is_block_theme()
			&& get_block_template( get_stylesheet() . '//header', 'wp_template_part' )
			&& get_block_template( get_stylesheet() . '//footer', 'wp_template_part' );
	}

	/**
	 * Registers the template. Hooked to `init`, and also called on activation,
	 * when `init` has already run.
	 *
	 * @return bool True when the template is available.
	 */
	public static function register() {
		if ( ! self::is_supported() ) {
			return false;
		}

		if ( WP_Block_Templates_Registry::get_instance()->is_registered( self::name() ) ) {
			return true;
		}

		register_block_template(
			self::name(),
			array(
				'title'       => __( 'Posts Grid & Filter Demo (wide)', 'elvpg-posts-grid-filter' ),
				'description' => __( 'The page title and the content in one 1280px column.', 'elvpg-posts-grid-filter' ),
				'post_types'  => array( 'page' ),
				'content'     => self::content(),
			)
		);

		return true;
	}

	/**
	 * In the editor, the page title field normally uses the theme's content
	 * width, while the template puts the title in the 1280px column. For pages
	 * with this template, the field gets the same width, so the editor looks
	 * like the page. Hooked to `enqueue_block_assets`, which also loads styles
	 * into the editor iframe.
	 */
	public static function editor_title_width() {
		global $post;

		if ( ! is_admin() || ! $post instanceof WP_Post || self::SLUG !== get_page_template_slug( $post ) ) {
			return;
		}

		wp_register_style( 'elvpg-demo-template-editor', false, array(), ELVPG_VERSION );
		wp_enqueue_style( 'elvpg-demo-template-editor' );
		wp_add_inline_style(
			'elvpg-demo-template-editor',
			'.editor-visual-editor__post-title-wrapper > .editor-post-title { max-width: 1280px; }'
		);
	}

	/**
	 * Template markup.
	 *
	 * @return string
	 */
	private static function content() {
		return '<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1280px"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:post-title {"level":1} /-->
	<!-- wp:post-content {"layout":{"type":"constrained","contentSize":"1280px"}} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->';
	}
}
