/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// This file is dynamically rewritten by ux-svelte + AssetMapper.
import type { SvelteComponent } from 'svelte';

export interface ComponentCollection {
    [key: string]: SvelteComponent;
}

export const components: ComponentCollection = {};
