# Tabs

A set of layered sections of content—known as tab panels—that are displayed one at a time.

```twig {"preview":true,"height":"300px"}
<twig:Tabs defaultValue="overview" class="w-[400px]">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="overview">Overview</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="analytics">Analytics</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="reports">Reports</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings">Settings</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="overview">
        <twig:Card>
            <twig:Card:Header>
                <twig:Card:Title>Overview</twig:Card:Title>
                <twig:Card:Description>
                    View your key metrics and recent project activity. Track progress
                    across all your active projects.
                </twig:Card:Description>
            </twig:Card:Header>
            <twig:Card:Content class="text-muted-foreground text-sm">
                You have 12 active projects and 3 pending tasks.
            </twig:Card:Content>
        </twig:Card>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="analytics">
        <twig:Card>
            <twig:Card:Header>
                <twig:Card:Title>Analytics</twig:Card:Title>
                <twig:Card:Description>
                    Track performance and user engagement metrics. Monitor trends and
                    identify growth opportunities.
                </twig:Card:Description>
            </twig:Card:Header>
            <twig:Card:Content class="text-muted-foreground text-sm">
                Page views are up 25% compared to last month.
            </twig:Card:Content>
        </twig:Card>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="reports">
        <twig:Card>
            <twig:Card:Header>
                <twig:Card:Title>Reports</twig:Card:Title>
                <twig:Card:Description>
                    Generate and download your detailed reports. Export data in
                    multiple formats for analysis.
                </twig:Card:Description>
            </twig:Card:Header>
            <twig:Card:Content class="text-muted-foreground text-sm">
                You have 5 reports ready and available to export.
            </twig:Card:Content>
        </twig:Card>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="settings">
        <twig:Card>
            <twig:Card:Header>
                <twig:Card:Title>Settings</twig:Card:Title>
                <twig:Card:Description>
                    Manage your account preferences and options. Customize your
                    experience to fit your needs.
                </twig:Card:Description>
            </twig:Card:Header>
            <twig:Card:Content class="text-muted-foreground text-sm">
                Configure notifications, security, and themes.
            </twig:Card:Content>
        </twig:Card>
    </twig:Tabs:Content>
</twig:Tabs>
```

## Installation

::: installation

## Usage

```twig
<twig:Tabs defaultValue="account" class="w-[400px]">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="account">Account</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="password">Password</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="account">Make changes to your account here.</twig:Tabs:Content>
    <twig:Tabs:Content value="password">Change your password here.</twig:Tabs:Content>
</twig:Tabs>
```

## Examples

### Line

Use the `variant="line"` prop on `Tabs:List` for a line style.

```twig {"preview":true}
<twig:Tabs defaultValue="overview">
    <twig:Tabs:List variant="line">
        <twig:Tabs:Trigger value="overview">Overview</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="analytics">Analytics</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="reports">Reports</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Vertical

Use `orientation="vertical"` for vertical tabs.

```twig {"preview":true}
<twig:Tabs defaultValue="account" orientation="vertical">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="account">Account</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="password">Password</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="notifications">Notifications</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Disabled

```twig {"preview":true}
<twig:Tabs defaultValue="home">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="home">Home</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings" disabled>
            Disabled
        </twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Icons

```twig {"preview":true}
<twig:Tabs defaultValue="preview">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="preview">
            <twig:ux:icon name="lucide:app-window" />
            Preview
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="code">
            <twig:ux:icon name="lucide:code" />
            Code
        </twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"500px"}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <twig:Tabs defaultValue="overview" class="w-full max-w-sm" dir="rtl">
        <twig:Tabs:List dir="rtl">
            <twig:Tabs:Trigger value="overview">نظرة عامة</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="analytics">التحليلات</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="reports">التقارير</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="settings">الإعدادات</twig:Tabs:Trigger>
        </twig:Tabs:List>
        <twig:Tabs:Content value="overview">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>نظرة عامة</twig:Card:Title>
                    <twig:Card:Description>
                        عرض مقاييسك الرئيسية وأنشطة المشروع الأخيرة. تتبع التقدم عبر جميع مشاريعك النشطة.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    لديك ١٢ مشروعًا نشطًا و٣ مهام معلقة.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
        <twig:Tabs:Content value="analytics">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>التحليلات</twig:Card:Title>
                    <twig:Card:Description>
                        تتبع مقاييس الأداء ومشاركة المستخدمين. راقب الاتجاهات وحدد فرص النمو.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    زادت مشاهدات الصفحة بنسبة ٢٥٪ مقارنة بالشهر الماضي.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
        <twig:Tabs:Content value="reports">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>التقارير</twig:Card:Title>
                    <twig:Card:Description>
                        إنشاء وتنزيل تقاريرك التفصيلية. تصدير البيانات بتنسيقات متعددة للتحليل.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    لديك ٥ تقارير جاهزة ومتاحة للتصدير.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
        <twig:Tabs:Content value="settings">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>الإعدادات</twig:Card:Title>
                    <twig:Card:Description>
                        إدارة تفضيلات حسابك وخياراته. تخصيص تجربتك لتناسب احتياجاتك.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    تكوين الإشعارات والأمان والسمات.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
    </twig:Tabs>

    {# Hebrew #}
    <twig:Tabs defaultValue="overview" class="w-full max-w-sm" dir="rtl">
        <twig:Tabs:List dir="rtl">
            <twig:Tabs:Trigger value="overview">סקירה כללית</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="analytics">אנליטיקה</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="reports">דוחות</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="settings">הגדרות</twig:Tabs:Trigger>
        </twig:Tabs:List>
        <twig:Tabs:Content value="overview">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>סקירה כללית</twig:Card:Title>
                    <twig:Card:Description>
                        הצג את המדדים העיקריים שלך ואת הנתונים האחרונים. עקוב אחר ההתקדמות בכל המיזמים.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    יש לך 12 מיזמים נגישים ו-3 משימות ממתינות.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
        <twig:Tabs:Content value="analytics">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>אנליטיקה</twig:Card:Title>
                    <twig:Card:Description>
                        עקוב אחר ביצועים ומדדי מעורבות משתמשים. זהה מגמות והזדמנויות צמיחה.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    הגידול עמד על 25% בהשוואה לחודש שעבר.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
        <twig:Tabs:Content value="reports">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>דוחות</twig:Card:Title>
                    <twig:Card:Description>
                        צור והורד את הדוחות המלאים שלך. ייצא נתונים בתבניות שונות לניתוח.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    יש לך 5 דוחות מוכנים וזמינים לייצוא.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
        <twig:Tabs:Content value="settings">
            <twig:Card dir="rtl">
                <twig:Card:Header>
                    <twig:Card:Title>הגדרות</twig:Card:Title>
                    <twig:Card:Description>
                        ערוך את הגדרות החשבון שלך. התאם אישית את החוויה כך שתתאים לצרכיך.
                    </twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="text-sm text-muted-foreground">
                    הגדר התראות, אבטחה וערכות נושא.
                </twig:Card:Content>
            </twig:Card>
        </twig:Tabs:Content>
    </twig:Tabs>
</div>
```

## API Reference

::: api-reference
