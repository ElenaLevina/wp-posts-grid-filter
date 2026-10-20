=== ELVPG Posts Grid & Filter ===
Contributors: elenalevin
Tags: blocks, posts grid, filter, pagination, interactivity api
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Posts Grid block with inner-block pagination and a Posts Filter block, kept in sync through the URL and the Interactivity API.

== Description ==

The plugin adds two blocks:

* **Posts Grid** — a dynamic grid of posts with 2, 3 or 4 columns and a "posts per page" setting. Pagination is an inner block, so it can be moved or removed.
* **Posts Filter** — category and tag dropdowns with multi-select. Terms of one type are combined with OR, the two types with AND.

The blocks do not talk to each other directly. The filter writes the selection to the URL, and the grid reads it on the server. That is why they can be placed anywhere on the page, in any order, and every filtered view has its own shareable link. With JavaScript, the grid updates without a page reload (Interactivity API router). Without JavaScript, the filter is a normal form and the pagination is normal links.

On activation the plugin creates demo content: 36 posts with images, excerpts, categories and tags, and a demo page with both blocks. Deleting the plugin removes only this demo content.

More details, the architecture decisions and the known limitations are in README.md in the source repository: https://github.com/ElenaLevina/wp-posts-grid-filter

== Installation ==

1. Upload the plugin zip in Plugins → Add New → Upload Plugin.
2. Activate it. The demo content and the "Posts Grid & Filter Demo" page are created.
3. Open the demo page, or add the Posts Grid and Posts Filter blocks to any page.

== Frequently Asked Questions ==

= Can the blocks use the core posts, categories and tags? =

Yes, with three filters: `elvpg_post_type`, `elvpg_category_taxonomy` and `elvpg_tag_taxonomy`.

= What happens to the demo content when I deactivate the plugin? =

Nothing. It is removed only when the plugin is deleted.

== Changelog ==

= 1.0.0 =
* First release.
