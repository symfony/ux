# Card

Displays a card with header, content, and footer.

```twig {"preview":true}
<twig:Card class="w-full max-w-sm">
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
                    <twig:Label for="email">Email</twig:Label>
                    <twig:Input
                        id="email"
                        type="email"
                        placeholder="m@example.com"
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <div class="flex items-center">
                        <twig:Label for="password">Password</twig:Label>
                        <a
                            href="#"
                            class="ml-auto inline-block text-sm underline-offset-4 hover:underline"
                        >
                            Forgot your password?
                        </a>
                    </div>
                    <twig:Input id="password" type="password" required />
                </div>
            </div>
        </form>
    </twig:Card:Content>
    <twig:Card:Footer class="flex-col gap-2">
        <twig:Button type="submit" class="w-full">
            Login
        </twig:Button>
        <twig:Button variant="outline" class="w-full">
            Login with Google
        </twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

## Installation

::: installation

## Usage

```twig
<twig:Card>
    <twig:Card:Header>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <twig:Card:Description>Card Description</twig:Card:Description>
        <twig:Card:Action>Card Action</twig:Card:Action>
    </twig:Card:Header>
    <twig:Card:Content>
        <p>Card Content</p>
    </twig:Card:Content>
    <twig:Card:Footer>
        <p>Card Footer</p>
    </twig:Card:Footer>
</twig:Card>
```

## Examples

### Size

Use the `size="sm"` prop to set the size of the card to small. The small size variant uses smaller spacing.

```twig {"preview":true}
<twig:Card size="sm" class="mx-auto w-full max-w-sm">
    <twig:Card:Header>
        <twig:Card:Title>Small Card</twig:Card:Title>
        <twig:Card:Description>
            This card uses the small size variant.
        </twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        <p>
            The card component supports a size prop that can be set to
            "sm" for a more compact appearance.
        </p>
    </twig:Card:Content>
    <twig:Card:Footer>
        <twig:Button variant="outline" size="sm" class="w-full">
            Action
        </twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### Spacing

In addition to the `size` prop, you can use the `--card-spacing` CSS variable to control the spacing between sections and the inset of card parts.

```twig {"preview":true}
<twig:Card class="mx-auto w-full max-w-sm [--card-spacing:--spacing(6)]">
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
                    <twig:Label for="email-spacing">Email</twig:Label>
                    <twig:Input
                        id="email-spacing"
                        type="email"
                        placeholder="m@example.com"
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <div class="flex items-center">
                        <twig:Label for="password-spacing">Password</twig:Label>
                        <a
                            href="#"
                            class="ms-auto inline-block text-sm underline-offset-4 hover:underline"
                        >
                            Forgot your password?
                        </a>
                    </div>
                    <twig:Input id="password-spacing" type="password" required />
                </div>
            </div>
        </form>
    </twig:Card:Content>
    <twig:Card:Footer class="flex-col gap-2">
        <twig:Button type="submit" class="w-full">
            Login
        </twig:Button>
        <twig:Button variant="outline" class="w-full">
            Login with Google
        </twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### Edge to Edge

Use negative margins with `-mx-(--card-spacing)` to make content go edge to edge while keeping it aligned with the card inset. When the edge-to-edge content sits above a footer, use `-mb-(--card-spacing)` on `Card:Content` to remove the section gap.

```twig {"preview":true}
<twig:Card class="mx-auto w-full max-w-sm">
    <twig:Card:Header>
        <twig:Card:Title>Terms of Service</twig:Card:Title>
        <twig:Card:Description>
            Review the terms before accepting the agreement.
        </twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content class="-mb-(--card-spacing)">
        <div class="-mx-(--card-spacing) max-h-48 space-y-4 overflow-y-scroll border-t bg-muted/50 px-(--card-spacing) py-4 text-sm leading-relaxed">
            <p>
                These terms govern your use of the workspace, including access to
                shared documents, project files, and collaboration tools.
            </p>
            <p>
                You are responsible for the content you upload and for ensuring that
                your team has the appropriate permissions to view or edit it.
            </p>
            <p>
                We may update features or limits as the service evolves. When those
                changes materially affect your workflow, we will notify your
                workspace administrators.
            </p>
            <p>
                By continuing, you agree to keep your account credentials secure and
                to follow your organization's acceptable use policies.
            </p>
        </div>
    </twig:Card:Content>
    <twig:Card:Footer class="justify-end gap-2">
        <twig:Button variant="outline">Decline</twig:Button>
        <twig:Button>Accept</twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### Image

Add an image before the card header to create a card with an image.

```twig {"preview":true}
<twig:Card class="relative mx-auto w-full max-w-sm pt-0">
    <div class="absolute inset-0 z-30 aspect-video bg-black/35"></div>
    <img
        src="https://avatar.vercel.sh/shadcn1"
        alt="Event cover"
        class="relative z-20 aspect-video w-full object-cover brightness-60 grayscale dark:brightness-40"
    />
    <twig:Card:Header>
        <twig:Card:Action>
            <twig:Badge variant="secondary">Featured</twig:Badge>
        </twig:Card:Action>
        <twig:Card:Title>Design systems meetup</twig:Card:Title>
        <twig:Card:Description>
            A practical talk on component APIs, accessibility, and shipping faster.
        </twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Footer>
        <twig:Button class="w-full">View Event</twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col items-center gap-8">
    {# Arabic #}
    <twig:Card class="w-full max-w-sm" dir="rtl">
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

    {# Hebrew #}
    <twig:Card class="w-full max-w-sm" dir="rtl">
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
</div>
```

## Accessibility

- `Card` and its parts are plain containers with no role, so the content inside keeps its own semantics. A card is not a landmark and is not announced as a grouping.
- `Card:Title` renders a `<div>`, not a heading. When the card is a section of the page, give it heading semantics with `role="heading"` and `aria-level`, so the title lands in the document outline.
- Do not make the whole card clickable by wrapping it in a link. Put the interactive element inside, in `Card:Action` or `Card:Footer`, so its accessible name is the action rather than the entire card text.
- When several cards repeat the same action label, extend each one with the item it applies to, for instance `aria-label="Renew Pro plan"`.

## API Reference

::: api-reference
