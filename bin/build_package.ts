/**
 * This file is used to compile the assets from an UX package.
 */

import * as fs from 'node:fs';
import * as path from 'node:path';
import { parseArgs } from 'node:util';
import * as LightningCSS from 'lightningcss';
import * as rollup from 'rollup';
import { globSync } from 'tinyglobby';
import { getRollupConfiguration } from './rollup.ts';

const args = parseArgs({
    allowPositionals: true,
    options: {
        watch: {
            type: 'boolean',
            description: 'Watch the source files for changes and rebuild when necessary.',
        },
    },
});

async function main() {
    const packageRoot = path.resolve(process.cwd(), args.positionals[0]);
    const isWatch = args.values.watch || false;

    if (!fs.existsSync(packageRoot)) {
        console.error(`The package directory "${packageRoot}" does not exist.`);
        process.exit(1);
    }

    if (!fs.existsSync(path.join(packageRoot, 'package.json'))) {
        console.error(`The package directory "${packageRoot}" does not contain a package.json file.`);
        process.exit(1);
    }

    const packageData = await import(path.join(packageRoot, 'package.json'), {with: { type: 'json'}});
    const packageName = packageData.name;
    const srcDir = path.join(packageRoot, 'src');
    const distDir = path.join(packageRoot, 'dist');

    if (!fs.existsSync(srcDir)) {
        console.error(`The package directory "${packageRoot}" does not contain a "src" directory.`);
        process.exit(1);
    }

    if (fs.existsSync(distDir)) {
        console.log(`Cleaning up the "${distDir}" directory...`);
        await fs.promises.rm(distDir, { recursive: true });
        await fs.promises.mkdir(distDir);
    }

    const inputScriptFiles = [
        ...globSync(path.join(srcDir, '*controller.ts')),
        ...(['@symfony/ux-react', '@symfony/ux-vue', '@symfony/ux-svelte'].includes(packageName)
            ? [path.join(srcDir, 'loader.ts'), path.join(srcDir, 'components.ts')]
            : []),
        ...(packageName === '@symfony/stimulus-bundle'
            ? [path.join(srcDir, 'loader.ts'), path.join(srcDir, 'controllers.ts')]
            : []),
    ];

    const inputStyleFile = packageData.config?.css_source;
    const buildCss = async () => {
        if (!inputStyleFile) {
            return;
        }
        const inputStyleFileDist = path.resolve(distDir, `${path.basename(inputStyleFile, '.css')}.min.css`);

        console.log('Minifying CSS...');
        const css = await fs.promises.readFile(inputStyleFile, 'utf-8');
        const { code: minified } = LightningCSS.transform({
            filename: path.basename(inputStyleFile, '.css'),
            code: Buffer.from(css),
            minify: true,
            sourceMap: false, // TODO: Maybe we can add source maps later? :)
        });
        await fs.promises.writeFile(inputStyleFileDist, minified);
    };

    if (inputScriptFiles.length === 0) {
        console.error(
            `No input files found for package "${packageName}" (directory "${packageRoot}").\nEnsure you have at least a file matching the pattern "src/*_controller.ts", or manually specify input files in "${import.meta.filename}" file.`
        );
        process.exit(1);
    }

    const rollupConfig = getRollupConfiguration({
        packageRoot,
        inputFiles: inputScriptFiles,
        isWatch,
        additionalPlugins: [
            ...(isWatch && inputStyleFile
                ? [
                      {
                          name: 'watcher',
                          buildStart(this: rollup.PluginContext) {
                              this.addWatchFile(inputStyleFile);
                          },
                      },
                  ]
                : []),
        ],
    });

    if (isWatch) {
        console.log(
            `Watching for JavaScript${inputStyleFile ? ' and CSS' : ''} files modifications in "${srcDir}" directory...`
        );

        const watcher = rollup.watch(rollupConfig);
        watcher.on('event', (event) => {
            if (event.code === 'ERROR') {
                console.error('Error during build:', event.error);
            }

            if ((event.code === 'BUNDLE_END' || event.code === 'ERROR') && event.result) {
                event.result.close();
            }
        });
        watcher.on('change', async (id, { event }) => {
            if (event === 'update') {
                console.log('Files were modified, rebuilding...');
            }

            if (inputStyleFile && id === inputStyleFile) {
                await buildCss();
            }
        });
    } else {
        console.log(`Building JavaScript files from ${packageName} package...`);
        const start = Date.now();

        if (typeof rollupConfig.output === 'undefined' || Array.isArray(rollupConfig.output)) {
            console.error(
                `The rollup configuration for package "${packageName}" does not contain a valid output configuration.`
            );
            process.exit(1);
        }

        const bundle = await rollup.rollup(rollupConfig);
        await bundle.write(rollupConfig.output);

        await buildCss();

        console.log(`Done in ${((Date.now() - start) / 1000).toFixed(3)} seconds.`);
    }
}

main();
