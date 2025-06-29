/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export function mountDOM(html: string = '') {
    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div);

    return div;
}

export function clearDOM() {
    document.body.innerHTML = '';
}
