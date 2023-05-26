/**
 * This file is used to compile the TypeScript files in the assets/src directory
 * of each package.
 *
 * It allows each package to spawn its own rollup process, which is necessary
 * to keep memory usage down.
 */
const { spawnSync } = require('child_process');
const glob = require('glob');

const files = [
    // custom handling for React
    'src/React/assets/src/loader.ts',
    'src/React/assets/src/components.ts',
    // custom handling for Svelte
    'src/Svelte/assets/src/loader.ts',
    'src/Svelte/assets/src/components.ts',
    // custom handling for Vue
    'src/Vue/assets/src/loader.ts',
    'src/Vue/assets/src/components.ts',
    // custom handling for StimulusBundle
    'src/StimulusBundle/assets/src/loader.ts',
    'src/StimulusBundle/assets/src/controllers.ts',
    ...glob.sync('src/*/assets/src/*controller.ts'),
];

files.forEach((file) => {
    const result = spawnSync('node', [
        'node_modules/.bin/rollup',
        '-c',
        '--environment',
        `INPUT_FILE:${file}`,
    ], {
        stdio: 'inherit',
        shell: true
    });

    if (result.error) {
        console.error(`Error compiling ${file}:`, result.error);
    }
});
