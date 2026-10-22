# ELVPG Posts Grid & Filter

WordPress plugin with two dynamic Gutenberg blocks: **Posts Grid** (with an inner **Posts Pagination** block) and **Posts Filter**. They stay in sync through the URL and the Interactivity API router. Activation seeds demo content.

Read `README.md` for the user-facing overview and `docs/decisions.md` (ADR-001…) for why things are built the way they are. Do not change a decision recorded there without saying so.

## Requirements to keep

- WordPress **6.6+**, PHP **7.4+**. No PHP 8 syntax (no `match`, named arguments, nullsafe `?->`, constructor promotion, union types). PHPCS checks this with PHPCompatibilityWP.
- WordPress Coding Standards (WPCS 3). Prefix everything with `elvpg` / `Elvpg_`. Text domain: `elvpg-posts-grid-filter`.
- Escape late (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`), sanitize input. Every `phpcs:ignore` needs a reason in the comment.

## Architecture map

| File | Responsibility |
|---|---|
| `elvpg-posts-grid-filter.php` | Bootstrap: constants, requires, hooks |
| `includes/class-elvpg-content-types.php` | `elvpg_post`, `elvpg_category`, `elvpg_tag`. Other code gets the names **only** through `post_type()`, `category_taxonomy()`, `tag_taxonomy()` (filterable, ADR-001) |
| `includes/class-elvpg-query.php` | URL state (`elvpg_cat`, `elvpg_tag`, `elvpg_page`), `WP_Query` args, per-request cache, pagination items. Pure functions are unit-tested |
| `includes/class-elvpg-blocks.php` | Registers blocks from `build/`, editor settings, missing-build notice |
| `includes/class-elvpg-empty-state.php` | "No posts found" message and actions |
| `includes/class-elvpg-seeder.php` | Demo content on activation, Multisite, idempotent |
| `includes/class-elvpg-demo-template.php` | Demo page template for block themes |
| `uninstall.php` | Removes only content marked with `_elvpg_demo` meta |
| `src/posts-grid/` | `render.php` (front end), `edit.js` + `post-card.js` (editor preview), `view.js` (page links), `style.scss` |
| `src/posts-pagination/` | `render.php`, `items.js` (JS copy of `pagination_items()`), `edit.js` |
| `src/posts-filter/` | `render.php` (GET form, works without JS), `view.js` (dropdowns, router navigation), `edit.js` |
| `src/shared/navigation.js` | Store namespace `elvpg/posts`, URL building, navigate/prefetch, loading state |
| `tests/Unit/` | PHPUnit tests for pure functions (no WordPress loaded) |

## Invariants — check these on every change

1. **The URL is the single source of truth** (ADR-002). The filter writes the URL, the grid reads it on the server. Blocks never talk to each other directly.
2. **All post queries go through `Elvpg_Query::get_posts()`**, so the grid and its pagination share one cached `WP_Query`.
3. **Markup is mirrored.** A change to card markup goes into `posts-grid/render.php` **and** `posts-grid/post-card.js`. A change to page numbers goes into `Elvpg_Query::pagination_items()` **and** `posts-pagination/items.js`. Same class names, one stylesheet.
4. **Progressive enhancement** (ADR-006): the filter is a real form and pagination is real links. Everything must still work with JavaScript off.
5. **Accessibility**: keep `aria-live` status, focus management, visible `:focus-visible` rings, 40–44px touch targets, `prefers-reduced-motion`.
6. **Layout follows the container**, not the screen: container queries and `cqi`, no media queries for block layout. RTL works through the generated `*-rtl.css`.
7. New user-visible strings are translatable. New query args go through the `elvpg_query_args` filter path.

## Commands

```bash
npm run build          # build blocks into build/ (needed after any change in src/)
npm start              # rebuild on change
npm run lint:js        # ESLint
npm run lint:css       # Stylelint
npm run lint:php       # PHPCS (runs inside wp-env)
npm run test:php       # PHPUnit (runs inside wp-env)
npm run release        # build + elvpg-posts-grid-filter.zip with runtime files only
npm run env:start      # local WordPress at http://localhost:8888 (admin / password)
```

Demo page: `http://localhost:8888/?pagename=elvpg-demo`. WP-CLI: `npx @wordpress/env run cli wp …`.

## wp-env safety

wp-env mounts **this folder** as the plugin. Never delete the plugin from wp-admin and never run `wp plugin delete` or `wp plugin uninstall` without `--skip-delete`: it deletes the source files and `.git`. To re-seed demo content:

```bash
npx @wordpress/env run cli wp plugin deactivate elvpg-posts-grid-filter
npx @wordpress/env run cli wp plugin uninstall elvpg-posts-grid-filter --skip-delete
npx @wordpress/env run cli wp plugin activate elvpg-posts-grid-filter
```

## How to work in this repo

- **Plan first** for anything bigger than a one-line fix: list the files you will touch (remember the mirrored pairs) and how you will check the result.
- After a change run `/verify` (lints, unit tests, build). Add or update unit tests for new logic in `Elvpg_Query` or other pure functions.
- A new technical decision gets an ADR entry (`/adr`). User-facing changes go into `README.md`.
- **Do not commit or push.** Show the diff and a suggested commit message; the developer reviews and commits.
- Keep commit messages short, imperative, in English, without AI attribution lines.
- Explain what changed and why in plain words, so the developer can defend every line.
