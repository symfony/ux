/**
 * This file is used to compile the assets from an UX package.
 */

import * as fs from 'node:fs';
import * as path from 'node:path';
import { parseArgs } from 'node:util';
import { globSync } from 'tinyglobby';
import { build } from 'tsdown';
import { readPackageJSON } from 'pkg-types';
import tsConfigPackage from '../tsconfig.package.json' with { type: 'json' };

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

    const packageData = await readPackageJSON(path.join(packageRoot, 'package.json'));
    const isStimulusBundle = '@symfony/stimulus-bundle' === packageData.name;
    const isReactOrVue = ['@symfony/ux-react', '@symfony/ux-vue'].some((name) => packageData.name.startsWith(name));
    const isTurbo = '@symfony/ux-turbo' === packageData.name;

    // Collect non-controller entry points declared in package.json "exports"
    // (e.g. "./upload" -> "./dist/upload/SignedUploadClient.js"), mapping each
    // "dist/*.js" target back to its "src/*.ts" source when it exists on disk.
    const collectExportTargets = (node) => {
        if (typeof node === 'string') return [node];
        if (node && typeof node === 'object') return Object.values(node).flatMap(collectExportTargets);
        return [];
    };
    const exportEntryFiles = [
        ...new Set(
            collectExportTargets(packageData.exports || {})
                .filter((target) => target.endsWith('.js'))
                .map((target) =>
                    target
                        .replace(/^\.\//, '')
                        .replace(/^dist\//, 'src/')
                        .replace(/\.js$/, '.ts')
                )
                .filter((srcFile) => fs.existsSync(path.join(packageRoot, srcFile)))
        ),
    ];

    const inputCssFile = packageData?.config?.css_source;
    const inputFiles = [
        ...new Set([
            ...globSync('src/**/*controller.ts'),
            ...exportEntryFiles,
            ...(isTurbo ? ['src/mercure_stream_source_element.ts'] : []),
            ...(isStimulusBundle ? ['src/loader.ts', 'src/controllers.ts'] : []),
            ...(isReactOrVue ? ['src/loader.ts', 'src/components.ts'] : []),
            ...(inputCssFile ? [inputCssFile] : []),
        ]),
    ];

    const external = new Set([
        // We force "dependencies" and "peerDependencies" to be external to avoid bundling them.
        ...Object.keys(packageData.dependencies || {}),
        ...Object.keys(packageData.peerDependencies || {}),
        // The "controllers.js" is generated on-the-fly by StimulusLoaderJavaScriptCompiler
        ...(isStimulusBundle ? ['./controllers.js'] : []),
        // The "components.js" files are generated on-the-fly by *ControllerLoaderAssetCompiler
        ...(isReactOrVue ? ['./components.js'] : []),
    ]);

    const outDir = path.join(packageRoot, 'dist');

    await build({
        entry: inputFiles,
        outDir,
        clean: true,
        watch: isWatch,
        format: 'esm',
        platform: 'browser',
        target: tsConfigPackage.compilerOptions.target,
        tsconfig: path.join(packageRoot, 'tsconfig.json'),
        dts: {
            entry: inputFiles.filter((inputFile) => !inputFile.endsWith('.css')),
        },
        ...(inputCssFile
            ? {
                  css: {
                      minify: true,
                      fileName: inputCssFile
                          .split('/')
                          .pop()
                          .replace(/\.css$/, '.min.css'),
                  },
              }
            : {}),
        // Prevent esbuild to inline relative and "external" imports (like "./components.js" for React, Vue).
        unbundle: isStimulusBundle || isReactOrVue,
        deps: {
            neverBundle: Array.from(external),
            onlyBundle: ['idiomorph'],
        },
        plugins: [
            {
                name: 'symfony-ux:clean-output',
                renderChunk(code, chunk) {
                    // Remove #region/#endregion comments from all files
                    let result = code.replace(/^\s*\/\/#(?:end)?region[^\n]*\n?/gm, '');

                    // Remove JSDoc comments only from .js files (preserve them in .d.ts)
                    if (chunk.fileName.endsWith('.js')) {
                        result = result.replace(/^\s*\/\*\*[\s\S]*?\*\/\s*\n/gm, (match) =>
                            match.includes('@deprecated') ? match : ''
                        );
                    }

                    return result;
                },
            },
        ],
    });
}

main();
