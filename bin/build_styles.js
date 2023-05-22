/**
 * Script to "build" the source CSS files to their final destination.
 */

const glob = require('glob');
const { exec } = require('child_process');
const path = require('path');
const fs = require('fs');

let pattern = 'src/*/assets/package.json';

// Use glob to find all matching package.json files
glob(pattern, function (err, files) {
    if (err) {
        console.error('Error while finding package.json files:', err);
        process.exit(1);
    }

    // Loop over all files
    files.forEach(file => {
        // Read the package.json file
        const pkg = JSON.parse(fs.readFileSync(file, 'utf-8'));

        // Get the css source
        const cssSourceRelative = pkg.config && pkg.config.css_source;

        if (!cssSourceRelative) {
            return;
        }
        // Construct the output path
        const cssSource = path.join(path.dirname(file), cssSourceRelative);
        const outputDir = path.join(path.dirname(file), 'dist');
        const outputFilename = path.basename(cssSource, '.css') + '.min.css';
        const output = path.join(outputDir, outputFilename);

        // Run the clean-css-cli command
        exec(`yarn run cleancss -o ${output} ${cssSource}`, function (err) {
            if (err) {
                console.error(`Error while minifying ${cssSource}:`, err);
                return;
            }

            console.log(`Minified ${cssSource} to ${output}`);
        });
    });
});
