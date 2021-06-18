/*
 * This file is part of the Symfony Live Component package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import '@symfony/stimulus-testing/setup';
// adds the missing "fetch" function - fetch-mock-jest will replace this
global.fetch = require('node-fetch');
