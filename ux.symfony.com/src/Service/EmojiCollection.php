<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

/**
 * Collection of emojis used in demos.
 *
 * @implements \IteratorAggregate<string>
 *
 * @internal
 */
final class EmojiCollection implements \IteratorAggregate, \Countable
{
    private array $emojis;

    public function __construct(array $emojis = [])
    {
        $this->emojis = $emojis ?: $this->loadEmojis();
    }

    public function paginate(int $page, int $perPage): self
    {
        return new self(\array_slice($this->emojis, ($page - 1) * $perPage, $perPage));
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->emojis);
    }

    public function count(): int
    {
        return \count($this->emojis);
    }

    private function loadEmojis(): array
    {
        return [
            '🐻',
            '🐨',
            '🐼',
            '🦥',
            '🦦',
            '🦨',
            '🦘',
            '🦡',
            '🐾',
            '🦃',
            '🐔',
            '🐓',
            '🐣',
            '🐤',
            '🐥',
            '🐦',
            '🐧',
            '🕊️',
            '🦅',
            '🦆',
            '🦢',
            '🦉',
            '🦤',
            '🪶',
            '🦩',
            '🦚',
            '🦜',
            '🪽',
            '🐦‍⬛',
            '🪿',
            '🐸',
            '🐊',
            '🐢',
            '🦎',
            '🐍',
            '🐲',
            '🐉',
            '🦕',
            '🦖',
            '🐳',
            '🐋',
            '🐬',
            '🦭',
            '🐟',
            '🐠',
            '🐡',
            '🦈',
            '🐙',
            '🐚',
            '🪸',
            '🪼',
            '🐌',
            '🦋',
            '🐛',
            '🐜',
            '🐝',
            '🪲',
            '🐞',
            '🦗',
            '🪳',
            '🕷️',
            '🕸️',
            '🦂',
            '🦟',
            '🪰',
            '🪱',
            '🦠',
            '💐',
            '🌸',
            '💮',
            '🪷',
            '🏵️',
            '🌹',
            '🥀',
            '🌺',
            '🌻',
            '🌼',
            '🌷',
            '🪻',
            '🌱',
            '🪴',
            '🌲',
            '🌳',
            '🌴',
            '🌵',
            '🌾',
            '🌿',
            '☘️',
            '🍀',
            '🍁',
            '🍂',
            '🍃',
            '🪹',
            '🪺',
            '🍄',
            '🍇',
            '🍈',
            '🍉',
            '🍊',
            '🍋',
            '🍌',
            '🍍',
            '🥭',
            '🍎',
            '🍏',
            '🍐',
            '🍑',
            '🍒',
            '🍓',
            '🫐',
            '🥝',
            '🍅',
            '🫒',
            '🥥',
            '🥑',
            '🍆',
            '🥔',
            '🥕',
            '🌽',
            '🌶️',
            '🫑',
            '🥒',
            '🥬',
            '🥦',
            '🧄',
            '🧅',
            '🍄',
            '🍠',
            '🥜',
            '🌰',
        ];
    }
}
