---
name: verify
description: Run all checks for the plugin — ESLint, Stylelint, PHPCS, PHPUnit and the build — and report the result. Use after any code change, before suggesting a commit.
---

Run the checks one by one and stop at the first failure only if the next step depends on it.

1. `npm run lint:js`
2. `npm run lint:css`
3. `npm run lint:php` — needs wp-env running (`npm run env:start`). If wp-env is not running, say so and skip PHPCS and PHPUnit.
4. `npm run test:php`
5. `npm run build`

If a lint error can be fixed safely (formatting, a missing escape, a Yoda condition), fix it, then run that check again. Do not silence a rule with `phpcs:ignore` or `eslint-disable` without a reason in the comment.

Finish with a short table: check, result, what was fixed. If PHP or JS files in `src/` changed, remind to reload `http://localhost:8888/?pagename=elvpg-demo` and check the page with JavaScript on and off.
