# Maintainers' guide

This document is for Symfony UX maintainers. It collects procedures that are not
needed by regular contributors.

## Releasing UX packages on NPM

The Git tag is the source of truth for the released version. Workspace
`package.json` files must already match the tag at publish time —
`release-on-npm.yaml` does not enforce this and would otherwise publish
whatever versions are committed. Always bump and merge **before** tagging.

### 1. Bump workspace versions

Trigger the **Prepare NPM release** workflow from the Actions tab
(`.github/workflows/prepare-npm-release.yaml`) with:

- `branch`: `2.x` or `3.x`
- `version`: e.g. `2.36.0` (no leading `v`)

It runs `pnpm install --frozen-lockfile`, `pnpm build`,
`pnpm version $VERSION --no-git-tag-version --workspaces --no-workspaces-update`,
commits on `bump/v$VERSION`, and opens a PR against the chosen branch.

Review and merge the PR.

### 2. Tag and publish

Create and push the `v$VERSION` tag via the standard Symfony release process.
The `release-on-npm.yaml` workflow then publishes via the OIDC trusted publisher.

### Manual fallback

If the workflow cannot be used, reproduce its steps locally from the release
branch with `upstream` pointing to `symfony/ux`:

```shell
$ git checkout 2.x # or 3.x
$ VERSION=2.36.0 && \
  pnpm install --frozen-lockfile && \
  pnpm build && \
  pnpm version $VERSION --no-git-tag-version --workspaces --no-workspaces-update && \
  git add . && \
  git commit -m "Bump npm packages to v$VERSION"
$ git push upstream HEAD
```
