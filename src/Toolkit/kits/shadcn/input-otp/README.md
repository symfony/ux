# Input OTP

A group of single-character inputs for one-time passwords and verification codes.

```twig {"preview":true}
<twig:InputOtp inputs="6">
    <twig:InputOtp:Group>
        {% for i in 1..6 %}
            <twig:InputOtp:Slot input="{{ i }}" value="{{ i }}" />
        {% endfor %}
    </twig:InputOtp:Group>
</twig:InputOtp>
```

## Installation

::: installation

## Usage

```twig
<twig:InputOtp inputs="6">
    <twig:InputOtp:Slot input="1" />
    <twig:InputOtp:Slot input="2" />
    <twig:InputOtp:Slot input="3" />
    <twig:InputOtp:Slot input="4" />
    <twig:InputOtp:Slot input="5" />
    <twig:InputOtp:Slot input="6" />
</twig:InputOtp>
```

## Examples

### Four Digits

Use the `inputs` prop on `InputOtp` to control the number of slots; each `InputOtp:Slot` derives its accessible label and `name` from it and its own `input` position.

```twig {"preview":true}
<twig:InputOtp inputs="4">
    <twig:InputOtp:Group>
        {% for i in 1..4 %}
            <twig:InputOtp:Slot input="{{ i }}" pattern="[0-9]" />
        {% endfor %}
    </twig:InputOtp:Group>
</twig:InputOtp>
```

### Separator

Use `InputOtp:Separator` between `InputOtp:Group` components to visually split the code.

```twig {"preview":true}
<twig:InputOtp inputs="6">
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="1" />
        <twig:InputOtp:Slot input="2" />
    </twig:InputOtp:Group>
    <twig:InputOtp:Separator />
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="3" />
        <twig:InputOtp:Slot input="4" />
    </twig:InputOtp:Group>
    <twig:InputOtp:Separator />
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="5" />
        <twig:InputOtp:Slot input="6" />
    </twig:InputOtp:Group>
</twig:InputOtp>
```

### Pattern

Use the `pattern` attribute on each `InputOtp:Slot` to restrict the accepted characters.

```twig {"preview":true}
<div class="flex flex-col gap-2 w-fit">
    <twig:Label for="digits-only">Digits Only</twig:Label>
    <twig:InputOtp inputs="6" id="digits-only">
        <twig:InputOtp:Group>
            {% for i in 1..6 %}
                <twig:InputOtp:Slot input="{{ i }}" pattern="[0-9]" />
            {% endfor %}
        </twig:InputOtp:Group>
    </twig:InputOtp>
</div>
```

### Alphanumeric

Combine `inputmode="text"` with a `pattern` to accept letters and digits.

```twig {"preview":true}
<twig:InputOtp inputs="6">
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="1" inputmode="text" pattern="[A-Za-z0-9]" />
        <twig:InputOtp:Slot input="2" inputmode="text" pattern="[A-Za-z0-9]" />
        <twig:InputOtp:Slot input="3" inputmode="text" pattern="[A-Za-z0-9]" />
    </twig:InputOtp:Group>
    <twig:InputOtp:Separator />
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="4" inputmode="text" pattern="[A-Za-z0-9]" />
        <twig:InputOtp:Slot input="5" inputmode="text" pattern="[A-Za-z0-9]" />
        <twig:InputOtp:Slot input="6" inputmode="text" pattern="[A-Za-z0-9]" />
    </twig:InputOtp:Group>
</twig:InputOtp>
```

### Controlled

Listen to the `input` event to react to the value as the user types, here with a small display controller.

```twig {"preview":true}
<div class="space-y-2" data-controller="input-otp-display">
    <twig:InputOtp inputs="6" data-action="input->input-otp-display#update">
        <twig:InputOtp:Group>
            {% for i in 1..6 %}
                <twig:InputOtp:Slot input="{{ i }}" />
            {% endfor %}
        </twig:InputOtp:Group>
    </twig:InputOtp>
    <div class="text-center text-sm">
        <span data-input-otp-display-target="empty">Enter your one-time password.</span>
        <span data-input-otp-display-target="filled" hidden>You entered: <span data-input-otp-display-target="output"></span></span>
    </div>
</div>
```

### Disabled

Use the `disabled` attribute on the slots to disable the OTP input.

```twig {"preview":true}
<twig:InputOtp inputs="6">
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="1" value="1" disabled />
        <twig:InputOtp:Slot input="2" value="2" disabled />
        <twig:InputOtp:Slot input="3" value="3" disabled />
    </twig:InputOtp:Group>
    <twig:InputOtp:Separator />
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="4" value="4" disabled />
        <twig:InputOtp:Slot input="5" value="5" disabled />
        <twig:InputOtp:Slot input="6" value="6" disabled />
    </twig:InputOtp:Group>
</twig:InputOtp>
```

### Invalid

Use `aria-invalid="true"` on the slots to mark the code as invalid.

```twig {"preview":true}
<twig:InputOtp inputs="6">
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="1" value="0" aria-invalid="true" />
        <twig:InputOtp:Slot input="2" value="0" aria-invalid="true" />
    </twig:InputOtp:Group>
    <twig:InputOtp:Separator />
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="3" value="0" aria-invalid="true" />
        <twig:InputOtp:Slot input="4" value="0" aria-invalid="true" />
    </twig:InputOtp:Group>
    <twig:InputOtp:Separator />
    <twig:InputOtp:Group>
        <twig:InputOtp:Slot input="5" value="0" aria-invalid="true" />
        <twig:InputOtp:Slot input="6" value="0" aria-invalid="true" />
    </twig:InputOtp:Group>
</twig:InputOtp>
```

### Form

A complete verification form combining `InputOtp` with `Card`, `Label` and `Button`.

```twig {"preview":true,"collapseClass":true}
<twig:Card class="mx-auto max-w-md">
    <twig:Card:Header>
        <twig:Card:Title>Verify your login</twig:Card:Title>
        <twig:Card:Description>
            Enter the verification code we sent to your email address: <span class="font-medium">m@example.com</span>.
        </twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <twig:Label for="otp-verification">Verification code</twig:Label>
                <twig:Button variant="outline" size="sm" class="gap-1.5">
                    <twig:ux:icon name="lucide:refresh-cw" class="size-3.5" />
                    Resend Code
                </twig:Button>
            </div>
            <twig:InputOtp inputs="6" id="otp-verification">
                <twig:InputOtp:Group class="*:data-[slot=input-otp-slot]:h-12 *:data-[slot=input-otp-slot]:w-11 *:data-[slot=input-otp-slot]:text-xl">
                    <twig:InputOtp:Slot input="1" required />
                    <twig:InputOtp:Slot input="2" required />
                    <twig:InputOtp:Slot input="3" required />
                </twig:InputOtp:Group>
                <twig:InputOtp:Separator class="mx-2" />
                <twig:InputOtp:Group class="*:data-[slot=input-otp-slot]:h-12 *:data-[slot=input-otp-slot]:w-11 *:data-[slot=input-otp-slot]:text-xl">
                    <twig:InputOtp:Slot input="4" required />
                    <twig:InputOtp:Slot input="5" required />
                    <twig:InputOtp:Slot input="6" required />
                </twig:InputOtp:Group>
            </twig:InputOtp>
            <p class="text-sm text-muted-foreground">
                <a href="#" class="underline underline-offset-4 hover:text-primary">I no longer have access to this email address.</a>
            </p>
        </div>
    </twig:Card:Content>
    <twig:Card:Footer class="flex flex-col gap-2">
        <twig:Button type="submit" class="w-full">Verify</twig:Button>
        <div class="text-sm text-muted-foreground">
            Having trouble signing in?
            <a href="#" class="underline underline-offset-4 hover:text-primary">Contact support</a>
        </div>
    </twig:Card:Footer>
</twig:Card>
```

### RTL

Use the `name` prop on `InputOtp` to change the base name of the inputs; here with right-to-left layouts in Arabic and Hebrew.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4">
    {# Arabic #}
    <div dir="rtl" class="flex flex-col gap-2 w-fit">
        <twig:Label for="ar-otp">رمز التحقق</twig:Label>
        <twig:InputOtp inputs="6" name="ar-otp" id="ar-otp">
            <twig:InputOtp:Group>
                {% for i in 1..6 %}
                    <twig:InputOtp:Slot input="{{ i }}" />
                {% endfor %}
            </twig:InputOtp:Group>
        </twig:InputOtp>
    </div>

    {# Hebrew #}
    <div dir="rtl" class="flex flex-col gap-2 w-fit">
        <twig:Label for="he-otp">קוד אימות</twig:Label>
        <twig:InputOtp inputs="6" name="he-otp" id="he-otp">
            <twig:InputOtp:Group>
                {% for i in 1..6 %}
                    <twig:InputOtp:Slot input="{{ i }}" />
                {% endfor %}
            </twig:InputOtp:Group>
        </twig:InputOtp>
    </div>
</div>
```

## API Reference

::: api-reference
