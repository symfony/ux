# Alert

Displays a callout for user attention.

```twig {"preview":true}
<div class="grid w-full max-w-md items-start gap-4">
    <twig:Alert>
        <twig:ux:icon name="lucide:circle-check-big" />
        <twig:Alert:Title>Payment successful</twig:Alert:Title>
        <twig:Alert:Description>
            Your payment of $29.99 has been processed. A receipt has been sent to your email address.
        </twig:Alert:Description>
    </twig:Alert>
    <twig:Alert>
        <twig:ux:icon name="lucide:info" />
        <twig:Alert:Title>New feature available</twig:Alert:Title>
        <twig:Alert:Description>
            We've added dark mode support. You can enable it in your account settings.
        </twig:Alert:Description>
    </twig:Alert>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Alert>
    <twig:ux:icon name="lucide:info" />
    <twig:Alert:Title>Heads up!</twig:Alert:Title>
    <twig:Alert:Description>
        You can add components to your app using the cli.
    </twig:Alert:Description>
    <twig:Alert:Action>
        <twig:Button variant="outline">Enable</twig:Button>
    </twig:Alert:Action>
</twig:Alert>
```

## Examples

### Basic

A basic alert with an icon, title and description.

```twig {"preview":true}
<twig:Alert class="max-w-md">
    <twig:ux:icon name="lucide:circle-check" />
    <twig:Alert:Title>Account updated successfully</twig:Alert:Title>
    <twig:Alert:Description>
        Your profile information has been saved. Changes will be reflected immediately.
    </twig:Alert:Description>
</twig:Alert>
```

### Destructive

Use `variant="destructive"` to create a destructive alert.

```twig {"preview":true}
<twig:Alert variant="destructive" class="max-w-md">
    <twig:ux:icon name="lucide:circle-alert" />
    <twig:Alert:Title>Payment failed</twig:Alert:Title>
    <twig:Alert:Description>
        Your payment could not be processed. Please check your payment method and try again.
    </twig:Alert:Description>
</twig:Alert>
```

### Action

Use `Alert:Action` to add a button or other action element to the alert.

```twig {"preview":true}
<twig:Alert class="max-w-md">
    <twig:Alert:Title>Dark mode is now available</twig:Alert:Title>
    <twig:Alert:Description>
        Enable it under your profile settings to get started.
    </twig:Alert:Description>
    <twig:Alert:Action>
        <twig:Button size="xs" variant="default">
            Enable
        </twig:Button>
    </twig:Alert:Action>
</twig:Alert>
```

### Custom Colors

You can customize the alert colors by adding custom classes such as `bg-amber-50 dark:bg-amber-950` to the `Alert` component.

```twig {"preview":true}
<twig:Alert class="max-w-md border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-50">
    <twig:ux:icon name="lucide:triangle-alert" />
    <twig:Alert:Title>Your subscription will expire in 3 days.</twig:Alert:Title>
    <twig:Alert:Description>
        Renew now to avoid service interruption or upgrade to a paid plan to continue using the service.
    </twig:Alert:Description>
</twig:Alert>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <div dir="rtl">
        <div class="grid w-full max-w-md items-start gap-4">
            <twig:Alert>
                <twig:ux:icon name="lucide:circle-check-big" />
                <twig:Alert:Title>تم الدفع بنجاح</twig:Alert:Title>
                <twig:Alert:Description>
                    تمت معالجة دفعتك البالغة 29.99 دولارًا. تم إرسال إيصال إلى عنوان بريدك الإلكتروني.
                </twig:Alert:Description>
            </twig:Alert>
            <twig:Alert>
                <twig:ux:icon name="lucide:info" />
                <twig:Alert:Title>ميزة جديدة متاحة</twig:Alert:Title>
                <twig:Alert:Description>
                    لقد أضفنا دعم الوضع الداكن. يمكنك تفعيله في إعدادات حسابك.
                </twig:Alert:Description>
            </twig:Alert>
        </div>
    </div>
    <div dir="rtl">
        <div class="grid w-full max-w-md items-start gap-4">
            <twig:Alert>
                <twig:ux:icon name="lucide:circle-check-big" />
                <twig:Alert:Title>התשלום בוצע בהצלחה</twig:Alert:Title>
                <twig:Alert:Description>
                    התשלום שלך בסך 29.99 דולר עובד. קבלה נשלחה לכתובת האימייל שלך.
                </twig:Alert:Description>
            </twig:Alert>
            <twig:Alert>
                <twig:ux:icon name="lucide:info" />
                <twig:Alert:Title>תכונה חדשה זמינה</twig:Alert:Title>
                <twig:Alert:Description>
                    כעת יש תמיכה במצב כהה. ניתן לאשר זאת בהגדרות החשבון.
                </twig:Alert:Description>
            </twig:Alert>
        </div>
    </div>
</div>
```

## API Reference

::: api-reference
