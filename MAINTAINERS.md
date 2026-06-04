# Maintainers' guide

This document is for Symfony UX maintainers. It collects procedures that are not
needed by regular contributors.

## Releasing UX packages on NPM

The Git tag is the source of truth for the released version. The
`release-on-npm.yaml` workflow refuses to publish if any workspace
`package.json` does not match the tag — bump and commit **before** tagging.

From the release branch (`2.x` or `3.x`), with `upstream` pointing to `symfony/ux`:

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

The tag is created and pushed afterwards by following the Symfony's release process.
The `release-on-npm.yaml` workflow then publishes via OIDC trusted publisher.
