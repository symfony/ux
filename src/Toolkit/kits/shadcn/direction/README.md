# Direction

A provider component that sets the text direction for your application.

```twig {"preview":true}
<twig:Tabs defaultValue="ar" class="w-full max-w-sm">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="en">English</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="ar">Arabic (العربية)</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="he">Hebrew (עברית)</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="en">
        <twig:Direction direction="ltr" lang="en">
            <twig:Card>
                <twig:Card:Header>
                    <twig:Card:Title>Login to your account</twig:Card:Title>
                    <twig:Card:Description>
                        Enter your email below to login to your account
                    </twig:Card:Description>
                    <twig:Card:Action>
                        <twig:Button variant="link">Sign Up</twig:Button>
                    </twig:Card:Action>
                </twig:Card:Header>
                <twig:Card:Content>
                    <form>
                        <div class="flex flex-col gap-6">
                            <div class="grid gap-2">
                                <twig:Label for="email-en">Email</twig:Label>
                                <twig:Input id="email-en" type="email" placeholder="m@example.com" required />
                            </div>
                            <div class="grid gap-2">
                                <div class="flex items-center">
                                    <twig:Label for="password-en">Password</twig:Label>
                                    <a href="#" class="ms-auto inline-block text-sm underline-offset-4 hover:underline">
                                        Forgot your password?
                                    </a>
                                </div>
                                <twig:Input id="password-en" type="password" required />
                            </div>
                        </div>
                    </form>
                </twig:Card:Content>
                <twig:Card:Footer class="flex-col gap-2">
                    <twig:Button type="submit" class="w-full">Login</twig:Button>
                    <twig:Button variant="outline" class="w-full">Login with Google</twig:Button>
                </twig:Card:Footer>
            </twig:Card>
        </twig:Direction>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="ar">
        <twig:Direction direction="rtl" lang="ar">
            <twig:Card>
                <twig:Card:Header>
                    <twig:Card:Title>تسجيل الدخول إلى حسابك</twig:Card:Title>
                    <twig:Card:Description>
                        أدخل بريدك الإلكتروني أدناه لتسجيل الدخول إلى حسابك
                    </twig:Card:Description>
                    <twig:Card:Action>
                        <twig:Button variant="link">إنشاء حساب</twig:Button>
                    </twig:Card:Action>
                </twig:Card:Header>
                <twig:Card:Content>
                    <form>
                        <div class="flex flex-col gap-6">
                            <div class="grid gap-2">
                                <twig:Label for="email-ar">البريد الإلكتروني</twig:Label>
                                <twig:Input id="email-ar" type="email" placeholder="m@example.com" required />
                            </div>
                            <div class="grid gap-2">
                                <div class="flex items-center">
                                    <twig:Label for="password-ar">كلمة المرور</twig:Label>
                                    <a href="#" class="ms-auto inline-block text-sm underline-offset-4 hover:underline">
                                        نسيت كلمة المرور؟
                                    </a>
                                </div>
                                <twig:Input id="password-ar" type="password" required />
                            </div>
                        </div>
                    </form>
                </twig:Card:Content>
                <twig:Card:Footer class="flex-col gap-2">
                    <twig:Button type="submit" class="w-full">تسجيل الدخول</twig:Button>
                    <twig:Button variant="outline" class="w-full">تسجيل الدخول باستخدام Google</twig:Button>
                </twig:Card:Footer>
            </twig:Card>
        </twig:Direction>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="he">
        <twig:Direction direction="rtl" lang="he">
            <twig:Card>
                <twig:Card:Header>
                    <twig:Card:Title>התחברות לחשבון שלך</twig:Card:Title>
                    <twig:Card:Description>
                        הזן את כתובת האימייל שלך למטה כדי להתחבר לחשבון שלך
                    </twig:Card:Description>
                    <twig:Card:Action>
                        <twig:Button variant="link">הרשמה</twig:Button>
                    </twig:Card:Action>
                </twig:Card:Header>
                <twig:Card:Content>
                    <form>
                        <div class="flex flex-col gap-6">
                            <div class="grid gap-2">
                                <twig:Label for="email-he">כתובת אימייל</twig:Label>
                                <twig:Input id="email-he" type="email" placeholder="m@example.com" required />
                            </div>
                            <div class="grid gap-2">
                                <div class="flex items-center">
                                    <twig:Label for="password-he">סיסמה</twig:Label>
                                    <a href="#" class="ms-auto inline-block text-sm underline-offset-4 hover:underline">
                                        שכחת את הסיסמה?
                                    </a>
                                </div>
                                <twig:Input id="password-he" type="password" required />
                            </div>
                        </div>
                    </form>
                </twig:Card:Content>
                <twig:Card:Footer class="flex-col gap-2">
                    <twig:Button type="submit" class="w-full">התחברות</twig:Button>
                    <twig:Button variant="outline" class="w-full">התחברות עם Google</twig:Button>
                </twig:Card:Footer>
            </twig:Card>
        </twig:Direction>
    </twig:Tabs:Content>
</twig:Tabs>
```

## Installation

::: installation

## Usage

Wrap the content with `Direction`, and set the same direction on the `<html>` element:

```twig
<html dir="rtl">
    <body>
        <twig:Direction direction="rtl">
            {# Your app content #}
        </twig:Direction>
    </body>
</html>
```

`Direction` renders a `<div>` with the `dir` attribute. The components of the kit follow the direction of their nearest `dir` ancestor: their classes use logical properties and `rtl:` variants, and their Stimulus controllers read the computed direction of their element. To see more examples, look for the RTL section on the component pages.

## Accessibility

- `Direction` renders a plain `<div>` with no role, so the content inside keeps its own semantics.
- Set the `lang` attribute next to the direction, for instance `lang="ar"`. Screen readers choose their pronunciation from `lang`, not from `dir`.

## API Reference

::: api-reference
