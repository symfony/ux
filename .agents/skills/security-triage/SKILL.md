---
name: security-triage
description: >
  Triage a security finding in a Symfony UX package into a disposition: a private CVE (coordinated disclosure through the Symfony security process), a public hardening PR (fix in the open, no CVE), or not-a-security-issue (reply to the reporter). Assigns severity and the affected maintained branches, and routes to the next step. Use when deciding whether a report or a discovered weakness needs a CVE, how it should be disclosed, or whether it is a security issue at all.
---

# Symfony UX Security Triage

Decides how a finding is handled, not whether the code is wrong. It complements `symfony-security-review` (which finds missing hardening) by making the disclosure call on a report.

Symfony UX follows the Symfony security process (https://symfony.com/security): reports go privately to security@symfony.com, the security team works on the fix in a private Git repository, and the release publishes a GitHub Security Advisory (GHSA) with a CVE and a severity. The fix lands as a merge titled `security #cve-<year>-<number> [<Package>] <title>`, for example `security #cve-2026-55878 [Toolkit] Harden recipe installer against path traversal`. A security fix is always published as one, never disguised as a refactor or a routine bug fix: users decide whether to upgrade from that signal.

The three dispositions:

| Disposition | Branch | Process |
|---|---|---|
| **CVE** | `cve-<year>-<number>` | Private fix, GHSA + CVE with severity, reporter credit, coordinated release, merge titled `security #cve-...` |
| **Public hardening** | normal topic branch | Normal open PR, merged as `bug #<number>`, no embargo |
| **Not a security issue** | none, or a normal topic branch | Reply to the reporter; optionally a doc or robustness PR |

The symfony/ux repository has no security or severity labels: the severity lives in the GHSA.

This skill produces a recommendation. The final call belongs to the Symfony security team; treat its output as a structured argument, and defer to https://symfony.com/security for the authoritative list of what is not a vulnerability.

## Progress checklist

- [ ] Step 0: Establish the facts (reproduce, scope, trust model)
- [ ] Step 1: Apply the disposition decision tree
- [ ] Step 2: Assign severity and affected maintained branches
- [ ] Step 3: Route (branch, next workflow, reporter reply)

## Confirmation rule

Whenever this skill says **"Wait for confirmation"**, treat anything other than an explicit affirmative as **no**: stop and ask the user how they want to proceed.

---

## Step 0 — Establish the facts

Before classifying, pin down four things. Guessing any of them produces a wrong call.

1. **Reachability**: is the vulnerable code on a path reachable from untrusted input in a default configuration, or does it need opt-in or insecure config?
2. **Actor and precondition**: what must the attacker already have? Unauthenticated and remote is the worst case; "already has the app secret" or "already controls the templates" usually means the precondition is itself game-over.
3. **Impact**: RCE, arbitrary file read/write, auth/authz bypass, XSS on a default-rendered surface, CSRF on a state-changing endpoint, signature bypass that accepts forged input, secret or data disclosure, or "only" DoS / info of low value.
4. **Contract**: is the package meant to defend this boundary, or is the unsafe behaviour documented as the app's responsibility? Boundaries ux defends: the LiveComponent props checksum and its request gate on live endpoints, TwigComponent attribute escaping, the Icons SVG sanitizer (for Iconify responses and local files alike), Autocomplete result escaping and search-query handling, the Toolkit installer's path confinement for third-party kits. Documented app responsibilities: a `writable: true` LiveProp accepts values from the client by design, `options_as_html: true` renders Autocomplete results as raw HTML on purpose, and an autocompleter left with the default `security: false` is public (see `src/Autocomplete/doc/index.rst`).

Reproduce if at all possible; an unreproducible report is not yet triable.

## Step 1 — Disposition decision tree

Apply in order. The first matching bucket wins.

### It is **not a security issue** if any of these hold
- **Requires misuse contrary to documentation**, with no default-config attack (one of the documented app responsibilities from Step 0.4).
- **Dev-only tooling** (debug commands, profiler data) manifesting only in a dev environment.
- **Precondition is already game-over** (attacker already holds the app secret, writes the templates, or has local access).
- **Not reproducible**, or rooted in a third-party dependency outside ux's control. Content ux fetches and renders is not in that category: ux sanitizes Iconify SVG responses because it outputs them (CVE-2026-55877).
- **Not a realistic bypass** (a comparison nuance with no working exploit, etc.).

Pure DoS / resource exhaustion is listed by the Symfony policy among the issues not considered security issues. ux has nevertheless published CVE-2026-49209 (low) for the unbounded `_batch` action fan-out in LiveComponent. For a DoS on a ux endpoint, recommend hardening, cite that precedent, and leave the call to the security team.

### It is a **CVE** only if **all** of these hold
1. **Default-reachable**: exploitable against a default or documented-safe config.
2. **Expected actor**: the attacker is at or below the trust level the boundary is meant to enforce (typically unauthenticated/remote, a lower-privileged user escalating, or the author of a third-party Toolkit kit), with no game-over precondition.
3. **Contracted boundary**: the package is meant to defend this (see Step 0.4).
4. **Real impact**: RCE, arbitrary file read/write, auth/authz bypass, stored/reflected XSS on a default surface, CSRF on live endpoints, signature bypass accepting forged input, or data disclosure.
5. **Maintained**: the vulnerable code ships on `2.x` or `3.x`.

### Otherwise it is **public hardening**
A genuine improvement where a CVE condition fails. Shapes from ux history:
- **Safer default where the unsafe one never reached production**: `LiveComponentSubscriber`'s test mode defaulted to `true` in the constructor, but the bundle's compiler pass already overrode it for non-test kernels; flipping the default was a `bug` PR (#3566).
- **Timing-safe comparison without a working exploit**: the Autocomplete `extra_options` checksum moved from `!==` to `hash_equals()` as a `bug` PR (#3565).
- **Robustness** improvements (input caps, broader sanitizer coverage).

ux has also published CVEs for fixes that close a boundary a default install already guards: CVE-2026-49210 (child component tag, rejected even though the request is gated by the live endpoint checks) and CVE-2026-49212 (checksum bound to component name and slot). When a finding has that defense-in-depth shape, present both kinds of precedent instead of deciding.

## Step 2 — Severity and affected branches

**Severity** (the GHSA severity; CVSS is a sanity check, not the goal). ux precedent:
- **high**: arbitrary file write/read (CVE-2026-55878, Toolkit path traversal through a crafted kit manifest). Also unauthenticated RCE or auth bypass.
- **medium**: XSS on a default-rendered surface (CVE-2025-47946 TwigComponent attributes, CVE-2026-49210 LiveComponent child tag, CVE-2026-49216 Autocomplete AJAX results, CVE-2026-55877 Icons SVG).
- **low**: bounded impact or defense-in-depth (CVE-2026-49208 lenient date LiveProp parsing, CVE-2026-49209 `_batch` DoS, CVE-2026-49211 LIKE wildcards in Autocomplete, CVE-2026-49212 checksum binding, CVE-2026-49215 CSRF through the CORS-safelisted `Accept` header).

**Affected branches**: find the oldest version where the vulnerable code exists and intersect with the maintained branches, `2.x` and `3.x` (ux publishes no `releases.json`). Fix on the lowest maintained affected branch, then merge up with the `merge-up` skill: every 2026 CVE fix landed on `2.x` and reached `3.x` that way. Code that exists only on `3.x` is fixed on `3.x`. Record the oldest exposure even if it predates maintained versions; it becomes the GHSA affected range.

## Step 3 — Route

State the recommendation as: **disposition + severity + affected maintained branches + the one-line rationale (which decision-tree conditions decided it)**, then route:

- **CVE**: name the branch `cve-<year>-<number>`. The fix is prepared privately and goes through the coordinated-disclosure process (GHSA + CVE, reporter credit, security release). Do not open a public PR or push to a public remote before the release; `origin` is a public fork, so it counts. The fix carries a `src/<Package>/CHANGELOG.md` entry ending with `(security fix)`, plus a BC note when the fix changes behaviour users can see (see the LiveComponent `2.36` entries). **Wait for confirmation** before any outward step, and print any `git push` command for the user instead of running it.
- **Public hardening**: open a normal PR against the lowest affected branch; use `symfony-security-review` to confirm the fix. Hardening PRs such as #3565 and #3566 carried no CHANGELOG entry; add one only when users see a behaviour change.
- **Not a security issue**: draft a short, factual reply to the reporter explaining why (cite the contract or threat-model reason), and optionally a doc clarification or low-priority robustness PR.

In every case, the fix follows TDD and runs only the affected package's tests: `cd src/<Package> && composer update && php vendor/bin/phpunit`, plus `pnpm run test:unit` and `pnpm run build` in `src/<Package>/assets` for a JS change, since `dist/` is committed. No Claude/Anthropic credit, comments sparingly, no issue references in code.

---

## Worked examples

From ux's published advisories and merged fixes:

| Finding shape | Disposition | Deciding factor |
|---|---|---|
| A third-party kit's `copy-files` entry with `..` makes the installer write outside the project (CVE-2026-55878) | **CVE, high** | the author of a remote kit is untrusted; arbitrary file write |
| `{{ attributes }}` renders attribute values unescaped (CVE-2025-47946) | **CVE, medium** | default rendering path, XSS |
| Iconify API or local SVG rendered without sanitization (CVE-2026-55877) | **CVE, medium** | ux outputs the markup, so it owns sanitizing it |
| Autocomplete Stimulus controller renders AJAX `text` unescaped (CVE-2026-49216) | **CVE, medium** | stored XSS through a default option |
| Client-sent child component tag interpolated into HTML (CVE-2026-49210) | **CVE, medium** | XSS, published even though the live endpoint checks gate it by default |
| `Accept` header used as the only CSRF gate on live endpoints (CVE-2026-49215) | **CVE, low** | `Accept` is CORS-safelisted, so cross-origin `fetch()` sets it without a preflight |
| Unbounded `_batch` actions, one sub-request each (CVE-2026-49209) | **CVE, low** | DoS; ux precedent differs from the Symfony policy default |
| `LiveComponentSubscriber` test mode defaulting to on outside the compiler pass (#3566) | **Hardening** | the unsafe default never reached production through the bundle's DI |
| `extra_options` checksum compared with `!==` (#3565) | **Hardening** | timing nuance, no working exploit |

Not-a-security-issue shapes follow from the documented contracts in Step 0.4 (a writable LiveProp set by the client, `options_as_html: true`, an autocompleter left public). They come from the documentation, not from a ruling on record, so say so in the reply.

## Gotchas

- **"Boundary crossed" does not imply CVE.** Impact and exposure decide it; present the ux precedents on both sides when the finding is defense-in-depth.
- **DoS is where ux practice and the written policy diverge.** Cite CVE-2026-49209 and let the security team decide.
- **Rendered third-party content is ux's boundary.** Whatever ux outputs (Iconify SVG, AJAX results rendered by a Stimulus controller) has to be made safe by ux, even though another system produced it.
- **Trust-model questions are for the maintainer.** Surface them (is a remote kit trusted? is a given prop meant to be client-writable?) instead of assuming the answer.
- **Embargo discipline.** Never name a CVE-bound finding, push a `cve-*` branch, or open a public issue or PR for a CVE-class finding before the coordinated release. **Wait for confirmation.**
- **Defer to authority.** https://symfony.com/security is the source of truth for what is not a vulnerability; this skill encodes observed practice, not policy.

## Error handling

- If reachability or the trust model is unknown, say so and triage as needs-human-judgement; do not force a disposition.
- Security reports are handled privately. Do not echo report contents into public artifacts, commit messages, or branch names that leak the vulnerability before release.
- Never push to a public remote during CVE triage. Stop and hand back to the user.
