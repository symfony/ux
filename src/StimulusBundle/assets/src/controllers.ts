/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// This file is dynamically rewritten by StimulusBundle + AssetMapper.
import { ControllerConstructor } from '@hotwired/stimulus';

export interface EagerControllersCollection {
    [key: string]: ControllerConstructor;
}
export interface LazyControllersCollection {
    [key: string]: () => Promise<{ default: ControllerConstructor }>;
}

export const eagerControllers: EagerControllersCollection = {};
export const lazyControllers: LazyControllersCollection = {};
export const isApplicationDebug = false;
