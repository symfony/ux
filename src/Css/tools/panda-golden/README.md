# Panda golden cases

Records every `css()` call made by Panda CSS's own test suite (input, config, output) into `tests/Fixtures/Panda/`, so the PHP engine can be compared to Panda byte for byte. It also records what `utility.transform()` returns for every utility of the test fixture config, with a few values each, into `tests/Fixtures/Panda/utilities.json`.

    node src/Css/tools/panda-golden/record.mjs

Needs Node 22, pnpm and git. Panda is cloned at the commit pinned in `record.mjs` into `~/.cache/symfony-ux-css/panda` (override with `PANDA_DIR`). A run takes about a minute once Panda's dependencies are installed.

To move to a newer Panda: change `PANDA_COMMIT`, run the script, fix any patch that no longer applies (the script stops with the snippet it could not find), then run `php tests/Golden/update-expectations.php` from `src/Css`.
