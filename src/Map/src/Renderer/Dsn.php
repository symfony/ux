<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Renderer;

use Symfony\UX\Map\Exception\InvalidArgumentException;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Dsn
{
    private readonly string $scheme;
    private readonly string $host;
    private readonly ?string $user;
    private readonly array $options;
    private readonly string $originalDsn;

    public function __construct(#[\SensitiveParameter] string $dsn)
    {
        $this->originalDsn = $dsn;

        if (false === $params = parse_url($dsn)) {
            throw new InvalidArgumentException('The map renderer DSN is invalid.');
        }

        if (!isset($params['scheme'])) {
            throw new InvalidArgumentException('The map renderer DSN must contain a scheme.');
        }
        $this->scheme = $params['scheme'];

        if (!isset($params['host'])) {
            throw new InvalidArgumentException('The map renderer DSN must contain a host (use "default" by default).');
        }
        $this->host = $params['host'];

        $this->user = '' !== ($params['user'] ?? '') ? rawurldecode($params['user']) : null;

        $options = [];
        parse_str($params['query'] ?? '', $options);
        $this->options = $options;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOriginalDsn(): string
    {
        return $this->originalDsn;
    }
}
