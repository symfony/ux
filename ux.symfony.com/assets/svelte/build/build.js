/**
 * Svelte Components work best when used with a packaging system like WebpackEncore.
 *
 * However, you *can* compile the .svelte files to .js files and use them directly.
 */
const fs = require('fs');
const path = require('path');
const { compile } = require('svelte/compiler');

function compileDirectory(inputDir, outputDir) {
    const files = fs.readdirSync(inputDir);

    for (const file of files) {
        const inputFile = path.join(inputDir, file);
        
        // Exclude node_modules directory
        if (inputFile.includes('node_')) {
            continue;
        }
        
        const stats = fs.statSync(inputFile);
        if (stats.isDirectory()) {
            const newOutputDir = path.join(outputDir, file);
            if (!fs.existsSync(newOutputDir)) {
                fs.mkdirSync(newOutputDir);
            }
            console.log('Entering', inputDir);
            compileDirectory(inputFile, newOutputDir);
            continue;
        }
        
        if(path.extname(file) === '.svelte') {
            console.log('Compiling', inputFile);
            const input = fs.readFileSync(inputFile, 'utf-8');
            const output = compile(input, { format: 'esm' });
            const outputFile = path.join(outputDir, `${path.basename(file, '.svelte')}.js`);
            fs.writeFileSync(outputFile, output.js.code);
        }
    }
}

compileDirectory(
    path.join(__dirname, '..',  'src' ),
    path.join(__dirname, '..',  'dist' ),
);
