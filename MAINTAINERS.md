# Maintainers' guide

This document is for Symfony UX maintainers. It covers procedures that regular contributors don't need.

## Releasing UX packages on npm

A release does not move every package: the splitter only tags the read-only repositories whose subtree changed. `release.sh` asks split.sh which ones those will be and bumps only their `package.json`, so npm never gets a version no split repository carries.

It then rebuilds assets to confirm the committed `dist/` files are up to date, commits `Bump npm packages to v3.3.0`, and creates the signed `v3.3.0` tag. It prints the packages it bumped, which is worth a look. Nothing is pushed.

From the release branch, with `upstream` pointing to `symfony/ux`:

```shell
$ git checkout 3.x # or 2.x
$ git pull upstream 3.x
$ ./release.sh 3.3.0
```

Review the commit and tag, then push:

```shell
$ git push upstream 3.x --follow-tags
```

Pushing the tag triggers `release-on-npm.yaml`, which runs `pnpm publish --recursive` against the OIDC trusted publisher. It skips private packages and any version npm already serves, so it publishes exactly what `release.sh` bumped. Re-running the job is harmless.

`release.sh` stops before touching anything if split.sh is unreachable, would tag nothing, or names a repository `splitsh.json` does not declare. To see its answer for yourself:

```shell
$ curl 'https://go.split.sh/api/projects/symfonyux/branches/3.x/tag-prediction?version=3.5.1'
{"branch":"3.x","tagged":["ux-map","ux-pagination"],"skipped":[...],"version":"3.5.1"}
```

### The first release of a new package

The Release on NPM workflow publishes through npm trusted publishing. This only works for a package that npm already has, so you must publish the first version of a new package by hand.

When a release bumps a package that npm does not have yet, `release.sh` ends with the steps to follow before you push: one `pnpm publish` command per package, then the npm page where you add the trusted publisher. After you push, the workflow skips these packages, because npm already has their version, and publishes the others.

`release.sh` stops before it changes anything if a package that npm does not have yet is not part of the release. This happens when split.sh does not split the package yet. The workflow would fail to publish it.

## Splitting packages into read-only repositories

Each `symfony/ux-*` package lives in its own read-only repository split from this monorepo, managed on the [split.sh dashboard](https://go.split.sh/dashboard#project-symfonyux).
