# Hardening families

Reference catalogue for the `symfony-security-review` skill. Each family comes from a real ux fix and lists:

- **Anchor**: how to find the sinks in scope (grep, run against the scope, not the whole tree).
- **Invariant / check**: the question to answer at every sink.
- **Source**: the fix the family comes from (advisory, CVE, merge). Entries marked *extrapolation* extend a fixed class to code that has not had a report; say so when you raise them.
- **Decision boundary**: shapes that look unsafe but are accepted. Do not flag these.

No family has an automated gate except W1 (zizmor). Families are grouped by package.

---

## LiveComponent

### L1. Props checksum (HMAC) binding and verification
- **Anchor**: `git grep -n "hash_hmac\|calculateChecksum\|verifyChecksum\|CHECKSUM_" -- src/LiveComponent/src`
- **Invariant**: the checksum over client-held props binds every context the blob is valid for, with unambiguous separators: `LiveComponentHydrator::calculateChecksum()` hashes `<component name>\0<slot>\0<json>`, where the slot is `props` or `propsFromParent`, so a blob minted for one component or slot cannot be replayed as another. Verification runs before the props are used, and compares with `hash_equals()` (see X1). A new signed blob (a new slot, a new component-scoped payload) gets its own slot value.
- **Source**: CVE-2026-49212 (GHSA-34w5-c283-j9fg, low), "Bind HMAC checksum to component name and slot".
- **Decision boundary**: `writable: true` LiveProps are outside the checksum by design; the client sets them. Treat their values as untrusted input (L2), not as a checksum bypass.

### L2. Strict hydration of client-writable values
- **Anchor**: `git grep -nE "new \\\$[a-zA-Z]+\(|createFromFormat|function hydrate" -- src/LiveComponent/src`, plus every `HydrationExtensionInterface` implementation.
- **Invariant**: a value the client can write is parsed strictly for its declared type and rejected (`BadRequestHttpException`) when it does not parse. Lenient constructors that accept relative or magic input are not a parser: a format-less `DateTimeInterface` prop is parsed with `createFromFormat(\DateTimeInterface::RFC3339, ...)`, matching what dehydration emits, instead of `new $className($value)`, which accepts `"now"`, `"+10 years"`.
- **Source**: CVE-2026-49208 (GHSA-89g7-22c8-3j23, low), "Parse format-less date LiveProps strictly with RFC 3339".
- **Decision boundary**: which props are writable is the app's choice; a writable prop receiving any well-typed value the app did not expect is app logic, not a ux finding.

### L3. Request gate on live endpoints (CSRF)
- **Anchor**: `git grep -n "isLiveComponentRequest\|testMode\|X-Requested-With\|HTML_CONTENT_TYPE" -- src/LiveComponent/src`
- **Invariant**: a live request is accepted only with a non-CORS-safelisted header (`X-Requested-With: XMLHttpRequest`) in addition to `Accept: application/vnd.live-component+html`, so a cross-origin page cannot send it without a preflight that Symfony does not answer. `Accept` alone is CORS-safelisted and is no gate. Any switch that skips the gate defaults to off: `LiveComponentSubscriber::$testMode` defaults to `false`, and `OptionalDependencyPass` turns it on only when `test.client` is defined.
- **Source**: CVE-2026-49215 (GHSA-4m4j-hmqq-3gxm, low), "Require X-Requested-With header to prevent CSRF" (merge `security #557`); hardening #3566, "Make `LiveComponentSubscriber` safe-by-default".
- **Decision boundary**: test mode on in the test kernel is intended. The bundled Stimulus controller sends the header, and clients calling live endpoints cross-origin have to allow it in CORS (documented BC note in the LiveComponent `2.36` CHANGELOG).

### L4. Fan-out caps on client-supplied lists
- **Anchor**: `git grep -n "MAX_ACTIONS_PER_BATCH\|foreach (\$actions" -- src/LiveComponent/src src/LiveComponent/assets/src`, then any loop over a request array that issues a sub-request, a render, or a query per entry.
- **Invariant**: a client-supplied list that fans out into server work has a server-side cap checked before the loop, rejected with `BadRequestHttpException`. When the client batches the same list, the client caps at the same value and queues the overflow: `BatchActionController::MAX_ACTIONS_PER_BATCH` and `MAX_ACTIONS_PER_BATCH` in `assets/src/Component/index.ts` are both `50`.
- **Source**: CVE-2026-49209 (GHSA-mm82-c99c-h2cf, low), "Cap the number of actions per `_batch` request".
- **Decision boundary**: none recorded. A cap far above real usage is fine; a cap on one side only is the finding.

### L5. Client values interpolated into generated HTML
- **Anchor**: `git grep -nE "sprintf\(.*<|'<'\s*\.|VALID_TAG|createHtml" -- src/LiveComponent/src src/TwigComponent/src`
- **Invariant**: a client-supplied value that ends up inside HTML the server builds by hand (a tag name, an attribute name) is validated against a strict grammar before use: the child placeholder tag must match `VALID_TAG` (`/\A[a-zA-Z][a-zA-Z0-9-]*+\z/`) in `ChildComponentPartialRenderer`, else `BadRequestHttpException`. Values in text or attribute-value positions are escaped instead (T1).
- **Source**: CVE-2026-49210 (GHSA-38x5-rcv4-xf7x, medium), "Reject malicious child component tags" (the `children[id].tag` payload).
- **Decision boundary**: the fix is still required even though the L3 gate stands in front of it in a default install; ux published a CVE for it.

## TwigComponent

### T1. Attribute and `is_safe` output escaping
- **Anchor**: `git grep -n "function __toString\|function render\|->escape(" -- src/TwigComponent/src/ComponentAttributes.php`, and every Twig function or filter declared `'is_safe' => ['html']` or `['html_attr']`: `git grep -n "is_safe" -- 'src/*/src/*.php'`
- **Invariant**: every path that returns or renders `ComponentAttributes` (`{{ attributes }}`, `render()`, `defaults()`, `only()`, `without()`) escapes keys and values for their context through Twig's `EscaperRuntime` (keys with `html_attr_relaxed`, values with `html`). A function declared `is_safe` escapes every value it did not produce itself, because Twig will not.
- **Source**: CVE-2025-47946 (GHSA-5j3w-5pcr-f8hg, medium), "Unsanitized HTML attribute injection via ComponentAttributes", fixed in 2.25.1 by escaping through `EscaperRuntime`. Applying the check to the other `is_safe` functions (such as `render_chart`, `ux_map`, `react_component`, `stimulus_*`, `turbo_stream_from`, `stream_notifications`, `live_action`) is an *extrapolation*: none has had a report.
- **Decision boundary**: attributes an app marks as already safe through Twig's own mechanisms are the app's call.

## Icons

### I1. SVG sanitization for every icon source
- **Anchor**: `git grep -n "IconFactory\|fromBody\|fromFile\|FORBIDDEN_ELEMENTS" -- src/Icons/src`
- **Invariant**: every SVG that reaches output, whether from the Iconify API or from a local file, goes through `IconFactory`, which removes `script`, `foreignObject`, `iframe`, `object`, `embed` and `handler` elements, SMIL animations targeting `on*`/`href`/`xlink:href`, CDATA sections and processing instructions (the tree is serialized as HTML, which would re-emit their payload), and every `on*` attribute, and checks `href`/`xlink:href` against a scheme allowlist (http(s), mailto, tel, raster `data:image/*`, fragments, relative URLs). A new icon source or a new way to build an `Icon` goes through the same factory.
- **Source**: CVE-2026-55877 (GHSA-6v8j-33hc-mv84, medium), "Sanitize Iconify SVG output and unify icon creation".
- **Decision boundary**: `<style>` is kept on purpose (themes use `prefers-color-scheme`); it is dropped only when its content contains a `</style>` breakout. Safe bodies are returned untouched so cached icons stay byte-identical. The Iconify base URI is app configuration (`ux_icons.iconify.endpoint`), not request input. The element and scheme lists are an answer-key: flag gaps for a data-provider test.

## Autocomplete

### A1. Rendering remote results in the Stimulus controller
- **Anchor**: `git grep -nE "option:|item:|render:|optionsAsHtml|innerHTML" -- src/Autocomplete/assets/src`
- **Invariant**: values from the AJAX response are escaped by default (TomSelect's `escape`) in every render callback; raw HTML is rendered only when `options_as_html` / `optionsAsHtmlValue` is explicitly true. A form-layer normalizer must not force the option one way and make the escape hatch unreachable.
- **Source**: CVE-2026-49216 (GHSA-mwqm-4fw3-cjvr, medium), "Fix XSS via unescaped AJAX response data". Checking other controllers that build HTML from fetched data with template literals or `innerHTML` is an *extrapolation*.
- **Decision boundary**: `options_as_html: true` is a documented opt-in; escaping is then the app's job. LiveComponent's `htmlToElement()` parses HTML the server rendered for the component; the server-side escaping (T1) is the control there.

### A2. Search query handling and endpoint exposure
- **Anchor**: `git grep -n "LIKE\|LIKE_ESCAPE_CHARACTER\|addcslashes\|searchable_fields\|filter_query" -- src/Autocomplete/src`
- **Invariant**: the user query is matched literally: `%`, `_` and the escape character itself are escaped, with an `ESCAPE` clause that is valid on every supported database. The escape character is `!` because `\` produced invalid SQL on PostgreSQL.
- **Source**: CVE-2026-49211 (GHSA-946h-jp5c-8fvh, low), "Escape LIKE wildcards in the search query"; portability fix #3685, "Fix LIKE ESCAPE clause breaking search on PostgreSQL".
- **Decision boundary**: `security` defaults to `false` (the endpoint is public) and `searchable_fields` defaults to every field; both are documented in `src/Autocomplete/doc/index.rst`. An endpoint an app leaves public is app configuration. Authorization, when configured and SecurityBundle is installed, runs in `AutocompleteResultsExecutor::fetchResults()` through `isGranted()` before results are fetched; a new results path that skips it is a finding.

## Cross-package

### X1. Constant-time comparison of secret-derived values
- **Anchor**: `git grep -nE "hash_hmac|hash_equals|checksum.*(!==|===)|(!==|===).*checksum" -- 'src/*/src/*.php'`
- **Invariant**: every comparison of a secret-derived value (LiveComponent checksum, Autocomplete `extra_options` checksum, Pagination cursor signature) uses `hash_equals(<known>, <user>)`. HMAC inputs have unambiguous field boundaries (see L1).
- **Source**: hardening #3565, "Use `hash_equals()` to compare the `extra_options` checksum" (Autocomplete). Current sites: `LiveComponentHydrator`, `AutocompleteController`, `Pagination\Cursor\CursorCodec`.
- **Decision boundary**: a non-constant-time compare without a working exploit was handled as public hardening, not a CVE.

## Toolkit

### K1. Path confinement when installing a kit
- **Anchor**: `git grep -n "copy-files\|pathDoesNotEscapeDirectory\|isBasePath\|Path::join" -- src/Toolkit/src`
- **Invariant**: every path taken from a kit manifest is rejected if any segment is `..` (either `/` or `\` separator), through `Assert::pathDoesNotEscapeDirectory()` in both `RecipeManifest` and `File`, and the installer re-checks the fully resolved source and destination with `Path::isBasePath()` right before each read and write. `Path::isRelative()` is not containment: it returns true for `../../../etc`.
- **Source**: CVE-2026-55878 (GHSA-p9xj-fpr2-jf2q, high), "Harden recipe installer against path traversal".
- **Decision boundary**: first-party kits under `src/Toolkit/kits/` are trusted; the risk is a remote kit (`ux:install --kit=https://github.com/...`, fetched by `GitHubRegistry`), whose author is the attacker.

## Repository

### W1. GitHub Actions workflows
- **Anchor**: any change under `.github/workflows/`.
- **Invariant**: workflows pass zizmor, and keep the shape the existing ones have: actions pinned to a commit SHA with the version in a comment, `permissions: {}` at the top and per-job grants, `persist-credentials: false` on checkout.
- **Source**: "[CI] Workflows hardenings, thanks to Zizmor" (24dc0e4) and "[CI] Pin explicit versions of external actions" (a57da9a); `.github/workflows/zizmor.yaml` runs on every PR.
- **Decision boundary**: none recorded; zizmor's CI result is the gate.

---

## Reviewer checklist (not tied to a single sink)

Confirm by reading. Raise as needs-human-judgement.

### C1. Secure-default regressions
- A constructor default, config default, or compiler pass that turns a check off outside tests (source: #3566). A default that turns a documented protection off without a CHANGELOG BC note.

### C2. Authorization on new endpoints (*extrapolation*)
- A new controller or route that returns data or runs actions has the same authorization hook its siblings have (Autocomplete: `isGranted()` through `AutocompleteResultsExecutor`). No ux advisory covers a missing check yet; raise it with the boundary named.
