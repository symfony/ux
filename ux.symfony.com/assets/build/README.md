# assets/build Directory

This project uses some items - like React and Svelte - that have their own custom,
non-Javascript formats (e.g. JSX). These require a build step to conver the custom
format to pure JavaScript. See the "scripts" section of package.json for more details.

This directory is committed to avoid needing to run the build step during deploy,
but it could be ignored.
