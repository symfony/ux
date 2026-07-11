<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Preset;

use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\EditorPresetInterface;

final class WysiwygMinimalPreset implements EditorPresetInterface
{
    public function build(): EditorConfigInterface
    {
        return new CKEditorConfig(
            common: new CommonOptions(
                toolbar: ['bold', 'italic', 'link'],
                placeholder: 'Write…',
            ),
            licenseKey: 'GPL',
        );
    }
}
