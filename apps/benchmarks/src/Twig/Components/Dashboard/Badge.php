<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Dashboard;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
final class Badge
{
    public string $variant = 'default';

    #[ExposeInTemplate]
    public function getClasses(): string
    {
        return match ($this->variant) {
            'secondary' => 'bg-secondary text-secondary-foreground [a]:hover:bg-secondary/80',
            'destructive' => 'bg-destructive/10 text-destructive focus-visible:ring-destructive/20 dark:bg-destructive/20 dark:focus-visible:ring-destructive/40 [a]:hover:bg-destructive/20',
            'outline' => 'border-border text-foreground [a]:hover:bg-muted [a]:hover:text-muted-foreground',
            'ghost' => 'hover:bg-muted hover:text-muted-foreground dark:hover:bg-muted/50',
            'link' => 'text-primary underline-offset-4 hover:underline',
            default => 'bg-primary text-primary-foreground [a]:hover:bg-primary/80',
        };
    }
}
