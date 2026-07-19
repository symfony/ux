<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\DependencyParser;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KitManifest
{
    /**
     * @param list<DependencyInterface> $dependencies Kit-global dependencies, available to every recipe
     * @param ?string                   $color        Accent color of the kit, used when rendering its documentation
     * @param ?string                   $icon         Path, relative to the kit root, to an SVG icon of the kit
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $license,
        public readonly string $homepage,
        public ?string $installAsMarkdown = null,
        public readonly array $dependencies = [],
        public readonly ?string $color = null,
        public readonly ?string $icon = null,
    ) {
        Assert::kitName($this->name);

        if (!filter_var($this->homepage, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(\sprintf('Invalid homepage URL "%s".', $this->homepage));
        }
    }

    /**
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        return new self(
            name: $data['name'] ?? throw new \InvalidArgumentException('Property "name" is required.'),
            description: $data['description'] ?? throw new \InvalidArgumentException('Property "description" is required.'),
            license: $data['license'] ?? throw new \InvalidArgumentException('Property "license" is required.'),
            homepage: $data['homepage'] ?? throw new \InvalidArgumentException('Property "homepage" is required.'),
            dependencies: DependencyParser::parse($data['dependencies'] ?? null, allowRecipe: false),
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
        );
    }
}
