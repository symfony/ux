<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Config;

use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygConfig;
use Symfony\UX\Editor\Config\CommonOptions;

final class CKEditorConfig extends AbstractWysiwygConfig
{
    public function __construct(
        CommonOptions $common = new CommonOptions(),
        public readonly array $extraPlugins = [],
        public readonly array $removePlugins = [],
        public readonly ?array $heading = null,
        public readonly ?array $image = null,
        public readonly ?array $link = null,
        public readonly ?string $licenseKey = null,
        array $nativeOverrides = [],
    ) {
        parent::__construct($common, $nativeOverrides);
    }

    public function getBridgeId(): string
    {
        return 'ckeditor';
    }

    protected function translateCommon(CommonOptions $c): array
    {
        $out = [];
        if (null !== $c->toolbar) {
            $out['toolbar'] = ['items' => $c->toolbar];
        }
        if (null !== $c->placeholder) {
            $out['placeholder'] = $c->placeholder;
        }
        if ($c->readOnly) {
            $out['readOnly'] = true;
        }
        if (null !== $c->language) {
            $out['language'] = $c->language;
        }
        if ([] !== $c->plugins) {
            $out['extraPlugins'] = $c->plugins;
        }

        return $out;
    }

    protected function translateOwn(): array
    {
        $out = [];
        if ([] !== $this->extraPlugins) {
            $out['extraPlugins'] = $this->extraPlugins;
        }
        if ([] !== $this->removePlugins) {
            $out['removePlugins'] = $this->removePlugins;
        }
        if (null !== $this->heading) {
            $out['heading'] = $this->heading;
        }
        if (null !== $this->image) {
            $out['image'] = $this->image;
        }
        if (null !== $this->link) {
            $out['link'] = $this->link;
        }
        if (null !== $this->licenseKey) {
            $out['licenseKey'] = $this->licenseKey;
        }

        return $out;
    }
}
