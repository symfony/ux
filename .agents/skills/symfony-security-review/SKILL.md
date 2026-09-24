---
name: symfony-security-review
description: >
  Review a change (a PR, the current branch diff, or a set of files) or audit a Symfony UX package or the whole `src/` tree for missing or incorrect security hardening. Reasons about trust boundaries from first principles, then checks the code against the hardening families catalogued from ux's past security fixes. Use when asked for a security review or a security audit of ux code, whether a change weakens a security control, or to hunt a vulnerability class across the packages.
---

# Symfony UX Security Review

Finds hardening that is missing or wrong, grounded in code. It runs in two modes:

- **Review a change** (default): a PR, the current branch vs its base, or named files.
- **Audit a target**: a package path (e.g. `src/LiveComponent`) or the whole `src/` tree, for one or all families.

Both modes run two passes: first reason about the change's trust boundaries from first principles (to catch novel issues), then check it against the hardening families catalogued in [`hardening-families.md`](hardening-families.md). Each family comes from a real ux fix; the catalogue is a checklist of known classes, not the search space.

## Scope and non-goals

- This skill **spots** missing hardening. It does not triage an incoming report end to end, assign a CVE, or merge a fix. `security-triage` makes the disclosure call.
- **Every finding must point at a real sink** (a concrete file and line). No speculative findings. A family with no sink in scope is not in scope; but a sink that matches no family is still in scope (that is what Step 1 is for).
- Respect the decision boundaries in the catalogue: several plausible-looking shapes are documented app responsibilities. Do not raise those.

## Progress checklist

- [ ] Step 0: Pick mode and resolve the scope
- [ ] Step 1: First-principles boundary pass (open-world)
- [ ] Step 2: Map to families (known-class checklist)
- [ ] Step 3: Check the automated gates
- [ ] Step 4: Manual per-family review
- [ ] Step 5: Report findings

## Confirmation rule

Whenever this skill says **"Wait for confirmation"**, treat anything other than an explicit affirmative as **no**: stop and ask the user how they want to proceed.

## Parallelism (large audits only)

When auditing the whole `src/` tree or a large package, fan Step 1 and Step 4 out to subagents: one per family, or one per package, each returning findings that point at a concrete file and line (no speculative findings leaking in through parallelism). Keep the rest in the main loop: aggregation, de-duplication, the completeness check, and the report are synthesis and must see every finding at once. For a single PR or a small diff, run every step inline; subagents are pure overhead there.

---

## Step 0 — Pick mode and resolve the scope

**Review a change.** Check the PR out locally so the whole package is readable, and diff against its real base:

```bash
# A public PR
git worktree add --detach .claude/worktrees/pr<n>
cd .claude/worktrees/pr<n> && gh pr checkout <n>
gh pr view <n> --json baseRefName --jq .baseRefName
git diff upstream/<base>...HEAD --stat
# The current branch against its base (2.x or 3.x)
git diff upstream/<base>...HEAD --stat
```

**Audit a target.** Take a package path or the whole `src/` tree. There is no diff; the "changed files" are the target files.

In both modes, build the file list from `src/<Package>/src/` (PHP), `src/<Package>/assets/src/` (TypeScript), `src/<Package>/config/` and `src/<Package>/templates/`. Drop `tests/`, `assets/test/` and the built `assets/dist/`: hardening lives in the implementation, and `dist/` is generated from `assets/src/`.

State the resolved mode and scope back to the user in one line before continuing.

## Step 1 — First-principles boundary pass (open-world)

This pass finds what the catalogue does not list. Do it before mapping to families, and do not let the anchors narrow it. For each file in scope, reason as an attacker, independent of any known family:

1. **Inputs**: what untrusted data can reach this code? In ux: the LiveComponent request payload (`props`, `updated`, `propsFromParent`, `children`, action arguments, `_batch` actions), the Autocomplete `query` and `extra_options`, AJAX responses a Stimulus controller renders, Iconify API responses and local SVG files, a third-party Toolkit kit (`--kit=https://github.com/...`: its manifest and files), values an app passes into component attributes, request headers.
2. **Flow**: follow each input to where it is used. Does it reach a sink (HTML output from PHP or from a TypeScript template literal / `innerHTML`, a Twig function declared `is_safe`, DQL/SQL, the filesystem, a sub-request, object construction from a client value, a comparison of a secret)?
3. **Boundary**: which trust boundary does it cross, and who is the expected actor (unauthenticated visitor, lower-privileged user, a cross-origin page, the author of a remote kit)?
4. **Worst case**: if the attacker fully controls the input, what is the maximum impact? State it concretely (file write, XSS, CSRF, forged props, data disclosure, DoS).

Record every input-to-sink path with a non-trivial worst case as a candidate finding, **whether or not it matches a catalogued family**. An unguarded path from untrusted input to a dangerous sink is a finding even if no anchor names it. This step uses no greps by design; it is meant to see sinks the dictionary misses.

## Step 2 — Map to families (known-class checklist)

Now apply the catalogue as a checklist, to confirm no *known* class slipped past Step 1. **Treat each anchor's listed APIs as seed examples of an abstract role** (a checksum over client data, a client value interpolated into HTML, a list that fans out into sub-requests, a path built from a manifest); extend to anything in scope that plays that role, including code the grep does not name.

For each file in scope, decide which families it touches using the anchors in [`hardening-families.md`](hardening-families.md). Run the anchors against the scope, not the whole tree, when reviewing a change:

```bash
# Example: which families does this branch touch?
git diff upstream/<base>...HEAD --name-only | grep -vE '/tests/|/assets/test/|/assets/dist/' > /tmp/scope.txt
grep -lE 'hash_hmac|hash_equals|is_safe|innerHTML|new \$|LIKE|Path::|copy-files|_batch|X-Requested-With' $(cat /tmp/scope.txt) 2>/dev/null
```

Carry forward both Step 1's boundary findings and the families with a sink in scope; they proceed to Step 4. List them.

## Step 3 — Check the automated gates

ux has no automated hardening gate for its PHP or TypeScript code, so every family goes through Step 4. Two checks exist around it:

- **zizmor** runs on every PR (`.github/workflows/zizmor.yaml`) and covers GitHub Actions workflows (family W1).
- **PHPStan** runs only for `src/Turbo` (`phpstan.dist.neon`), with no security-specific rule.

## Step 4 — Manual per-family review

For each in-scope family, apply its invariant from [`hardening-families.md`](hardening-families.md). The catalogue gives, per family: the anchor, the invariant, the fix it comes from, and the decision boundary.

Work the sinks, not the families in the abstract: for every sink site the anchor found, answer the family's check question. If the answer is "no guard / wrong guard", it is a candidate finding; confirm it is not excluded by the decision boundary before reporting.

## Step 5 — Report findings

Output a table, highest severity first:

| Location | Family | Severity | Invariant at risk | Suggested hardening | Regression test |
|---|---|---|---|---|---|
| `src/LiveComponent/src/Util/ChildComponentPartialRenderer.php:NN` | L5 live-markup | Medium | client tag interpolated into HTML | validate against `VALID_TAG` | malicious-tag case in `InterceptChildComponentRenderSubscriberTest` |

**Severity rubric**:

- **Critical**: pre-auth RCE or full auth bypass.
- **High**: arbitrary file read/write, signature/secret bypass that accepts forged props.
- **Medium**: stored/reflected XSS on a default-rendered surface, sensitive data leak.
- **Low**: DoS / resource exhaustion, lenient parsing with bounded impact, defense-in-depth only.
- **Not a finding**: excluded by a decision boundary (say which one).

When a finding moves on to `security-triage`, the level becomes the proposed GHSA severity (Critical and High both propose `high`).

For each real finding, state whether a **regression test** is required at the boundary, in the shape ux already uses: a malicious input that must be rejected (the `tests/Fixtures/kits/malicious` kit for the Toolkit installer, the malicious child tags in `InterceptChildComponentRenderSubscriberTest`).

**Completeness check (before you finalise).** State explicitly: is there an untrusted input, a sink, a checksum, an HTML output, or a trust boundary in scope that matched **no** anchor and was not already raised in Step 1? If so, reason about it from scratch before reporting. A clean family sweep is not a clean review.

The table holds findings from both passes: the Step 1 boundary pass (Family column = the vulnerability class, or `novel`) and the Step 4 family review.

Separate **confirmed** findings from **needs-human-judgement** ones. Do not inflate.

---

## If a finding is real: fix handoff

- A CVE-class finding goes to `security-triage` first and stays private: no public issue, PR, commit, or pushed branch before the coordinated release (`origin` is a public fork too). **Wait for confirmation.**
- **TDD**: write the failing regression test first, then the fix.
- **Package-scoped tests only**: `cd src/<Package> && composer update && php vendor/bin/phpunit`. For a TypeScript fix, also `pnpm run test:unit` and `pnpm run build` in `src/<Package>/assets`, and commit `dist/`.
- When a limit exists on both sides (PHP and TypeScript), change both in the same commit.
- No `Co-Authored-By`, no Claude/Anthropic credit. Comments sparingly, and do not reference issue numbers in code or tests.
- Never run `git push`; print the command for the user.

## Gotchas

- **Decision boundaries are real.** A `writable: true` LiveProp is client-controlled by design; `options_as_html: true` renders raw HTML on purpose; an autocompleter with the default `security: false` is public by documentation; the Icons sanitizer keeps `<style>` on purpose. The catalogue lists these; respect them or you will cry wolf.
- **Data families are answer-keys.** The Icons forbidden-element and URL-scheme lists and the LiveComponent `VALID_TAG` regex are curated sets; a review only confirms them, it cannot prove the next missing entry. Flag gaps for a data-provider test.
- **Paired limits drift.** `MAX_ACTIONS_PER_BATCH` exists in `BatchActionController` and in `assets/src/Component/index.ts`; changing one side alone either breaks the client or reopens the cap.
- **Escaping has to be portable.** The Autocomplete LIKE escape character is `!` because `\` produced invalid SQL on PostgreSQL (#3685). A fix that escapes correctly on one database and breaks another is not done.
- **Anchors are seed examples, not the search space.** Novel issues surface in the Step 1 first-principles pass, not the greps. A finding that matches no family is still a finding; do not let the dictionary bound the review.

## Error handling

- Never fabricate a sink. If the anchor finds nothing, say the family is out of scope.
- Security reports are handled privately. Do not echo report contents into public artifacts.
- When unsure whether something crosses a decision boundary, report it as needs-human-judgement with the boundary named, and **wait for confirmation**.
