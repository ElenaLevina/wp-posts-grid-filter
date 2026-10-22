---
name: feature
description: Plan and implement a change to the blocks (a new setting, filter, card field, sort order…) the way this repo expects — plan, mirrored PHP/JS markup, tests, checks, docs.
argument-hint: <what to add or change>
---

Task: $ARGUMENTS

1. **Understand.** Read `CLAUDE.md` (invariants) and the ADRs in `docs/decisions.md` that the task touches. If the task conflicts with a recorded decision, say which one and ask before going on.
2. **Plan.** Before editing, write a short plan:
   - files to change, including the mirrored pairs (`render.php` ↔ `post-card.js`, `Elvpg_Query::pagination_items()` ↔ `items.js`) and `block.json` if there is a new attribute;
   - how the state travels: block attribute, URL parameter (`Elvpg_Query`) or block context;
   - how it works without JavaScript;
   - how to check it (unit test, page URL, editor).
   Wait for approval of the plan.
3. **Implement** in small steps. Keep WordPress 6.6 / PHP 7.4 compatibility, WPCS, escaping, translatable strings, accessibility.
4. **Test.** Add or update PHPUnit tests in `tests/Unit/` for new pure logic.
5. **Check.** Run `/verify`.
6. **Document.** Update `README.md` if the user sees the change. Add an ADR with `/adr` if a real choice between options was made.
7. **Report.** What changed and why, file by file, and a suggested commit message. Do not commit.
