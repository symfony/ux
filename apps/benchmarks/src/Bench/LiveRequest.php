<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class LiveRequest
{
    public function __construct(
        public readonly string $url,
        public readonly string $body,
        public readonly string $pageUrl,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [
            'Accept' => 'application/vnd.live-component+html',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Live-Url' => $this->pageUrl,
        ];
    }

    public function contentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}
