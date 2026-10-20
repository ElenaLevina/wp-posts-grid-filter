# ELVPG Posts Grid & Filter

A WordPress plugin with two Gutenberg blocks:

- **Posts Grid** — a dynamic grid of posts with a **Posts Pagination** inner block.
- **Posts Filter** — category and tag filters for the grid.

The two blocks are independent: put them anywhere on the same page, in any order, and they stay in sync. On activation the plugin creates all demo content, so it is ready to try right away.

Detailed reasoning for every technical decision is in [docs/decisions.md](docs/decisions.md) (short architecture decision records, ADR-001 to ADR-010). This README is the overview.

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [What activation creates](#what-activation-creates)
- [How to check it](#how-to-check-it)
- [How it works](#how-it-works)
- [Tradeoffs](#tradeoffs)
- [Known limitations](#known-limitations)
- [Development](#development)
- [How this was built](#how-this-was-built)

---

## Requirements

| | Minimum | Notes |
|---|---|---|
| WordPress | 6.6 | The blocks' editor scripts use the React JSX runtime that WordPress ships since 6.6 |
| PHP | 7.4 | Checked with PHPCompatibilityWP |
| Theme | any | A block theme is recommended. On WordPress 6.7+ with a block theme, the demo page uses a wide page template from the plugin |

Tested with WordPress 7.1 and 6.8 (Twenty Twenty-Five) and with the minimum version, 6.6 (Twenty Twenty-Four). Also checked with a classic theme (Twenty Twenty-One), on Multisite (subdirectories) and on an RTL site.

## Installation

### Option A — the release zip (no build step)

1. Download `elvpg-posts-grid-filter.zip` from the GitHub release.
2. In WordPress: **Plugins → Add New Plugin → Upload Plugin**, choose the zip, **Install Now**, **Activate**.
3. Open **Pages → Posts Grid & Filter Demo** and view the page.

The zip contains the compiled `build/` folder, so nothing else is needed.

### Option B — from source with wp-env (Docker)

Requires Node.js 20.19+ and Docker.

```bash
git clone https://github.com/ElenaLevina/wp-posts-grid-filter.git elvpg-posts-grid-filter
cd elvpg-posts-grid-filter
npm install
npm run build
npm run env:start
```

- Demo page: <http://localhost:8888/?pagename=elvpg-demo> (works with any permalink setting).
- The folder name matters: the `lint:php` and `test:php` scripts expect the plugin at `wp-content/plugins/elvpg-posts-grid-filter`, so clone into a folder with that name.
- Admin: <http://localhost:8888/wp-admin> — user `admin`, password `password`.
- wp-env activates the plugin, so the demo content is created at once.
- To start again from a clean database: `npx @wordpress/env reset development`.

## What activation creates

| Content | Details |
|---|---|
| Post type | `elvpg_post` ("Grid Posts" in the admin menu) |
| Taxonomies | `elvpg_category` (hierarchical) and `elvpg_tag` |
| Terms | 4 categories: Accessibility, Design, Development, Performance. 6 tags: AI Tools, CSS, Gutenberg, PHP, React, SEO |
| Posts | 36 posts, each with a featured image, an excerpt, 1–2 categories and 1–3 tags. Images are bundled WebP files, so no network access is needed |
| Demo page | "Posts Grid & Filter Demo" with a heading, the filter, a short text and the grid (4 columns, 8 posts per page, pagination inside) |

Everything the plugin creates is marked with the `_elvpg_demo` meta key:

- **Activating again** creates only what is missing, so there are no duplicates.
- **Deactivating** keeps all content.
- **Deleting the plugin** (`uninstall.php`) removes only the marked content. Posts and terms that users created themselves stay.
- **Multisite:** network activation seeds every site, and new sites get the content when they are created.

## How to check it

Open the demo page. Expected results with the seeded content:

| Categories | Tags | Posts |
|---|---|---|
| — | — | 36 (5 pages) |
| Design | — | 13 |
| Design **or** Development | — | 29 |
| — | CSS **or** SEO | 22 |
| Design | CSS | 11 |
| Design **or** Development | CSS | 12 |
| Accessibility | CSS **or** SEO | 8 |
| Performance | AI Tools | 0 — the empty state explains why and offers ways out |
| Design | PHP | 0 |

Things worth trying:

- **No page reload.** Ticking a checkbox updates the grid and the URL (`?elvpg_cat=3,5&elvpg_tag=7`). Copy the URL into a new tab: the same results show.
- **Back / Forward** restore the previous results and the checkboxes.
- **Pagination** keeps the current filters. Changing a filter goes back to page 1.
- **Block placement.** In the editor, move the filter below the grid or into a column: it still works.
- **Keyboard only.** Tab to a dropdown, Enter to open, Tab and Space to tick, Escape to close (focus returns to the button).
- **JavaScript disabled.** The filter is a normal form with an "Apply filters" button, and the pagination links are normal links. Both still work.
- **Editor.** Both blocks show a live preview with the real posts and terms. Inspector controls: maximum columns (2–4) and posts per page (1–24) for the grid; show/hide categories and tags for the filter. The toolbar has a heading level control for the post titles. Filtering itself works on the published page only.
- **Narrow screens.** The grid shows fewer columns, the pagination fits one row, and the filter buttons share one row.

## How it works

```text
elvpg-posts-grid-filter.php      Plugin header, hooks
uninstall.php                    Removes demo content on delete
includes/
  class-elvpg-content-types.php  Post type and taxonomies (names can be filtered)
  class-elvpg-seeder.php         Demo content, Multisite
  class-elvpg-query.php          Query service: URL state → tax_query → cached WP_Query
  class-elvpg-blocks.php         Block registration, editor settings
  class-elvpg-empty-state.php    "No posts found" message and actions
  class-elvpg-demo-template.php  Wide page template for the demo page
src/
  posts-grid/                    block.json, render.php, edit.js, view.js, styles
  posts-pagination/              Inner block of the grid
  posts-filter/                  block.json, render.php, edit.js, view.js, styles
  shared/navigation.js           URL building and client-side navigation
tests/Unit/QueryTest.php         PHPUnit tests for the filter and pagination logic
docs/decisions.md                Architecture decision records
```

### Inter-block communication: the URL is the only state

This is the main decision of the task ([ADR-002](docs/decisions.md#adr-002--inter-block-communication-interactivity-api-with-the-url-as-the-single-source-of-truth)).

The filter never talks to the grid directly. When a checkbox changes, the filter builds a new URL (`?elvpg_cat=3,5&elvpg_tag=7`) and asks the **Interactivity API router** to navigate there. The router fetches the server-rendered page and replaces only the grid's router region. The grid reads the URL on the server.

Why this approach:

- **Placement does not matter.** The blocks share no parent and no JavaScript object, only the URL. Block context was not an option: it only flows from a parent to its inner blocks.
- **One source of markup.** A card is rendered in one place, `render.php`. A REST + JavaScript approach would have to build the same card again in JavaScript.
- **Shareable and reversible.** The URL can be bookmarked or shared, and Back/Forward work.
- **Progressive enhancement.** Without JavaScript the same URL parameters come from a plain GET form.
- **It is the standard WordPress way** for front-end block interactivity (Interactivity API, WordPress 6.5+).

Alternatives considered: custom DOM events with a REST endpoint returning JSON, or a shared global JS object. Both would avoid a full-page request, but would duplicate rendering in JavaScript and lose the URL state.

### Filter logic: OR inside a type, AND across types

`Elvpg_Query::build_tax_query()` builds one `tax_query` ([ADR-005](docs/decisions.md#adr-005--filter-logic-or-within-a-type-and-across-types)):

```php
array(
    'relation' => 'AND',
    array( 'taxonomy' => 'elvpg_category', 'field' => 'term_id', 'terms' => array( 3, 5 ), 'operator' => 'IN' ),
    array( 'taxonomy' => 'elvpg_tag',      'field' => 'term_id', 'terms' => array( 7 ),    'operator' => 'IN' ),
)
```

`IN` gives OR inside one filter type, and `relation => AND` gives AND across types. A type with no selection adds no clause. URL values are accepted only as positive integers; anything else is dropped. The builder is a pure function covered by unit tests, and every combination of up to two categories and two tags was also checked against a real `WP_Query`.

### Pagination as an inner block

WordPress renders inner blocks **before** their parent, so the pagination cannot receive the grid's `WP_Query` ([ADR-004](docs/decisions.md#adr-004--pagination-as-an-inner-block)):

- The grid passes "posts per page" to the pagination through **block context**.
- Both blocks call one query service, `Elvpg_Query::get_posts()`, which keeps a **static per-request cache**. The page makes one posts query, not two.
- The page number has its own URL parameter, `elvpg_page`, so it does not conflict with WordPress's own `paged`.
- The pagination block can only be placed inside a grid (`"parent"` in block.json).

### Rendering

Both blocks are **dynamic**: `render.php` builds the markup on every request, so the grid always shows current posts ([ADR-003](docs/decisions.md#adr-003--rendering-dynamic-blocks-on-the-front-end-react-preview-in-the-editor)). In the editor, React components read posts and terms from the `core` data store (`useSelect`) and use the same class names, so one stylesheet serves both.

### Accessibility and responsive layout

([ADR-006](docs/decisions.md#adr-006--progressive-enhancement-and-accessibility))

- Dropdowns follow the disclosure pattern (`aria-expanded`, native checkboxes in a `fieldset`).
- A polite live region announces the number of results after each update. After a page change, focus moves to the top of the grid.
- While results load, the grid is dimmed and marked `aria-busy`; if it takes longer than 300 ms, skeleton cards replace the old results.
- The whole card is clickable with a "stretched link": one link per card for screen readers.
- Focus rings are shown for keyboard users. All text colors pass WCAG AA.
- Layout is based on **container queries**: the grid, the filter and the pagination adapt to their own width, not the screen, so they also work in a sidebar or a narrow column.

## Tradeoffs

- **A full page request per filter change.** The router fetches the whole server-rendered page and keeps only the grid. A JSON endpoint would send less data, at the cost of rendering cards twice and losing the URL state. The router prefetches pagination links on hover, which hides most of the delay.
- **Card markup exists twice:** in `render.php` and in the editor preview (`post-card.js`). They share class names and styles. This is the usual cost of a live React preview; `ServerSideRender` would avoid it but is slower and is no longer recommended for new blocks.
- **No filtering in the editor.** The editor shows the first page of all posts. Filtering there would need the URL state inside the editor and gives authors little value.
- **Stretched link.** The text inside a card cannot be selected with the mouse.
- **A custom post type** instead of core posts, because the brief asks for a prefixed post type. Switching to core posts, categories and tags takes three filters (`elvpg_post_type`, `elvpg_category_taxonomy`, `elvpg_tag_taxonomy`) — see [ADR-001](docs/decisions.md#adr-001--content-model-prefixed-post-type-and-taxonomies).

## Known limitations

- **Demo page layout on WordPress 6.6 or with other themes.** The wide demo page template needs WordPress 6.7+ and a block theme with header and footer template parts. Otherwise the demo page uses the theme's default page template, and the content sits in the theme's normal column.
- **One URL state per page.** All grids on a page follow the same filter. Two independent filter-and-grid pairs on one page are not supported.
- **Term IDs in the URL.** Shared links work on the same site; they are not portable between sites. Slugs would be more readable, at the cost of extra term lookups.
- **Large networks.** On Multisite, network activation seeds every site in one request. On a very large network this could time out; a WP-CLI command or a background queue would be the next step.
- **Hierarchical categories** are shown as a flat list in the filter. Selecting a parent category also includes its children (WordPress's default for `tax_query`).
- **No result counts** next to filter options.

## Development

| Command | What it does |
|---|---|
| `npm run build` | Builds the blocks into `build/` |
| `npm start` | Rebuilds on every change |
| `npm run lint:js` | ESLint (WordPress config) |
| `npm run lint:css` | Stylelint (WordPress config) |
| `npm run lint:php` | PHPCS: WordPress Coding Standards + PHP 7.4 compatibility (runs in wp-env) |
| `npm run test:php` | PHPUnit unit tests (runs in wp-env) |
| `npm run env:start` / `env:stop` | Start / stop the local WordPress |
| `npm run release` | Builds and creates `elvpg-posts-grid-filter.zip` with only the runtime files (PHP, `build/`, `assets/`, `readme.txt`) |

The first time, install the PHP tools inside wp-env: `npm run composer -- install`.

`readme.txt` is the plugin readme in the WordPress.org format; this README is for GitHub. The release zip passes [Plugin Check](https://wordpress.org/plugins/plugin-check/). Run it on the source folder and it also lists development files (tests, configs, dotfiles); they are not part of the release.

Testing approach ([ADR-009](docs/decisions.md#adr-009--testing-strategy)):

- **Unit tests** for the query builder, input parsing and page numbers (39 tests). They do not need WordPress, so they run in milliseconds.
- **Linting** for PHP, JS and CSS, with 0 errors and 0 warnings. Each `phpcs:ignore` names the rule and explains why it does not apply.
- **Manual checks** with the table and the list above, in the browser, with the keyboard and without JavaScript.

## How this was built

I used Claude Code as a pair programmer: I made the architecture decisions, reviewed and tested every change. The decision records in `docs/decisions.md` were written along the way, and the commit history shows the work step by step.

## License

GPL-2.0-or-later
