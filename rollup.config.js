import resolve from '@rollup/plugin-node-resolve';
import typescript from '@rollup/plugin-typescript';
import glob from 'glob';
import path from 'path';
import pkgUp from 'pkg-up';

const files = glob.sync("src/**/assets/src/*controller.ts");
const packages = files.map((file) => {
    const absolutePath = path.join(__dirname, file);
    const packageData = require(pkgUp.sync({ cwd: absolutePath }));
    const peerDependencies = [
        '@hotwired/stimulus',
        ...(packageData.peerDependencies ? Object.keys(packageData.peerDependencies) : [])
    ];

    return {
        input: file,
        output: {
            file: path.join(absolutePath, '..', '..', 'dist', path.basename(file, '.ts') + '.js'),
            format: 'esm',
        },
        external: peerDependencies,
        plugins: [
            resolve(),
            typescript(),
        ],
    };
});

export default packages;
