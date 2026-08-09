# Maintainers' guide

This document is for Symfony UX maintainers. It covers procedures that regular contributors don't need.

## Releasing UX packages on npm

The Git tag is the source of truth for the released version. Workspace `package.json` files must already match the tag at publish time — `release-on-npm.yaml` publishes whatever versions are committed and does not verify that they match the tag.

`release.sh` keeps everything in sync. It rebuilds assets to confirm the committed `dist/` files are up to date, bumps every workspace `package.json`, commits `Bump npm packages to v2.37.0`, and creates the signed `v2.37.0` tag — all in one step. Nothing is pushed.

From the release branch, with `upstream` pointing to `symfony/ux`:

```shell
$ git checkout 2.x # or 3.x
$ git pull upstream 2.x
$ ./release.sh 2.37.0
```

Review the commit and tag, then push:

```shell
$ git push upstream 2.x --follow-tags
```

Pushing the tag triggers `release-on-npm.yaml`, which publishes each package to npm via the OIDC trusted publisher.

## Splitting packages into read-only repositories

Each `symfony/ux-*` package lives in its own read-only repository split from this monorepo, managed on the [split.sh dashboard](https://go.split.sh/dashboard#project-symfonyux).
