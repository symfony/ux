/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import type { Application } from '@hotwired/stimulus';
import { startApplication } from './core.js';
import { eagerControllers, isApplicationDebug, lazyControllers } from './controllers.js';

export { loadControllers } from './core.js';

export const startStimulusApp = (): Application =>
    startApplication(eagerControllers, lazyControllers, isApplicationDebug);
