# Accordion

A vertically stacked set of interactive headings that each reveal a section of content.

```twig {"preview":true,"height":"300px"}
<twig:Accordion id="accordion-demo" defaultValue="{{ ['shipping'] }}" class="max-w-lg">
    <twig:Accordion:Item value="shipping">
        <twig:Accordion:Trigger>What are your shipping options?</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            We offer standard (5-7 days), express (2-3 days), and overnight
            shipping. Free shipping on international orders.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
    <twig:Accordion:Item value="returns">
        <twig:Accordion:Trigger>What is your return policy?</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            Returns accepted within 30 days. Items must be unused and in original
            packaging. Refunds processed within 5-7 business days.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
    <twig:Accordion:Item value="support">
        <twig:Accordion:Trigger>How can I contact customer support?</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            Reach us via email, live chat, or phone. We respond within 24 hours
            during business days.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
</twig:Accordion>
```

## Installation

::: installation

## Usage

```twig
<twig:Accordion id="accordion-usage" defaultValue="{{ ['item-1'] }}">
    <twig:Accordion:Item value="item-1">
        <twig:Accordion:Trigger>Is it accessible?</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            Yes. It adheres to the WAI-ARIA design pattern.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
</twig:Accordion>
```

## Examples

### Basic

A basic accordion that shows one item at a time. The first item is open by default.

```twig {"preview":true,"height":"300px"}
{% set items = [
    {
        value: 'item-1',
        trigger: 'How do I reset my password?',
        content:
        "Click on 'Forgot Password' on the login page, enter your email address, and we'll send you a link to reset your password. The link will expire in 24 hours.",
    },
    {
        value: 'item-2',
        trigger: 'Can I change my subscription plan?',
        content:
        'Yes, you can upgrade or downgrade your plan at any time from your account settings. Changes will be reflected in your next billing cycle.',
    },
    {
        value: 'item-3',
        trigger: 'What payment methods do you accept?',
        content:
        'We accept all major credit cards, PayPal, and bank transfers. All payments are processed securely through our payment partners.',
    },
] %}

<twig:Accordion id="accordion-basic" defaultValue="{{ ['item-1'] }}" class="max-w-lg">
    {% for item in items %}
        <twig:Accordion:Item value="{{ item.value }}">
            <twig:Accordion:Trigger>{{ item.trigger }}</twig:Accordion:Trigger>
            <twig:Accordion:Content>{{ item.content }}</twig:Accordion:Content>
        </twig:Accordion:Item>
    {% endfor %}
</twig:Accordion>
```

### Multiple

Use the `multiple` prop to allow multiple items to be open at the same time.

```twig {"preview":true,"height":"400px"}
{% set items = [
    {
        value: 'notifications',
        trigger: 'Notification Settings',
        content:
        'Manage how you receive notifications. You can enable email alerts for updates or push notifications for mobile devices.',
    },
    {
        value: 'privacy',
        trigger: 'Privacy & Security',
        content:
        'Control your privacy settings and security preferences. Enable two-factor authentication, manage connected devices, review active sessions, and configure data sharing preferences. You can also download your data or delete your account.',
    },
    {
        value: 'billing',
        trigger: 'Billing & Subscription',
        content:
        'View your current plan, payment history, and upcoming invoices. Update your payment method, change your subscription tier, or cancel your subscription.',
    },
] %}

<twig:Accordion id="accordion-multiple" multiple class="max-w-lg" defaultValue="{{ ['notifications'] }}">
    {% for item in items %}
        <twig:Accordion:Item value="{{ item.value }}">
            <twig:Accordion:Trigger>{{ item.trigger }}</twig:Accordion:Trigger>
            <twig:Accordion:Content>{{ item.content }}</twig:Accordion:Content>
        </twig:Accordion:Item>
    {% endfor %}
</twig:Accordion>
```

### Disabled

Use the `disabled` prop on `Accordion:Item` to disable individual items.

```twig {"preview":true,"height":"300px"}
<twig:Accordion id="accordion-disabled" class="max-w-lg">
    <twig:Accordion:Item value="item-1">
        <twig:Accordion:Trigger>Can I access my account history?</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            Yes, you can view your complete account history including all
            transactions, plan changes, and support tickets in the Account History
            section of your dashboard.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
    <twig:Accordion:Item value="item-2" disabled>
        <twig:Accordion:Trigger>Premium feature information</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            This section contains information about premium features. Upgrade your
            plan to access this content.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
    <twig:Accordion:Item value="item-3">
        <twig:Accordion:Trigger>How do I update my email address?</twig:Accordion:Trigger>
        <twig:Accordion:Content>
            You can update your email address in your account settings.
            You&apos;ll receive a verification email at your new address to
            confirm the change.
        </twig:Accordion:Content>
    </twig:Accordion:Item>
</twig:Accordion>
```

### Borders

Add `border` to the `Accordion` and `border-b last:border-b-0` to the `Accordion:Item` to add borders to the items.

```twig {"preview":true,"height":"300px"}
{% set items = [
    {
        value: 'billing',
        trigger: 'How does billing work?',
        content:
        'We offer monthly and annual subscription plans. Billing is charged at the beginning of each cycle, and you can cancel anytime. All plans include automatic backups, 24/7 support, and unlimited team members.',
    },
    {
        value: 'security',
        trigger: 'Is my data secure?',
        content:
        'Yes. We use end-to-end encryption, SOC 2 Type II compliance, and regular third-party security audits. All data is encrypted at rest and in transit using industry-standard protocols.',
    },
    {
        value: 'integration',
        trigger: 'What integrations do you support?',
        content:
        'We integrate with 500+ popular tools including Slack, Zapier, Salesforce, HubSpot, and more. You can also build custom integrations using our REST API and webhooks.',
    },
] %}

<twig:Accordion
    id="accordion-borders"
    class="max-w-lg rounded-lg border"
    defaultValue="{{ ['billing'] }}"
>
    {% for item in items %}
        <twig:Accordion:Item
            value="{{ item.value }}"
            class="border-b px-4 last:border-b-0"
        >
            <twig:Accordion:Trigger>{{ item.trigger }}</twig:Accordion:Trigger>
            <twig:Accordion:Content>{{ item.content }}</twig:Accordion:Content>
        </twig:Accordion:Item>
    {% endfor %}
</twig:Accordion>
```

### Card

Wrap the `Accordion` in a `Card` component.

```twig {"preview":true,"height":"450px"}
<twig:Card class="w-full max-w-sm">
    <twig:Card:Header>
        <twig:Card:Title>Subscription &amp; Billing</twig:Card:Title>
        <twig:Card:Description>
            Common questions about your account, plans, payments and cancellations.
        </twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        <twig:Accordion id="accordion-card" defaultValue="{{ ['plans'] }}">
            <twig:Accordion:Item value="plans">
                <twig:Accordion:Trigger>What subscription plans do you offer?</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    We offer three subscription tiers: Starter ($9/month), Professional ($29/month), and Enterprise ($99/month). Each plan includes increasing storage limits, API access, priority support, and team collaboration features.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
            <twig:Accordion:Item value="billing">
                <twig:Accordion:Trigger>How does billing work?</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    Billing occurs automatically at the start of each billing cycle. We accept all major credit cards, PayPal, and ACH transfers for enterprise customers. You'll receive an invoice via email after each payment.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
            <twig:Accordion:Item value="cancel">
                <twig:Accordion:Trigger>How do I cancel my subscription?</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    You can cancel your subscription anytime from your account settings. There are no cancellation fees or penalties. Your access will continue until the end of your current billing period.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
        </twig:Accordion>
    </twig:Card:Content>
</twig:Card>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"450px"}
<div class="flex flex-col gap-8">
    <div dir="rtl">
        <twig:Accordion id="accordion-rtl-ar" defaultValue="{{ ['item-1'] }}" class="max-w-md">
            <twig:Accordion:Item value="item-1">
                <twig:Accordion:Trigger>كيف يمكنني إعادة تعيين كلمة المرور؟</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    انقر على 'نسيت كلمة المرور' في صفحة تسجيل الدخول، أدخل عنوان بريدك الإلكتروني، وسنرسل لك رابطًا لإعادة تعيين كلمة المرور. سينتهي صلاحية الرابط خلال 24 ساعة.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
            <twig:Accordion:Item value="item-2">
                <twig:Accordion:Trigger>هل يمكنني تغيير خطة الاشتراك الخاصة بي؟</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    نعم، يمكنك ترقية أو تخفيض خطتك في أي وقت من إعدادات حسابك. ستظهر التغييرات في دورة الفوترة التالية.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
            <twig:Accordion:Item value="item-3">
                <twig:Accordion:Trigger>ما هي طرق الدفع التي تقبلونها؟</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    نقبل جميع بطاقات الائتمان الرئيسية و PayPal والتحويلات المصرفية. تتم معالجة جميع المدفوعات بأمان من خلال شركاء الدفع لدينا.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
        </twig:Accordion>
    </div>
    <div dir="rtl">
        <twig:Accordion id="accordion-rtl-he" defaultValue="{{ ['item-1'] }}" class="max-w-md">
            <twig:Accordion:Item value="item-1">
                <twig:Accordion:Trigger>איך אני משנה את הסיסמה שלי?</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    לחץ על 'שכחתי סיסמה' בעמוד ההתחברות, הזן את כתובת האימייל שלך, ונשלח לך קישור לשינוי הסיסמה. הקישור יסתיים תוך 24 שעות.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
            <twig:Accordion:Item value="item-2">
                <twig:Accordion:Trigger>האם אני יכול לשנות את תוכנית המנוי שלי?</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    כן, אתה יכול לשדרג או להוריד את התוכנית שלך בכל עת מההגדרות של החשבון שלך. השינויים יבואו לידי ביטוי במחזור החיוב הבא.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
            <twig:Accordion:Item value="item-3">
                <twig:Accordion:Trigger>אילו אמצעי תשלום אתם מקבלים?</twig:Accordion:Trigger>
                <twig:Accordion:Content>
                    אנו מקבלים כרטיסי אשראי, PayPal והעברות בנקאיות.
                </twig:Accordion:Content>
            </twig:Accordion:Item>
        </twig:Accordion>
    </div>
</div>
```

## API Reference

::: api-reference
