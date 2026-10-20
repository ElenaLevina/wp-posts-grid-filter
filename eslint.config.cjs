/**
 * ESLint config: the WordPress defaults from @wordpress/scripts, plus one setting.
 *
 * `@wordpress/*` packages are not installed as dependencies: at build time
 * the dependency extraction plugin turns those imports into the `wp.*`
 * globals that WordPress already loads on the page. Listing them as core
 * modules tells the import rules not to look for them in package.json.
 */
const wordpressConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpressConfig,
	{
		settings: {
			'import/core-modules': [
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/components',
				'@wordpress/compose',
				'@wordpress/core-data',
				'@wordpress/data',
				'@wordpress/html-entities',
				'@wordpress/i18n',
				'@wordpress/interactivity',
				'@wordpress/interactivity-router',
			],
		},
	},
];
