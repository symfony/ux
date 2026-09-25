# Maintainers' guide

This document is for Symfony UX maintainers. It covers procedures that regular contributors don't need.

## Releasing UX packages on npm

A release does not move every package: the splitter only tags the read-only repositories whose subtree changed. `release.sh` asks split.sh which ones those will be and bumps only their `package.json`, so npm never gets a version no split repository carries.

It then rebuilds assets to confirm the committed `dist/` files are up to date, commits `Bump npm packages to v2.37.0`, and creates the signed `v2.37.0` tag. It prints the packages it bumped, which is worth a look. Nothing is pushed.

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

Pushing the tag triggers `release-on-npm.yaml`, which runs `pnpm publish --recursive` against the OIDC trusted publisher. It skips private packages and any version npm already serves, so it publishes exactly what `release.sh` bumped. Re-running the job is harmless.

`release.sh` stops before touching anything if split.sh is unreachable, would tag nothing, or names a repository `splitsh.json` does not declare. To see its answer for yourself:

```shell
$ curl 'https://go.split.sh/api/projects/symfonyux/branches/2.x/tag-prediction?version=2.36.2'
{"branch":"2.x","tagged":["ux-autocomplete"],"skipped":[...],"version":"2.36.2"}
```

## Splitting packages into read-only repositories

Each `symfony/ux-*` package lives in its own read-only repository split from this monorepo, managed on the [split.sh dashboard](https://go.split.sh/dashboard#project-symfonyux).
