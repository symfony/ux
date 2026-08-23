# Navigation Menu

A collection of navigation links with optional hover-triggered submenus.

```twig {"preview":true,"height":"340px"}
<div class="flex items-start justify-center" style="min-height: 320px">
    <twig:NavigationMenu>
        <twig:NavigationMenu:List>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>Getting started</twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid w-96 gap-1">
                        <li>
                            <a class="block rounded-md p-3 hover:bg-accent" href="/docs">
                                <div class="text-sm font-medium leading-none">Introduction</div>
                                <p class="line-clamp-2 text-sm text-muted-foreground">Reusable components built with Tailwind CSS.</p>
                            </a>
                        </li>
                        <li>
                            <a class="block rounded-md p-3 hover:bg-accent" href="/docs/installation">
                                <div class="text-sm font-medium leading-none">Installation</div>
                                <p class="line-clamp-2 text-sm text-muted-foreground">How to install dependencies and structure your app.</p>
                            </a>
                        </li>
                        <li>
                            <a class="block rounded-md p-3 hover:bg-accent" href="/docs/primitives/typography">
                                <div class="text-sm font-medium leading-none">Typography</div>
                                <p class="line-clamp-2 text-sm text-muted-foreground">Styles for headings, paragraphs, lists, and more.</p>
                            </a>
                        </li>
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>Components</twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid w-[500px] grid-cols-2 gap-2">
                        {% set components = [
                            {title: 'Alert Dialog', href: '/docs/primitives/alert-dialog', description: 'A modal dialog that interrupts the user with important content and expects a response.'},
                            {title: 'Hover Card', href: '/docs/primitives/hover-card', description: 'For sighted users to preview content available behind a link.'},
                            {title: 'Progress', href: '/docs/primitives/progress', description: 'Displays an indicator showing the completion progress of a task.'},
                            {title: 'Scroll Area', href: '/docs/primitives/scroll-area', description: 'Visually or semantically separates content.'},
                            {title: 'Tabs', href: '/docs/primitives/tabs', description: 'Layered sections of content displayed one panel at a time.'},
                            {title: 'Tooltip', href: '/docs/primitives/tooltip', description: 'A popup that displays information related to an element on hover or focus.'},
                        ] %}
                        {% for component in components %}
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="{{ component.href }}">
                                    <div class="text-sm font-medium leading-none">{{ component.title }}</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">{{ component.description }}</p>
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Link href="/docs">Docs</twig:NavigationMenu:Link>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>Resources</twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid w-[500px] grid-cols-2 gap-2">
                        {% set resources = [
                            {title: 'Blog', href: '/blog', description: 'Read the latest news and articles from the team.'},
                            {title: 'Changelog', href: '/changelog', description: 'See what shipped in every release.'},
                            {title: 'Support', href: '/support', description: 'Get help from the community and the maintainers.'},
                            {title: 'Roadmap', href: '/roadmap', description: 'Discover what we are planning to build next.'},
                        ] %}
                        {% for resource in resources %}
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="{{ resource.href }}">
                                    <div class="text-sm font-medium leading-none">{{ resource.title }}</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">{{ resource.description }}</p>
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
        </twig:NavigationMenu:List>
    </twig:NavigationMenu>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:NavigationMenu>
    <twig:NavigationMenu:List>
        <twig:NavigationMenu:Item>
            <twig:NavigationMenu:Link href="/">Home</twig:NavigationMenu:Link>
        </twig:NavigationMenu:Item>
        <twig:NavigationMenu:Item>
            <twig:NavigationMenu:Link href="/docs">Docs</twig:NavigationMenu:Link>
        </twig:NavigationMenu:Item>
    </twig:NavigationMenu:List>
</twig:NavigationMenu>
```

## Examples

### Simple

A menu of plain links, without any submenu.

```twig {"preview":true,"height":"120px"}
<div class="flex items-start justify-center" style="min-height: 100px">
    <twig:NavigationMenu>
        <twig:NavigationMenu:List>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Link href="/">Home</twig:NavigationMenu:Link>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Link href="/docs">Docs</twig:NavigationMenu:Link>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Link href="/pricing">Pricing</twig:NavigationMenu:Link>
            </twig:NavigationMenu:Item>
        </twig:NavigationMenu:List>
    </twig:NavigationMenu>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"340px"}
<div class="flex flex-col items-center gap-16 py-8" style="min-height: 320px">
    {# Arabic #}
    <div dir="rtl">
        <twig:NavigationMenu>
            <twig:NavigationMenu:List>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Trigger>ابدأ</twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content>
                        <ul class="grid w-96 gap-1">
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs">
                                    <div class="text-sm font-medium leading-none">مقدمة</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">مكونات قابلة لإعادة الاستخدام مبنية باستخدام Tailwind CSS.</p>
                                </a>
                            </li>
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs/installation">
                                    <div class="text-sm font-medium leading-none">التثبيت</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">كيفية تثبيت التبعيات وتنظيم تطبيقك.</p>
                                </a>
                            </li>
                        </ul>
                    </twig:NavigationMenu:Content>
                </twig:NavigationMenu:Item>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Link href="/docs">التوثيق</twig:NavigationMenu:Link>
                </twig:NavigationMenu:Item>
            </twig:NavigationMenu:List>
        </twig:NavigationMenu>
    </div>

    {# Hebrew #}
    <div dir="rtl">
        <twig:NavigationMenu>
            <twig:NavigationMenu:List>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Trigger>תחילת העבודה</twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content>
                        <ul class="grid w-96 gap-1">
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs">
                                    <div class="text-sm font-medium leading-none">מבוא</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">רכיבים לשימוש חוזר הבנויים עם Tailwind CSS.</p>
                                </a>
                            </li>
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs/installation">
                                    <div class="text-sm font-medium leading-none">התקנה</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">כיצד להתקין תלויות ולבנות את האפליקציה שלך.</p>
                                </a>
                            </li>
                        </ul>
                    </twig:NavigationMenu:Content>
                </twig:NavigationMenu:Item>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Link href="/docs">תיעוד</twig:NavigationMenu:Link>
                </twig:NavigationMenu:Item>
            </twig:NavigationMenu:List>
        </twig:NavigationMenu>
    </div>
</div>
```

## API Reference

::: api-reference
