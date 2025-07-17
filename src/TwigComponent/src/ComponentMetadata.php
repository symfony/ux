<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentMetadata
{
    /**
     * @internal
     */
    public function __construct(private array $config)
    {
    }

    public function getName(): string
    {
        return $this->config['key'];
    }

    /**
     * @return string Component's twig template
     */
    public function getTemplate(): string
    {
        return $this->config['template'];
    }

    /**
     * @return class-string The Component's FQCN
     */
    public function getClass(): string
    {
        return $this->config['class'];
    }

    /**
     * @return string The Component's service id
     */
    public function getServiceId(): string
    {
        return $this->config['service_id'];
    }

    public function isPublicPropsExposed(): bool
    {
        return $this->get('expose_public_props', false);
    }

    public function isAnonymous(): bool
    {
        return !isset($this->config['service_id']);
    }

    public function getAttributesVar(): string
    {
        return $this->get('attributes_var', 'attributes');
    }

    /**
     * @return list<string>
     *
     * @internal
     */
    public function getPreMounts(): array
    {
        return $this->get('pre_mount', []);
    }

    /**
     * @return list<string>
     *
     * @internal
     */
    public function getMounts(): array
    {
        return $this->get('mount', []);
    }

    /**
     * @return list<string>
     *
     * @internal
     */
    public function getPostMounts(): array
    {
        return $this->get('post_mount', []);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
