<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\QuillJs\DTO;

use Symfony\UX\QuillJs\DTO\Fields\BlockField\AlignField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\BackgroundColorField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\ColorField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\DirectionField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\FontField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\HeaderField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\HeaderGroupField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\IndentField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\ListField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\ScriptField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\SizeField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\BlockQuoteField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\BoldField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\CleanField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\CodeBlockField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\CodeField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\EmojiField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\FormulaField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\ImageField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\ItalicField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\LinkField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\StrikeField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\UnderlineField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\VideoField;
use Symfony\UX\QuillJs\DTO\Fields\Interfaces\QuillBlockFieldInterface;
use Symfony\UX\QuillJs\DTO\Fields\Interfaces\QuillGroupInterface;
use Symfony\UX\QuillJs\DTO\Fields\Interfaces\QuillInlineFieldInterface;

final class QuillGroup implements QuillGroupInterface
{
    /**
     * @return array<int<0, max>, array<string>|string>
     */
    public static function build(QuillBlockFieldInterface|QuillInlineFieldInterface ...$fields): array
    {
        $array = [];
        foreach ($fields as $field) {
            if ($field instanceof QuillInlineFieldInterface) {
                $array[] = $field->getOption();
            }
            if ($field instanceof QuillBlockFieldInterface) {
                foreach ($field->getOption() as $key => $option) {
                    $array[][$key] = $option;
                }
            }
        }

        return $array;
    }

    /**
     * @return array<int<0, max>, array<string>|string>
     */
    public static function buildWithAllFields(): array
    {
        $stylingFields = [
            new BoldField(),
            new ItalicField(),
            new UnderlineField(),
            new StrikeField(),
            new BlockQuoteField(),
            new LinkField(),
            new SizeField(),
            new HeaderField(),
            new HeaderGroupField(),
            new ColorField(),
            new IndentField(),
        ];

        $orgaFields = [
            new AlignField(),
            new BackgroundColorField(),
            new ListField(),
            new ListField(ListField::LIST_FIELD_OPTION_BULLET),
            new ListField(ListField::LIST_FIELD_OPTION_CHECK),
            new FontField(),
            new DirectionField(),
            new CodeField(),
            new CodeBlockField(),
            new ScriptField(),
            new ScriptField(ScriptField::SCRIPT_FIELD_OPTION_SUPER),
            new FormulaField(),
        ];

        $otherFields = [
            new ImageField(),
            new VideoField(),
            new EmojiField(),
            new CleanField(),
        ];

        $fields = array_merge($stylingFields, $orgaFields, $otherFields);

        return self::build(...$fields);
    }
}
