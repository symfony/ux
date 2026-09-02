<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Formats pagination information messages with i18n support.
 *
 * Message ids are the natural English messages: without a configured
 * translator, an identity translator renders them as-is, including
 * "%count%" pluralization.
 *
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PaginationInfoFormatter
{
    private readonly TranslatorInterface $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator ?? new class implements TranslatorInterface {
            use TranslatorTrait;
        };
    }

    /**
     * Format info message for offset-based pagination.
     *
     * @param NumberedPaginationInterface<mixed> $pagination
     */
    public function format(NumberedPaginationInterface $pagination): string
    {
        if (0 === $pagination->count()) {
            return $this->translator->trans('No items', [], 'UXPaginationBundle');
        }

        $start = $pagination->getFirstItemNumber();
        $end = $pagination->getLastItemNumber();
        \assert(null !== $start && null !== $end);

        if (null !== $total = $pagination->getTotalItems()) {
            return $this->translator->trans('Showing %start%-%end% of %total%', [
                '%start%' => $start,
                '%end%' => $end,
                '%total%' => $total,
            ], 'UXPaginationBundle');
        }

        return $this->translator->trans('Showing %start%-%end%', [
            '%start%' => $start,
            '%end%' => $end,
        ], 'UXPaginationBundle');
    }

    /**
     * Format info message for cursor-based pagination.
     *
     * @param CursorPaginationInterface<mixed> $pagination
     */
    public function formatCursor(CursorPaginationInterface $pagination): string
    {
        $count = $pagination->count();
        if (0 === $count) {
            return $this->translator->trans('No items', [], 'UXPaginationBundle');
        }

        if ($pagination->hasNext()) {
            return $this->translator->trans('Showing %count% item|Showing %count% items', [
                '%count%' => $count,
            ], 'UXPaginationBundle');
        }

        return $this->translator->trans('Showing %count% item (last page)|Showing %count% items (last page)', [
            '%count%' => $count,
        ], 'UXPaginationBundle');
    }
}
