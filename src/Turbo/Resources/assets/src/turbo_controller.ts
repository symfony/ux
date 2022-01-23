/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

// Expose Turbo to the rest of the app to allow for dynamic Turbo calls
window.Turbo = Turbo;

/**
 * Empty Stimulus controller only used for Symfony Flex wiring.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
export default class extends Controller {}
