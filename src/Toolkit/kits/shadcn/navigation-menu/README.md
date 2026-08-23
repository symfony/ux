# Navigation Menu

A collection of navigation links with optional hover-triggered submenus.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 340px">
    <twig:NavigationMenu>
        <twig:NavigationMenu:List>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                        Getting started
                        <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                    </twig:Button>
                </twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid gap-1 w-96">
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
                                <p class="line-clamp-2 text-sm text-muted-foreground">Styles for headings, paragraphs, lists...etc</p>
                            </a>
                        </li>
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                        Components
                        <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                    </twig:Button>
                </twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid gap-2 w-[500px] grid-cols-2">
                        {% set components = [
                            {title: 'Alert Dialog', href: '/docs/primitives/alert-dialog', description: 'A modal dialog that interrupts the user with important content and expects a response.'},
                            {title: 'Hover Card', href: '/docs/primitives/hover-card', description: 'For sighted users to preview content available behind a link.'},
                            {title: 'Progress', href: '/docs/primitives/progress', description: 'Displays an indicator showing the completion progress of a task, typically displayed as a progress bar.'},
                            {title: 'Scroll-area', href: '/docs/primitives/scroll-area', description: 'Visually or semantically separates content.'},
                            {title: 'Tabs', href: '/docs/primitives/tabs', description: 'A set of layered sections of content—known as tab panels—that are displayed one at a time.'},
                            {title: 'Tooltip', href: '/docs/primitives/tooltip', description: 'A popup that displays information related to an element when the element receives keyboard focus or the mouse hovers over it.'},
                        ] %}
                        {% for c in components %}
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="{{ c.href }}">
                                    <div class="text-sm font-medium leading-none">{{ c.title }}</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">{{ c.description }}</p>
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Link href="/docs">Docs</twig:NavigationMenu:Link>
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
            <twig:NavigationMenu:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>Docs</twig:Button>
            </twig:NavigationMenu:Trigger>
            <twig:NavigationMenu:Content>
                <twig:NavigationMenu:Link href="/docs">Introduction</twig:NavigationMenu:Link>
                <twig:NavigationMenu:Link href="/docs/installation">Installation</twig:NavigationMenu:Link>
            </twig:NavigationMenu:Content>
        </twig:NavigationMenu:Item>
    </twig:NavigationMenu:List>
</twig:NavigationMenu>
```

The submenu opens on hover and on keyboard focus, and stays open while the focus moves between the trigger and the links of the submenu.

## Examples

### Basic

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 340px">
    <twig:NavigationMenu>
        <twig:NavigationMenu:List>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                        Getting started
                        <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                    </twig:Button>
                </twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid gap-1 w-96">
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
                                <p class="line-clamp-2 text-sm text-muted-foreground">Styles for headings, paragraphs, lists...etc</p>
                            </a>
                        </li>
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                        Components
                        <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                    </twig:Button>
                </twig:NavigationMenu:Trigger>
                <twig:NavigationMenu:Content>
                    <ul class="grid gap-2 w-[500px] grid-cols-2">
                        {% set components = [
                            {title: 'Alert Dialog', href: '/docs/primitives/alert-dialog', description: 'A modal dialog that interrupts the user with important content and expects a response.'},
                            {title: 'Hover Card', href: '/docs/primitives/hover-card', description: 'For sighted users to preview content available behind a link.'},
                            {title: 'Progress', href: '/docs/primitives/progress', description: 'Displays an indicator showing the completion progress of a task, typically displayed as a progress bar.'},
                            {title: 'Scroll-area', href: '/docs/primitives/scroll-area', description: 'Visually or semantically separates content.'},
                            {title: 'Tabs', href: '/docs/primitives/tabs', description: 'A set of layered sections of content—known as tab panels—that are displayed one at a time.'},
                            {title: 'Tooltip', href: '/docs/primitives/tooltip', description: 'A popup that displays information related to an element when the element receives keyboard focus or the mouse hovers over it.'},
                        ] %}
                        {% for c in components %}
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="{{ c.href }}">
                                    <div class="text-sm font-medium leading-none">{{ c.title }}</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">{{ c.description }}</p>
                                </a>
                            </li>
                        {% endfor %}
                    </ul>
                </twig:NavigationMenu:Content>
            </twig:NavigationMenu:Item>
            <twig:NavigationMenu:Item>
                <twig:NavigationMenu:Link href="/docs">Docs</twig:NavigationMenu:Link>
            </twig:NavigationMenu:Item>
        </twig:NavigationMenu:List>
    </twig:NavigationMenu>
</div>
```

### With delays

Use the `openDelay` and `closeDelay` props of `NavigationMenu:Item` (in milliseconds) to avoid flickering when moving between items.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 200px">
    <twig:NavigationMenu>
        <twig:NavigationMenu:List>
            {% for item in ['Products', 'Solutions', 'Resources'] %}
                <twig:NavigationMenu:Item openDelay="150" closeDelay="300">
                    <twig:NavigationMenu:Trigger>
                        <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                            {{ item }}
                            <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                        </twig:Button>
                    </twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content class="w-56">
                        <twig:NavigationMenu:Link href="#" class="w-full justify-start">Overview</twig:NavigationMenu:Link>
                        <twig:NavigationMenu:Link href="#" class="w-full justify-start">Pricing</twig:NavigationMenu:Link>
                        <twig:NavigationMenu:Link href="#" class="w-full justify-start">Changelog</twig:NavigationMenu:Link>
                    </twig:NavigationMenu:Content>
                </twig:NavigationMenu:Item>
            {% endfor %}
        </twig:NavigationMenu:List>
    </twig:NavigationMenu>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-12" style="min-height: 720px">
    {# Arabic #}
    <div dir="rtl">
        <twig:NavigationMenu>
            <twig:NavigationMenu:List>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Trigger>
                        <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                            البدء
                            <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                        </twig:Button>
                    </twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content>
                        <ul class="grid gap-1 w-96">
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
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs/primitives/typography">
                                    <div class="text-sm font-medium leading-none">الطباعة</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">أنماط للعناوين والفقرات والقوائم...إلخ</p>
                                </a>
                            </li>
                        </ul>
                    </twig:NavigationMenu:Content>
                </twig:NavigationMenu:Item>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Trigger>
                        <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                            المكونات
                            <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                        </twig:Button>
                    </twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content>
                        <ul class="grid gap-2 w-[500px] grid-cols-2">
                            {% set components_ar = [
                                {title: 'حوار التنبيه', href: '/docs/primitives/alert-dialog', description: 'حوار نافذة يقطع المستخدم بمحتوى مهم ويتوقع استجابة.'},
                                {title: 'بطاقة التحويم', href: '/docs/primitives/hover-card', description: 'للمستخدمين المبصرين لمعاينة المحتوى المتاح خلف الرابط.'},
                                {title: 'التقدم', href: '/docs/primitives/progress', description: 'يعرض مؤشرًا يوضح تقدم إتمام المهمة، عادةً يتم عرضه كشريط تقدم.'},
                                {title: 'منطقة التمرير', href: '/docs/primitives/scroll-area', description: 'يفصل المحتوى بصريًا أو دلاليًا.'},
                                {title: 'التبويبات', href: '/docs/primitives/tabs', description: 'مجموعة من أقسام المحتوى المتعددة الطبقات—المعروفة بألواح التبويب—التي يتم عرضها واحدة في كل مرة.'},
                                {title: 'تلميح', href: '/docs/primitives/tooltip', description: 'نافذة منبثقة تعرض معلومات متعلقة بعنصر عند تحويم الماوس فوقه.'},
                            ] %}
                            {% for c in components_ar %}
                                <li>
                                    <a class="block rounded-md p-3 hover:bg-accent" href="{{ c.href }}">
                                        <div class="text-sm font-medium leading-none">{{ c.title }}</div>
                                        <p class="line-clamp-2 text-sm text-muted-foreground">{{ c.description }}</p>
                                    </a>
                                </li>
                            {% endfor %}
                        </ul>
                    </twig:NavigationMenu:Content>
                </twig:NavigationMenu:Item>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Link href="/docs">الوثائق</twig:NavigationMenu:Link>
                </twig:NavigationMenu:Item>
            </twig:NavigationMenu:List>
        </twig:NavigationMenu>
    </div>

    {# Hebrew #}
    <div dir="rtl">
        <twig:NavigationMenu>
            <twig:NavigationMenu:List>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Trigger>
                        <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                            התחלה
                            <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                        </twig:Button>
                    </twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content>
                        <ul class="grid gap-1 w-96">
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs">
                                    <div class="text-sm font-medium leading-none">הקדמה</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">רכיבים לשימוש חוזר שנבנו עם Tailwind CSS.</p>
                                </a>
                            </li>
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs/installation">
                                    <div class="text-sm font-medium leading-none">התקנה</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">כיצד להתקין תלויות ולבנות את האפליקציה שלך.</p>
                                </a>
                            </li>
                            <li>
                                <a class="block rounded-md p-3 hover:bg-accent" href="/docs/primitives/typography">
                                    <div class="text-sm font-medium leading-none">טיפוגרפיה</div>
                                    <p class="line-clamp-2 text-sm text-muted-foreground">סגנונות לכותרות, פסקאות, רשימות...וכו'</p>
                                </a>
                            </li>
                        </ul>
                    </twig:NavigationMenu:Content>
                </twig:NavigationMenu:Item>
                <twig:NavigationMenu:Item>
                    <twig:NavigationMenu:Trigger>
                        <twig:Button variant="ghost" size="sm" {{ ...navigation_menu_trigger_attrs }}>
                            רכיבים
                            <twig:ux:icon name="lucide:chevron-down" class="relative top-px size-3 transition-transform duration-200 in-data-[state=open]:rotate-180" aria-hidden="true" />
                        </twig:Button>
                    </twig:NavigationMenu:Trigger>
                    <twig:NavigationMenu:Content>
                        <ul class="grid gap-2 w-[500px] grid-cols-2">
                            {% set components_he = [
                                {title: 'דיאלוג התראה', href: '/docs/primitives/alert-dialog', description: 'דיאלוג מודאלי שמפריע למשתמש עם תוכן חשוב ומצפה לתגובה.'},
                                {title: 'כרטיס ריחוף', href: '/docs/primitives/hover-card', description: 'למשתמשים רואים כדי להציג תצוגה מקדימה של תוכן זמין מאחורי קישור.'},
                                {title: 'התקדמות', href: '/docs/primitives/progress', description: 'מציג אינדיקטור המציג את התקדמות ההשלמה של משימה, בדרך כלל מוצג כסרגל התקדמות.'},
                                {title: 'אזור גלילה', href: '/docs/primitives/scroll-area', description: 'מפריד תוכן חזותית או סמנטית.'},
                                {title: 'כרטיסיות', href: '/docs/primitives/tabs', description: 'קבוצה של חלקי תוכן מרובדים—המכונים לוחות כרטיסיות—המוצגים אחד בכל פעם.'},
                                {title: 'טולטיפ', href: '/docs/primitives/tooltip', description: 'חלון קופץ המציג מידע הקשור לאלמנט כאשר העכבר מרחף מעליו.'},
                            ] %}
                            {% for c in components_he %}
                                <li>
                                    <a class="block rounded-md p-3 hover:bg-accent" href="{{ c.href }}">
                                        <div class="text-sm font-medium leading-none">{{ c.title }}</div>
                                        <p class="line-clamp-2 text-sm text-muted-foreground">{{ c.description }}</p>
                                    </a>
                                </li>
                            {% endfor %}
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
