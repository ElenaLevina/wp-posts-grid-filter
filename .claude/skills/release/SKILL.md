---
name: release
description: Build the release zip and check what is inside before it goes to a GitHub release.
disable-model-invocation: true
---

1. Run `/verify`. Stop if anything fails.
2. Run `npm run release`.
3. Run `unzip -l elvpg-posts-grid-filter.zip` and check:
   - one root folder `elvpg-posts-grid-filter/`;
   - `build/` with all three blocks, `includes/`, `assets/seed/` (36 images), `readme.txt`, `uninstall.php`, the main plugin file;
   - no `src/`, `tests/`, `node_modules/`, `vendor/`, dotfiles, `README.md` or config files.
4. Check that `Version:` in `elvpg-posts-grid-filter.php`, `ELVPG_VERSION` and `Stable tag:` in `readme.txt` match.
5. Remind the developer of the manual steps:
   - Plugin Check on the runtime files only:
     `npx @wordpress/env run cli wp plugin check elvpg-posts-grid-filter --exclude-directories=tests,docs,bin,src,node_modules,vendor,.claude --exclude-files=.DS_Store,.wp-env.json,.gitignore,.phpunit.result.cache,phpcs.xml.dist,phpunit.xml.dist,README.md,CLAUDE.md,package.json,package-lock.json,composer.json,composer.lock,eslint.config.cjs,elvpg-posts-grid-filter.zip`
   - a GitHub release with the zip attached, and a note to download it instead of the "Source code" archives.
