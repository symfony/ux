<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor;

use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Bridge\CKEditor\Transformer\CKEditorTransformer;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygBridge;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class CKEditorBridge extends AbstractWysiwygBridge
{
    public function getId(): string
    {
        return 'ckeditor';
    }

    public function getDefaultConfig(): EditorConfigInterface
    {
        return new CKEditorConfig();
    }

    public function createTransformer(): EditorContentTransformerInterface
    {
        return new CKEditorTransformer();
    }
}
