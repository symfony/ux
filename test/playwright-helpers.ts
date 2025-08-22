import * as path from 'node:path';
import * as fs from 'fs';

export function getSymfonyKernelVersionId(): number {
    const kernelPath = path.join(import.meta.dirname, '../test_apps/e2e-app/vendor/symfony/http-kernel/Kernel.php');
    if (!fs.existsSync(kernelPath)) {
        throw new Error(`Unable to read Symfony Kernel version ID, the file "${kernelPath}" does not exist.`)
    }

    const match = fs.readFileSync(kernelPath, 'utf8').match((/VERSION_ID = (\d+)/));
    if (match === null) {
        throw new Error(`Unable to extract Symfony Kernel version ID.`)
    }

    return Number(match[1]);
}
