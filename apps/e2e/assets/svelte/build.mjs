import * as fs from 'node:fs';
import * as path from 'node:path';
import {globSync} from 'tinyglobby'
import {compile} from 'svelte/compiler';

globSync('src/**/*.svelte')
    .forEach((file) => {
        const builtFile = file.replace('src/', 'build/').replace('.svelte', '.js')
        console.log(`Building ${file} to ${builtFile}...`)

        const result = compile(fs.readFileSync(file, 'utf8'), {
            format: 'esm',
        });

        fs.mkdirSync(path.dirname(builtFile), { recursive: true })
        fs.writeFileSync(builtFile, result.js.code);
    });
