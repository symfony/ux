import { babel } from '@rollup/plugin-babel';
import { nodeResolve } from '@rollup/plugin-node-resolve';

export default [
    {
        input: 'src/live_controller.js',
        output: {
            name: 'LiveController',
            file: 'dist/live-controller.esm.js',
            format: 'esm',
            sourcemap: false,
        },
        external: [
            /@babel\/runtime/,
            /core-js\//,
            'stimulus',
        ],
        plugins: [
            babel({
                babelHelpers: 'runtime',
            }),
            nodeResolve(),
        ],
    },
];
