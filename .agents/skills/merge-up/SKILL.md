---
name: merge-up
description: >
  Cascade-merge the maintained Symfony UX branches from oldest to newest (2.x -> 3.x),
  resolve conflicts, run the affected packages' tests and prepare the push. Use when the
  user wants the maintained branches merged up or synced.
---

# Symfony UX Branch Cascade Merge

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

The maintained branches, oldest first:

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
```

Read the merge-up notes of the incoming pull requests first. The bot copies the whole pull request description into the merge commit, so the authors' own instructions for this merge are already in the local history. A description can end with a "Merge-up to `<branch>`" paragraph naming the declarations to add, the style the target branch expects, or the resolved code itself:

```bash
git log --merges --format=%H <TARGET>..<SOURCE> \
  | xargs -I{} git log -1 --format=%B {} \
  | grep -inE 'merge(d| )?-?up|adapt|on [0-9]+\.x'
```

Prefer the resolution a note gives over an equivalent one of your own: it is what the author wrote the change for and usually ran on the target branch already, and a merge commit is the wrong place for a refactor. A note also covers files that merge cleanly, which is why this runs before the merge and not only when git reports a conflict.

```bash
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

Read each conflicted file, resolve it, then `git add` it by name. When all are resolved:

```bash
git commit --no-edit
```

#### Conflict resolution rules

| File pattern | Strategy |
|---|---|
| `CHANGELOG*.md` | Keep entries from both sides; newer branch entries on top |
| `version` in `assets/package.json`, version constants | Keep the TARGET branch value |
| `assets/dist/**` | Never merge by hand. Resolve the sources first, then rebuild the package on the TARGET (`cd src/<NAME>/assets && pnpm run build`) and stage the result: CI's "Dist Files Unbuilt" job fails when `dist/` does not match a fresh build |
| `.github/workflows/*.yaml`, CI config | Keep the TARGET value for branch-specific pins. A job merged from SOURCE may carry SOURCE's `php-version` (2.x runs PHP 8.1+); bump it to the TARGET's minimum (3.x runs PHP 8.4+) |
| Idiom the TARGET replaced (logic extracted to a trait, a method or class removed) | Take the TARGET version; the SOURCE change is superseded. `git checkout --ours <file>`, then re-apply the part of the SOURCE fix the TARGET still needs |
| File the TARGET deleted (modify/delete conflict) | Keep it deleted if the TARGET removed the feature (confirm with `git log <TARGET> -- <file>`); the SOURCE edit is moot. `git rm <file>` |
| Test using docblock metadata (`@dataProvider`, `@testWith`, `@group legacy`) | Convert to attributes (`#[DataProvider(...)]`, `#[TestWith([...])]`, `#[Group(...)]`). 3.x runs PHPUnit 11/12, where doc-comment metadata is deprecated and then ignored, so the data sets are never passed and the test errors with "too few arguments" |
| Compat guard added by SOURCE (`class_exists()` / `method_exists()` fallback for a symbol an older dependency may lack) | Check whether the TARGET dropped it on purpose: `git log <TARGET> -S'<guard text>' -- <file>`. If it did, take SOURCE's new structure but leave the guard out |
| Both sides added a member at the same spot (no overlapping content, git just collapsed them onto a shared closing) | Keep both. Give each its own terminator: two `elseif` branches each need their own `return`/closing brace, and two methods each need their own `}`. Private methods go last, after all public ones |
| Code files | Merge logically based on context; when unsure, ask the user |

#### Structural divergence across major versions

3.x removed what 2.x deprecated and raised the minimum PHP from 8.1 to 8.4. When merging 2.x into 3.x:

- Remove test methods marked `@group legacy` / `#[Group('legacy')]`: the deprecations they cover are gone in 3.x. Also remove any test, fixture or import that references a removed symbol, otherwise it fatals on the TARGET.
- Code merged from SOURCE that branches on or polyfills a PHP below 8.4 (`\PHP_VERSION_ID < ...` guards, or `function_exists()` / `class_exists()` fallbacks for symbols that are always available) is dead on 3.x and can be collapsed to the modern path. The TARGET usually dropped it already, so prefer its version; clean up only where SOURCE's old-PHP code lands somewhere the TARGET had not simplified.
- New `test*` methods from 2.x arrive without a return type, while 3.x declares `: void` on them (its php-cs-fixer config applies `void_return` to `tests/`). Run `php vendor/bin/php-cs-fixer fix <file>` on the merged test files.
- Prefer the TARGET branch's approach for any refactored idiom.

#### Divergence a clean merge hides

Most of these produce no conflict at all: the merge succeeds and the tests fail. They show up as a merged test that is fine on SOURCE and wrong on TARGET.

- **A config key the TARGET removed or deprecated.** A merged test builds a bundle config that the TARGET no longer accepts. The symptom is `Unrecognized option "x"` with the valid list attached, or a deprecation the run reports. Drop the key if it is boilerplate rather than what the test is about. Grep the whole merge diff for the key, since several merged tests usually carry it.
- **A default the TARGET flipped.** SOURCE adds a code path behind a flag whose default the TARGET changed, so the new path becomes the TARGET's default and changes observable output. Both sides merge cleanly and the assertions SOURCE wrote for the old path now fail. Confirm with `git log <TARGET> -S'<flag> = <value>'`, then adapt the expectations, capturing the real output from a run rather than guessing at it.

After resolving, show `git diff HEAD~1` (first parent of the merge commit, i.e.
the previous TARGET state) and wait for the user to confirm the resolution looks
correct before proceeding.

### 2c. Run tests for affected packages

Extract package and bridge names from changed files:

```bash
git diff --name-only HEAD~1..HEAD
```

Paths look like `src/<NAME>/...`, or `src/<NAME>/src/Bridge/<BRIDGE>/...` for a bridge, which has its own `composer.json` and test suite. Deduplicate, then run the tests from each directory. Each package has its own `vendor/` and the branches pin different dependencies, so update it first:

```bash
(cd <DIR>; composer update; symfony php vendor/bin/phpunit)
```

When `src/<NAME>/assets/` changed, also run that package's JS tests: `(cd src/<NAME>/assets; pnpm run test:unit)`.

Ignore files outside these directories (root configs, `.github/`, etc.): they
don't have package-level test suites.

Read the whole summary line, not just the exit status: a suite can end with `Tests: N, Failures: 1` or abort on a `Fatal error` well before any `FAILURES!` banner, and ANSI colour codes sit in front of those words, so a check anchored to the start of a line reports a red run as green.

If tests fail or report PHPUnit deprecations (2.x runs phpunit-bridge, 3.x PHPUnit 11/12), first check whether the failure is pre-existing. Cheapest test first: if the merge did not touch the failing area, it did not cause the failure.

```bash
git diff --name-only HEAD~1..HEAD -- <path of the failing test or the code it covers>
```

Only when that is inconclusive, run the test on the TARGET before the merge (`git checkout HEAD~1`, run, `git checkout <TARGET>`). Beware a CI baseline as evidence: a branch tip that has not been pushed in a while keeps an old green run, and CI installs dependencies fresh on every run, so a release made in between can turn a suite red with no commit to blame.

Only fix failures introduced by the merge:

1. Analyze and fix the code, including any PHPUnit deprecation notices.
2. Commit the fix: `[<ComponentName>] Fix merge conflict resolution`.
3. Re-run failing tests until green and deprecation-free.

Report any pre-existing failures to the user without attempting to fix them.

#### Failures a local run cannot show

A local `composer update` installs the sibling UX packages a package requires (e.g. `symfony/ux-twig-component` for LiveComponent) from Packagist, at their released version. CI first runs `.github/build-packages.php`, which rewrites every `composer.json` to use the sibling packages from the checkout. A merged test that relies on a sibling change from the same merge can therefore fail locally and pass in CI. To reproduce CI, run `php .github/build-packages.php` before `composer update`, and restore the rewritten `composer.json` files with `git checkout -- '*composer.json'` before committing anything.

CI also runs a lowest-dependencies job and jobs pinned to specific Symfony versions, while a local run gets the newest versions the constraints allow. An assertion pinned to a Symfony component's exception message can break when a newer release appends to it: assert the part that identifies the failure and leave the tail free (drop a trailing `.`, or use `expectExceptionMessageMatches()`), and fix it on the oldest branch that has the test so the cascade carries it up.

Before writing a CI failure off as flaky, check that the group meant to exclude it is actually excluded: the jobs pass `--exclude-group skip-on-lowest` and `--exclude-group transient-on-windows`, and on 3.x the group has to be an attribute, since PHPUnit 11 deprecates doc-comment metadata and PHPUnit 12 ignores it.

### 2d. Ask for confirmation before pushing

Show:

```
Merge: <SOURCE> -> <TARGET>
Affected: <package list>
Tests: all passing

Commits since upstream/<TARGET>:
git log --oneline upstream/<TARGET>..<TARGET>

Ready to push? (yes / no)
```

**Wait for confirmation.** The user may make changes themselves before confirming.

### 2e. Hand over the push and continue

The user pushes. Print the command for them to run:

```bash
git push upstream <TARGET>
```

Wait until they report it done. If the push fails, **stop** and let them handle it.

Print "✓ `<SOURCE>` -> `<TARGET>` done." and continue to the next pair.

---

## Final summary

```
All merges complete:
  2.x -> 3.x  ✓
```

---

## Gotchas

- `CHANGELOG.md` conflicts are the most common; entries must be kept from both sides, never dropped.
- A merge can introduce test failures even without conflicts, because behavior from the older branch may be incompatible with newer code. Always run tests.
- A **clean (no-conflict) merge still needs verification**, not just a commit: auto-merged test metadata (docblock vs attribute), a rebuilt `dist/`, and CI version pins can each be wrong even when git reports no conflict.
- Some packages have slow test suites. Only run tests for packages with changed files, not the entire project.
- When the user states a constraint about the merge as a whole, it applies to the entire merge diff, not only to the files git flagged as conflicting. Grep the whole diff for the concept and check every hit, including other packages and their bridges. Auto-merged hunks are where a constraint like that gets lost silently.
- Resolving only the conflicted hunk of a file leaves the rest of it auto-merged. When the two sides restructured the same file, read the resolved file end to end before staging it, otherwise it can end up declaring the same thing twice or dropping a `return`.

## Error handling

- Never force-push or rewrite history.
- Never use `--no-verify` on commits.
- Never `git add -A` (or `git add .`) while resolving: it sweeps the user's untracked working files into the merge commit. Stage the files you resolved, by name.
- Never `git reset` in the middle of a merge: it deletes `.git/MERGE_HEAD`, and the commit that follows records a single parent, silently turning the merge into a squash.
- Never auto-recover from a failed `git pull`. Stop and hand control back to the user.
- Never parallelize the cascade or run branches concurrently (e.g. via subagents): each merge depends on the previous one and shares the git working tree. Run strictly oldest to newest, one at a time.
