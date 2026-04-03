---
name: symfony-ux-merge-up
description: >
    Cascade-merge maintained Symfony UX branches from oldest to newest (e.g. 2.x to 3.x).
    Use when the user says "merge branches", "merge up", "cascade merge", "sync branches", or "update branches".
---

# Symfony Branch Cascade Merge

Merges each maintained branch into the next one, from oldest to newest.

## Progress checklist

- [ ] Step 0: Pre-flight checks
- [ ] Step 1: Fetch maintained branches and pull them
- [ ] Step 2: Cascade merge loop

---

## Confirmation rule

Whenever the skill says **"Wait for confirmation"**, treat anything other than an
explicit affirmative as **no**: stop and ask the user how they want to proceed.

---

## Step 0 — Pre-flight checks

```bash
git status --porcelain --untracked-files=no
```

If any output, **stop**:

> "The working tree is not clean. Please commit or stash your changes first."

---

## Step 1 — Fetch maintained branches and pull them

### 1a. Get the branch list

Unlike Symfony, Symfony UX has no https://symfony.com/releases.json endpoint.

Instead, the maintained branches are:

- 2.x
- 3.x

Store them as `BRANCHES`.

### 1b. Pull every branch

For each branch in `BRANCHES`:

```bash
git checkout <branch>
git pull --ff-only upstream <branch>
```

Using `--ff-only` ensures local branches haven't diverged from upstream. If the
pull fails, **stop** and report the error.

---

## Step 2 — Cascade merge loop

For each consecutive pair `(SOURCE, TARGET)` in `BRANCHES`:

### 2a. Merge

```bash
git checkout <TARGET>
composer up
git merge <SOURCE>
```

Three outcomes are possible:

- **Already up-to-date:** print "✓ `<TARGET>` already up-to-date with `<SOURCE>`"
  and skip to the next pair.
- **Clean merge (no conflicts):** git creates the merge commit automatically.
  Proceed directly to step 2c.
- **Conflicts:** proceed to step 2b.

### 2b. Resolve conflicts (only when git reports conflicts)

List conflicts:

```bash
git diff --name-only --diff-filter=U
```

Read each conflicted file, resolve it, then `git add` it. When all are resolved:

```bash
git commit --no-edit
```

#### Conflict resolution rules

| File pattern                                      | Strategy                                                    |
| ------------------------------------------------- | ----------------------------------------------------------- |
| `CHANGELOG*.md`                                   | Keep entries from both sides; newer branch entries on top   |
| Version constants, `composer.json` branch aliases | Keep the TARGET branch value                                |
| Code files                                        | Merge logically based on context; when unsure, ask the user |

#### Legacy tests across major versions

When merging from one major version to the next (e.g. 2.x → 3.x), remove test
methods marked with `@group legacy` or `#[Group('legacy')]`. The deprecations
they cover have been removed in the new major version, so the tests are no
longer relevant.

After resolving, show `git diff HEAD~1` (first parent of the merge commit, i.e.
the previous TARGET state) and wait for the user to confirm the resolution looks
correct before proceeding.

### 2c. Run tests for affected components

Extract component, bridge, and bundle names from changed files:

```bash
git diff --name-only HEAD~1..HEAD
```

Paths look like `src/<NAME>/...`. Deduplicate,
then run tests for each:

```bash
(cd src/<NAME>; symfony php vendor/bin/phpunit)
```

Ignore files outside these directories (root configs, `.github/`, etc.): they
don't have component-level test suites.

If tests fail or report PHPUnit deprecations (the PHPUnit version may differ
between branches), first check whether the failure is pre-existing: run the same
test on the TARGET branch before the merge (`git stash && git checkout HEAD~1`
or check CI). Only fix failures introduced by the merge:

1. Analyze and fix the code, including any PHPUnit deprecation notices.
2. Commit the fix: `[<ComponentName>] Fix merge conflict resolution`.
3. Re-run failing tests until green and deprecation-free.

Report any pre-existing failures to the user without attempting to fix them.

### 2d. Ask for confirmation before pushing

Show:

```
Merge: <SOURCE> → <TARGET>
Affected: <component list>
Tests: all passing

Commits since upstream/<TARGET>:
git log --oneline upstream/<TARGET>..<TARGET>

Ready to push? (yes / no)
```

**Wait for confirmation.** The user may make changes themselves before confirming.

### 2e. Push and continue

```bash
git push upstream <TARGET>
```

If the push fails, **stop** and report the error.

Print "✓ `<SOURCE>` → `<TARGET>` done." and continue to the next pair.

---

## Final summary

```
All merges complete:
  2.x → 3.x  ✓
```

---

## Gotchas

- `CHANGELOG.md` conflicts are the most common; entries must be kept from both
  sides, never dropped.
- A merge can introduce test failures even without conflicts, because behavior
  from the older branch may be incompatible with newer code. Always run tests.
- Some components have slow test suites. Only run tests for components with
  changed files, not the entire project.

## Error handling

- **Never** force-push or rewrite history.
- **Never** use `--no-verify` on commits.
- **Never** auto-recover from a failed `git push` or `git pull`. Stop and hand
  control back to the user.
