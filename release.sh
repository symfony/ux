#!/usr/bin/env bash

# Cut a release: bump every workspace package.json, commit, and tag it.
#
# Usage: ./release.sh <x.y.z>
#
# It only prepares the commit and tag locally. Pushing the tag is left to you,
# because that is what triggers the npm publish (see MAINTAINERS.md).

set -euo pipefail

version="${1:-}"
if [ -z "$version" ]; then
    echo "Usage: $0 <x.y.z>" >&2
    exit 1
fi

if ! [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
    echo "Error: invalid version '$version'. Expected semver without leading 'v' (e.g. 3.3.0 or 3.3.0-rc1)." >&2
    exit 1
fi

branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "$branch" != "2.x" ] && [ "$branch" != "3.x" ]; then
    echo "Error: releases are cut from '2.x' or '3.x', but you are on '$branch'." >&2
    exit 1
fi

if [ "${version%%.*}" != "${branch%%.*}" ]; then
    echo "Error: version '$version' does not match branch '$branch' (major version mismatch)." >&2
    exit 1
fi

tag="v${version}"
if git rev-parse -q --verify "refs/tags/$tag" >/dev/null; then
    echo "Error: tag '$tag' already exists." >&2
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "Error: the working tree is not clean. Commit or stash your changes first." >&2
    exit 1
fi

git fetch --quiet upstream "$branch"
if [ "$(git rev-parse HEAD)" != "$(git rev-parse FETCH_HEAD)" ]; then
    echo "Error: local '$branch' is not in sync with 'upstream/$branch'. Pull first." >&2
    exit 1
fi

# release-on-npm.yaml publishes the committed dist/ files as-is, so rebuild them
# and refuse to tag if they drift from the committed sources.
pnpm install --frozen-lockfile
pnpm build
if [ -n "$(git status --porcelain)" ]; then
    echo "Error: built dist files differ from the committed ones. Rebuild and commit them first." >&2
    git status --porcelain >&2
    exit 1
fi

# Recursive mode bumps every workspace package.json but always skips the commit
# and tag (and leaves the private root package untouched), so we do both by hand.
pnpm version "$version" --recursive --no-git-checks
if [ -z "$(git status --porcelain)" ]; then
    echo "Error: no package.json changes after 'pnpm version'. Version '$version' may already be set." >&2
    exit 1
fi

git commit -a -m "Bump npm packages to $tag"
git tag -s -m "Create tag $tag" "$tag"

echo
echo "Prepared $tag. Review the commit and tag, then publish with:"
echo
echo "    git push upstream $branch --follow-tags"
echo
echo "Pushing $tag triggers the Release on NPM workflow, which publishes to npm."
