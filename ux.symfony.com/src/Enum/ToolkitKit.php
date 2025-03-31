<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

/**
 * For convenience and performance, official UX Toolkit kits are hardcoded.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum ToolkitKit: string
{
    case Shadcn = 'shadcn';

    public function getHumanName(): string
    {
        return match ($this) {
            self::Shadcn => 'Shadcn UI',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Shadcn => 'Component based on the Shadcn UI library, one of the most popular design systems in JavaScript world.',
        };
    }

    public function getDescriptionForComponent(string $component): string
    {
        return match ($this) {
            self::Shadcn => 'Component "'.$component.'" based on the Shadcn UI library, one of the most popular design systems in JavaScript world.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Shadcn => 'simple-icons:shadcnui',
        };
    }

    public function getGettingStarted(): string
    {
        return match ($this) {
            self::Shadcn => <<<MARKDOWN
            # Getting started

            This kit provides ready-to-use and fully-customizable UI Twig components based on [Shadcn UI](https://ui.shadcn.com/) components's **design**.

            Please note that not every Shadcn UI component is available in this kit, but we are working on it!

            ## Requirements

            This kit requires TailwindCSS to work:
            - If you use Symfony AssetMapper, you can install TailwindCSS with the [TailwindBundle](https://symfony.com/bundles/TailwindBundle/current/index.html),
            - If you use Webpack Encore, you can follow the [TailwindCSS installation guide for Symfony](https://tailwindcss.com/docs/installation/framework-guides/symfony)

            ## Installation

            In your `assets/styles/app.css`, after the TailwindCSS imports, add the following code:

            ```css
            @custom-variant dark (&:is(.dark *));

            :root {
                --radius: 0.625rem;
                --background: oklch(1 0 0);
                --foreground: oklch(0.145 0 0);
                --card: oklch(1 0 0);
                --card-foreground: oklch(0.145 0 0);
                --popover: oklch(1 0 0);
                --popover-foreground: oklch(0.145 0 0);
                --primary: oklch(0.205 0 0);
                --primary-foreground: oklch(0.985 0 0);
                --secondary: oklch(0.97 0 0);
                --secondary-foreground: oklch(0.205 0 0);
                --muted: oklch(0.97 0 0);
                --muted-foreground: oklch(0.556 0 0);
                --accent: oklch(0.97 0 0);
                --accent-foreground: oklch(0.205 0 0);
                --destructive: oklch(0.577 0.245 27.325);
                --border: oklch(0.922 0 0);
                --input: oklch(0.922 0 0);
                --ring: oklch(0.708 0 0);
                --chart-1: oklch(0.646 0.222 41.116);
                --chart-2: oklch(0.6 0.118 184.704);
                --chart-3: oklch(0.398 0.07 227.392);
                --chart-4: oklch(0.828 0.189 84.429);
                --chart-5: oklch(0.769 0.188 70.08);
                --sidebar: oklch(0.985 0 0);
                --sidebar-foreground: oklch(0.145 0 0);
                --sidebar-primary: oklch(0.205 0 0);
                --sidebar-primary-foreground: oklch(0.985 0 0);
                --sidebar-accent: oklch(0.97 0 0);
                --sidebar-accent-foreground: oklch(0.205 0 0);
                --sidebar-border: oklch(0.922 0 0);
                --sidebar-ring: oklch(0.708 0 0);
            }

            .dark {
                --background: oklch(0.145 0 0);
                --foreground: oklch(0.985 0 0);
                --card: oklch(0.205 0 0);
                --card-foreground: oklch(0.985 0 0);
                --popover: oklch(0.269 0 0);
                --popover-foreground: oklch(0.985 0 0);
                --primary: oklch(0.922 0 0);
                --primary-foreground: oklch(0.205 0 0);
                --secondary: oklch(0.269 0 0);
                --secondary-foreground: oklch(0.985 0 0);
                --muted: oklch(0.269 0 0);
                --muted-foreground: oklch(0.708 0 0);
                --accent: oklch(0.371 0 0);
                --accent-foreground: oklch(0.985 0 0);
                --destructive: oklch(0.704 0.191 22.216);
                --border: oklch(1 0 0 / 10%);
                --input: oklch(1 0 0 / 15%);
                --ring: oklch(0.556 0 0);
                --chart-1: oklch(0.488 0.243 264.376);
                --chart-2: oklch(0.696 0.17 162.48);
                --chart-3: oklch(0.769 0.188 70.08);
                --chart-4: oklch(0.627 0.265 303.9);
                --chart-5: oklch(0.645 0.246 16.439);
                --sidebar: oklch(0.205 0 0);
                --sidebar-foreground: oklch(0.985 0 0);
                --sidebar-primary: oklch(0.488 0.243 264.376);
                --sidebar-primary-foreground: oklch(0.985 0 0);
                --sidebar-accent: oklch(0.269 0 0);
                --sidebar-accent-foreground: oklch(0.985 0 0);
                --sidebar-border: oklch(1 0 0 / 10%);
                --sidebar-ring: oklch(0.439 0 0);
            }

            @layer base {
                * {
                    border-color: var(--border);
                    outline-color: var(--ring);
                }

                body {
                    background-color: var(--background);
                    color: var(--foreground);
                }
            }
            ```

            And voilà! You are now ready to use Shadcn components in your Symfony project.
            MARKDOWN,
        };
    }
}
