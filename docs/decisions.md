# Architecture decisions

Short records of the main technical decisions in this plugin: the context, the options I considered, what I chose and what it costs. The README links here for the details.

---

## ADR-001 — Content model: prefixed post type and taxonomies

**Context.** The brief says the grid "displays posts", but also asks for a unique prefix for the "post type and any custom taxonomy names". These two lines can be read in two ways.

**Options.**
1. A custom post type (`elvpg_post`) with custom taxonomies (`elvpg_category`, `elvpg_tag`).
2. Core `post` with core `category` and `post_tag`.

**Decision.** Option 1. It follows the prefix requirement literally and keeps the demo content isolated: on a site that already has posts, the grid and the filters still show exactly the seeded data, and uninstall can remove only what the plugin created.

**Consequences.** The blocks read the post type and taxonomy names through filters (`elvpg_post_type`, `elvpg_category_taxonomy`, `elvpg_tag_taxonomy`), so switching to core posts, categories and tags takes three short filters in a theme or mu-plugin. When a name is switched, the plugin does not register its own type or taxonomy, and the demo content is seeded into the core ones.

---

## ADR-002 — Inter-block communication: Interactivity API, with the URL as the single source of truth

**Context.** The filter and the grid are siblings. They can be placed anywhere on the page, in any order, and must stay in sync. Block context does not work here, because it only flows from a parent to its inner blocks.

**Options.**
1. **Interactivity API + `@wordpress/interactivity-router`.** The filter writes its state to the URL and asks the router to navigate. The router fetches the server-rendered page and replaces only the grid's router region.
2. Custom DOM events + a REST endpoint that returns posts as JSON, rendered again in JavaScript.
3. A shared global JS object or `localStorage`.

**Decision.** Option 1.
- The URL (`?elvpg_cat=…&elvpg_tag=…&elvpg_page=…`) is the only state. It gives shareable links, a working back button, and the same result after a page refresh.
- The markup of a post card lives in one place: the PHP render callback. Nothing is rendered twice in JavaScript.
- It is the standard WordPress way for front-end block interactivity since 6.5, and it works no matter where each block sits on the page.
- It degrades gracefully: without JavaScript the filter is a plain GET form and the pagination is plain links (see ADR-006).

**Implementation details.**
- The filter never talks to the grid. It builds a URL and calls `navigateTo()` from `src/shared/navigation.js`; the grid reads the URL on the server. Every grid on the page follows the same URL.
- The router matches regions by id between the old and the new page, so the id must be stable across requests. It is based on the grid's position on the page: `elvpg-grid-1`, `elvpg-grid-2`…
- The router module is loaded with a dynamic `import()` on the first interaction, so the initial page load stays small.
- The filter's checkboxes are outside the router region, so they are not re-rendered. A `data-wp-watch` callback keeps them in line with the URL after Back/Forward and after "Clear filters" in the grid's empty state.
- If the user clicks quickly, the router ignores responses from older navigations, so the grid always shows the latest selection.

**Consequences.** Requires WordPress 6.5+. Each filter change makes a request for a full server-rendered page. This is acceptable for this use case, and the router prefetches pagination links on hover. Options 2 and 3 would avoid this request, but would duplicate rendering logic in JavaScript and lose the URL state.

---

## ADR-003 — Rendering: dynamic blocks on the front end, React preview in the editor

**Context.** Posts must load dynamically. In the editor the block still needs a live preview.

**Options for the editor preview.**
1. `ServerSideRender` — the editor requests the PHP markup from the server.
2. A React preview that reads posts from the `core` data store with `useSelect` / `getEntityRecords`.

**Decision.** Front end: dynamic blocks with `render.php`. Editor: option 2. It reacts instantly to the Inspector controls, uses the editor's data cache, and the WordPress team recommends it over `ServerSideRender` for new blocks.

**Filtering in the editor.** The Posts Filter block shows a static preview in the editor (the real category and tag checkboxes), but it does not filter the grid preview. The brief asks for a *front-end* filtering UI, and the editor is for building and styling the page, not for browsing content. Connecting them in the editor would add state that has no meaning for the saved page.

**Consequences.** The card markup exists twice: in `render.php` and in the editor component. They share class names and one stylesheet, so they look the same. This duplication is a deliberate tradeoff.

---

## ADR-004 — Pagination as an inner block

**Context.** Pagination must be an inner block of the grid. WordPress renders inner blocks **before** their parent, so the pagination block cannot receive the parent's `WP_Query` object directly.

**Decision.**
- `elvpg/posts-pagination` is a separate block with `"parent": ["elvpg/posts-grid"]`, so it can only live inside a grid.
- The grid shares its settings (posts per page, a grid id) with the pagination through **block context** (`providesContext` / `usesContext`).
- Both blocks call one query service, `Elvpg_Query`, with the same arguments. The service keeps a static per-request cache, so the database query runs only once.
- The page number uses its own query var, `elvpg_page`, not `paged`. On a static page WordPress already uses `page`/`paged` for its own pagination, and a custom var avoids conflicts with the theme and with other blocks. It also works with any permalink structure, including plain permalinks.

- The page numbers come from `Elvpg_Query::pagination_items()`, a pure function with unit tests. Short lists show every page; long lists show the first and last page, the current page with its neighbours, and "…" for skipped pages: `1 … 5 6 7 … 12`.
- In the editor, the pagination preview reads "posts per page" from the same block context, so it shows the real number of pages and updates as soon as the grid setting changes.

**Consequences.** When the filters change, the page resets to 1. A page number beyond the last page shows the empty state with a "Clear filters" link, which also removes the page number. Page links keep the current filters and change only `elvpg_page`; page 1 has no page parameter, so each page has one URL.

---

## ADR-005 — Filter logic: OR within a type, AND across types

**Decision.** The query service builds one `tax_query`:

```php
array(
    'relation' => 'AND',
    array( 'taxonomy' => 'elvpg_category', 'field' => 'term_id', 'terms' => $categories, 'operator' => 'IN' ),
    array( 'taxonomy' => 'elvpg_tag',      'field' => 'term_id', 'terms' => $tags,       'operator' => 'IN' ),
)
```

`IN` gives OR inside one filter type, and `relation => AND` gives AND across types. A type with no selection adds no clause. No selection at all shows every post.

**Input from the URL.** The filter state comes from public GET parameters, so it is treated as untrusted:
- `parse_ids()` accepts both `elvpg_cat=3,5` (the URL the JavaScript filter builds) and `elvpg_cat[]=3&elvpg_cat[]=5` (what the HTML form sends without JavaScript). Anything that is not a positive whole number is dropped. IDs are sorted and unique, so the same selection always gives the same cache key.
- `parse_page()` turns any invalid page number into 1.
- `clamp_per_page()` keeps "posts per page" between 1 and 24, so a hand-edited block attribute cannot request thousands of posts.
- No nonce is needed: this is a public, read-only request, like a search form.

**Extensibility.** The final arguments pass through the `elvpg_query_args` filter, so a site can change the ordering or add a meta query without editing the plugin.

The builder is a pure function (term IDs in, query args out), so it is covered by fast unit tests that do not need WordPress (ADR-009).

---

## ADR-006 — Progressive enhancement and accessibility

**Decision.**
- The filter is a real `<form method="get">` with checkboxes in `<fieldset>`/`<legend>` groups and a submit button. With JavaScript, changes apply instantly and the button is hidden. Without JavaScript, the form submits and the server renders the filtered grid.
- The form keeps the other parameters of the current URL as hidden fields. Without this, with plain permalinks (`?page_id=…`) a no-JS submit would leave the page.
- The checkboxes are native inputs styled as chips, so keyboard and screen reader support comes from the browser. Focus stays on the checkbox after a change.
- Pagination is a `<nav>` with real links and `aria-current="page"`. Ctrl/Cmd-click still opens a page in a new tab.
- After a client-side update, a polite `aria-live` region announces the result ("4 posts found. Page 1 of 2."). When the page changes, focus moves to the top of the grid, so keyboard users do not stay at the bottom.
- While the next page loads, the grid has `aria-busy="true"` and is dimmed. Smooth scrolling is turned off for users who prefer reduced motion.
- Empty state: "No posts match these filters" with a "Clear filters" link.

---

## ADR-007 — Demo content seeding

**Decision.**
- Seeding runs in the activation hook. The post type is registered there explicitly, because `init` has already fired for that request.
- Everything the plugin creates is marked with the `_elvpg_demo` meta key. Activating again does not create duplicates.
- Featured images are bundled in `assets/seed/` and imported as real attachments with generated sizes and alt text. Activation needs no network access.
- **Multisite.** On network activation the seeder runs on every site with `switch_to_blog()`. Sites created later get the demo content through the `wp_initialize_site` hook (priority 900, after core has created the site's tables).
- **Rewrite rules on Multisite.** After `switch_to_blog()` the global `$wp_rewrite` still holds the permalink settings of the original site, so `flush_rewrite_rules()` would save wrong rules for the other sites. On a switched site the seeder deletes the `rewrite_rules` option instead, and WordPress rebuilds the rules on that site's next request.

**Consequences.** On very large networks, seeding every site during one request could time out. A WP-CLI command or a background queue would be the next step; this is listed under known limitations.

---

## ADR-008 — Deactivation and uninstall

**Decision.** Deactivation keeps all content and only resets rewrite rules: deactivating a plugin should never delete a user's data. `uninstall.php` removes only what the plugin created, on every site of a network:
- posts, attachments (with their files) and the demo page marked with the `_elvpg_demo` post meta;
- terms marked with the `_elvpg_demo` term meta. Only terms the seeder created get this mark. A term that already existed on the site is reused but never marked, so it is never deleted;
- the `elvpg_demo_page_id` option.

Posts and terms that users added themselves stay in the database. This follows the usual WordPress practice: deleting a plugin should not silently delete the site owner's own work.

---

## ADR-009 — Testing strategy

**Decision.**
- **Unit tests (PHPUnit 9.6, the last version that supports PHP 7.4)** for the query builder: categories only, tags only, both, empty input, invalid input. This covers the core rule of the brief. The tests do not load WordPress, so they run in milliseconds.
- **Integration check during development:** every combination of up to two categories and up to two tags (319 combinations) was run through a real `WP_Query` and compared with the expected result computed by hand from the seeded data. It is a one-off script, not part of the repository: the unit tests guard the logic, and this check confirmed that WordPress reads the generated `tax_query` the way we expect.
- **Linting:** WordPress Coding Standards via PHPCS; `wp-scripts lint-js` and `lint-style`.
- **Manual checklist** in the README for the UI: filter combinations with expected results, pagination, no-JS mode, keyboard use, block placement in different orders.

Full end-to-end tests were left out on purpose. They would add setup time and fragility without much extra confidence for a project of this size.

---

## ADR-010 — Delivery

**Decision.** A GitHub repository with step-by-step commits, plus a release zip that **includes the compiled `build/` folder**. This way the plugin works right after "Upload → Activate" with no `npm` step. The minimum requirements are WordPress 6.5 and PHP 7.4.
