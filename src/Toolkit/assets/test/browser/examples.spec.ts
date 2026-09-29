import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { expect, screenshotAnnotation, test, themes } from './fixtures';

type Example = { kit: string; recipe: string; id: string };

const appDir = fileURLToPath(new URL('../../../../../apps/toolkit', import.meta.url));
const output = execFileSync('php', ['bin/console', 'app:examples'], { cwd: appDir, encoding: 'utf8' });
const examples: Example[] = JSON.parse(output);

for (const { kit, recipe, id } of examples) {
    for (const theme of themes) {
        const name = [kit, recipe, 'tests', 'screenshots', `${id}-${theme}.png`];

        test(`${kit}/${recipe}/${id} ${theme}`, screenshotAnnotation(name), async ({ page, gotoExample }) => {
            await gotoExample(`${kit}/${recipe}/${id}`, { theme, timers: 'fake' });

            await expect(page).toHaveScreenshot(name, { fullPage: true });
        });
    }
}
