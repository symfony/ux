/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// This file is dynamically rewritten by ux-vue + AssetMapper.
import type { Component } from 'vue';

export interface ComponentCollection {
    [key: string]: Component;
}

export const components: ComponentCollection = {};
