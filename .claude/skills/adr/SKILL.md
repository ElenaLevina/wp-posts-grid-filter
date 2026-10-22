---
name: adr
description: Add an architecture decision record to docs/decisions.md in the existing format.
argument-hint: <decision title>
---

Add a new ADR to `docs/decisions.md` for: $ARGUMENTS

- Number it after the last one (`## ADR-0NN — Title`) and separate it with `---` like the others.
- Use the same parts: **Context.**, **Options.** (numbered, at least two real options), **Decision.**, **Consequences.** (what it costs, what it rules out).
- Plain, short sentences. Name the real files, functions and hooks.
- If the decision changes an older ADR, add a line to the older one that points to the new one instead of rewriting history.

Show the new ADR text for review before saving.
