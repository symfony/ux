import fs from 'fs';
import path from 'path';

export function createRequireContextPolyfill (rootDir: string) {
    return (base: string, deep: boolean, filter: RegExp): __WebpackModuleApi.RequireContext => {
        const basePrefix = path.resolve(rootDir, base);
        const files: { [key: string]: boolean } = {};

        function readDirectory(directory: string) {
            fs.readdirSync(directory).forEach((file: string) => {
                const fullPath = path.resolve(directory, file);

                if (fs.statSync(fullPath).isDirectory()) {
                    if (deep) {
                        readDirectory(fullPath);
                    }

                    return;
                }

                if (!filter.test(fullPath)) {
                    return;
                }

                files[fullPath.replace(basePrefix, '.')] = true;
            });
        }

        readDirectory(path.resolve(rootDir, base));

        function Module(file: string) {
            return require(basePrefix + '/' + file);
        }

        Module.keys = () => Object.keys(files);

        return (Module as __WebpackModuleApi.RequireContext);
    };
}
