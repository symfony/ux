# Field

Combine labels, controls, and help text to compose accessible form fields and grouped inputs.

```twig {"preview":true}
<div class="w-full max-w-md">
    <form>
        <twig:Field:Group>
            <twig:Field:Set>
                <twig:Field:Legend>Payment Method</twig:Field:Legend>
                <twig:Field:Description>
                    All transactions are secure and encrypted
                </twig:Field:Description>
                <twig:Field:Group>
                    <twig:Field>
                        <twig:Field:Label for="checkout-7j9-card-name-43j">
                            Name on Card
                        </twig:Field:Label>
                        <twig:Input
                            id="checkout-7j9-card-name-43j"
                            placeholder="Evil Rabbit"
                            required
                        />
                    </twig:Field>
                    <twig:Field>
                        <twig:Field:Label for="checkout-7j9-card-number-uw1">
                            Card Number
                        </twig:Field:Label>
                        <twig:Input
                            id="checkout-7j9-card-number-uw1"
                            placeholder="1234 5678 9012 3456"
                            required
                        />
                        <twig:Field:Description>
                            Enter your 16-digit card number
                        </twig:Field:Description>
                    </twig:Field>
                    <div class="grid grid-cols-3 gap-4">
                        <twig:Field>
                            <twig:Field:Label for="checkout-exp-month-ts6">
                                Month
                            </twig:Field:Label>
                            <twig:Select id="checkout-exp-month-ts6">
                                <option value="" disabled selected>MM</option>
                                <option value="01">01</option>
                                <option value="02">02</option>
                                <option value="03">03</option>
                                <option value="04">04</option>
                                <option value="05">05</option>
                                <option value="06">06</option>
                                <option value="07">07</option>
                                <option value="08">08</option>
                                <option value="09">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </twig:Select>
                        </twig:Field>
                        <twig:Field>
                            <twig:Field:Label for="checkout-7j9-exp-year-f59">
                                Year
                            </twig:Field:Label>
                            <twig:Select>
                                <option value="" disabled selected>YYY</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                                <option value="2028">2028</option>
                                <option value="2029">2029</option>
                            </twig:Select>
                        </twig:Field>
                        <twig:Field>
                            <twig:Field:Label for="checkout-7j9-cvv">CVV</twig:Field:Label>
                            <twig:Input id="checkout-7j9-cvv" placeholder="123" required />
                        </twig:Field>
                    </div>
                </twig:Field:Group>
            </twig:Field:Set>
            <twig:Field:Separator />
            <twig:Field:Set>
                <twig:Field:Legend>Billing Address</twig:Field:Legend>
                <twig:Field:Description>
                    The billing address associated with your payment method
                </twig:Field:Description>
                <twig:Field:Group>
                    <twig:Field orientation="horizontal">
                        <twig:Checkbox
                            id="checkout-7j9-same-as-shipping-wgm"
                            checked
                        />
                        <twig:Field:Label
                            for="checkout-7j9-same-as-shipping-wgm"
                            class="font-normal"
                        >
                            Same as shipping address
                        </twig:Field:Label>
                    </twig:Field>
                </twig:Field:Group>
            </twig:Field:Set>
            <twig:Field:Set>
                <twig:Field:Group>
                    <twig:Field>
                        <twig:Field:Label for="checkout-7j9-optional-comments">
                            Comments
                        </twig:Field:Label>
                        <twig:Textarea
                            id="checkout-7j9-optional-comments"
                            placeholder="Add any additional comments"
                            class="resize-none"
                        />
                    </twig:Field>
                </twig:Field:Group>
            </twig:Field:Set>
            <twig:Field orientation="horizontal">
                <twig:Button type="submit">Submit</twig:Button>
                <twig:Button variant="outline" type="button">
                    Cancel
                </twig:Button>
            </twig:Field>
        </twig:Field:Group>
    </form>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Field:Set>
    <twig:Field:Legend>Profile</twig:Field:Legend>
    <twig:Field:Description>This appears on invoices and emails.</twig:Field:Description>
    <twig:Field:Group>
        <twig:Field>
            <twig:Field:Label for="name">Full name</twig:Field:Label>
            <twig:Input id="name" autocomplete="off" placeholder="Evil Rabbit" />
            <twig:Field:Description>This appears on invoices and emails.</twig:Field:Description>
        </twig:Field>
        <twig:Field>
            <twig:Field:Label for="username">Username</twig:Field:Label>
            <twig:Input id="username" autocomplete="off" aria-invalid />
            <twig:Field:Error>Choose another username.</twig:Field:Error>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Switch id="newsletter" />
            <twig:Field:Label for="newsletter">Subscribe to the newsletter</twig:Field:Label>
        </twig:Field>
    </twig:Field:Group>
</twig:Field:Set>
```

## Examples

### Input

```twig {"preview":true}
<div class="w-full max-w-md">
    <twig:Field:Set>
        <twig:Field:Group>
            <twig:Field>
                <twig:Field:Label for="username">Username</twig:Field:Label>
                <twig:Input id="username" type="text" placeholder="Max Leiter" />
                <twig:Field:Description>
                    Choose a unique username for your account.
                </twig:Field:Description>
            </twig:Field>
            <twig:Field>
                <twig:Field:Label for="password">Password</twig:Field:Label>
                <twig:Field:Description>
                    Must be at least 8 characters long.
                </twig:Field:Description>
                <twig:Input id="password" type="password" placeholder="••••••••" />
            </twig:Field>
        </twig:Field:Group>
    </twig:Field:Set>
</div>
```

### Textarea

```twig {"preview":true}
<div class="w-full max-w-md">
    <twig:Field:Set>
        <twig:Field:Group>
            <twig:Field>
                <twig:Field:Label for="feedback">Feedback</twig:Field:Label>
                <twig:Textarea
                    id="feedback"
                    placeholder="Your feedback helps us improve..."
                    rows="4"
                />
                <twig:Field:Description>
                    Share your thoughts about our service.
                </twig:Field:Description>
            </twig:Field>
        </twig:Field:Group>
    </twig:Field:Set>
</div>
```

### Select

```twig {"preview":true}
<div class="w-full max-w-md">
    <twig:Field>
        <twig:Field:Label for="department">Department</twig:Field:Label>
        <twig:Select id="department">
            <option value="engineering">Engineering</option>
            <option value="design">Design</option>
            <option value="marketing">Marketing</option>
            <option value="sales">Sales</option>
            <option value="support">Customer Support</option>
            <option value="hr">Human Resources</option>
            <option value="finance">Finance</option>
            <option value="operations">Operations</option>
        </twig:Select>
        <twig:Field:Description>
            Select your department or area of work.
        </twig:Field:Description>
    </twig:Field>
</div>
```

### Fieldset

```twig {"preview":true}
<div class="w-full max-w-md space-y-6">
    <twig:Field:Set>
        <twig:Field:Legend>Address information</twig:Field:Legend>
        <twig:Field:Description>
            We need your address to deliver your order.
        </twig:Field:Description>
        <twig:Field:Group>
            <twig:Field>
                <twig:Field:Label for="street">Street address</twig:Field:Label>
                <twig:Input id="street" type="text" placeholder="123 Main St" />
            </twig:Field>
            <div class="grid grid-cols-2 gap-4">
                <twig:Field>
                    <twig:Field:Label for="city">City</twig:Field:Label>
                    <twig:Input id="city" type="text" placeholder="New York" />
                </twig:Field>
                <twig:Field>
                    <twig:Field:Label for="zip">Postal code</twig:Field:Label>
                    <twig:Input id="zip" type="text" placeholder="90502" />
                </twig:Field>
            </div>
        </twig:Field:Group>
    </twig:Field:Set>
</div>
```

### Checkbox

```twig {"preview":true}
<div class="w-full max-w-md">
    <twig:Field:Group>
        <twig:Field:Set>
            <twig:Field:Legend variant="label">
                Show these items on the desktop
            </twig:Field:Legend>
            <twig:Field:Description>
                Select the items you want to show on the desktop.
            </twig:Field:Description>
            <twig:Field:Group class="gap-3">
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="finder-pref-9k2-hard-disks-ljj" />
                    <twig:Field:Label
                        for="finder-pref-9k2-hard-disks-ljj"
                        class="font-normal"
                        checked
                    >
                        Hard disks
                    </twig:Field:Label>
                </twig:Field>
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="finder-pref-9k2-external-disks-1yg" />
                    <twig:Field:Label
                        for="finder-pref-9k2-external-disks-1yg"
                        class="font-normal"
                    >
                        External disks
                    </twig:Field:Label>
                </twig:Field>
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="finder-pref-9k2-cds-dvds-fzt" />
                    <twig:Field:Label
                        for="finder-pref-9k2-cds-dvds-fzt"
                        class="font-normal"
                    >
                        CDs, DVDs, and iPods
                    </twig:Field:Label>
                </twig:Field>
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="finder-pref-9k2-connected-servers-6l2" />
                    <twig:Field:Label
                        for="finder-pref-9k2-connected-servers-6l2"
                        class="font-normal"
                    >
                        Connected servers
                    </twig:Field:Label>
                </twig:Field>
            </twig:Field:Group>
        </twig:Field:Set>
        <twig:Field:Separator />
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="finder-pref-9k2-sync-folders-nep" checked />
            <twig:Field:Content>
                <twig:Field:Label for="finder-pref-9k2-sync-folders-nep">
                    Sync Desktop & Documents folders
                </twig:Field:Label>
                <twig:Field:Description>
                    Your Desktop & Documents folders are being synced with iCloud Drive. You can access them from other devices.
                </twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
    </twig:Field:Group>
</div>
```

### Switch

```twig {"preview":true}
<twig:Field orientation="horizontal" class="w-fit">
    <twig:Field:Label for="2fa">Multi-factor authentication</twig:Field:Label>
    <twig:Switch id="2fa" />
</twig:Field>
```

### Choice Card

Wrap `Field` components inside `Field:Label` to create selectable field groups. This works with `RadioGroup:Item`, `Checkbox`, and `Switch` components.

```twig {"preview":true}
<twig:Field:Group class="w-full max-w-xs">
    <twig:Field:Set>
        <twig:Field:Legend variant="label">Compute Environment</twig:Field:Legend>
        <twig:Field:Description>Select the compute environment for your cluster.</twig:Field:Description>
        <twig:RadioGroup>
            <twig:Field:Label for="kubernetes-r2h">
                <twig:Field orientation="horizontal">
                    <twig:Field:Content>
                        <twig:Field:Title>Kubernetes</twig:Field:Title>
                        <twig:Field:Description>Run GPU workloads on a K8s cluster.</twig:Field:Description>
                    </twig:Field:Content>
                    <twig:RadioGroup:Item name="compute" value="kubernetes" id="kubernetes-r2h" checked />
                </twig:Field>
            </twig:Field:Label>
            <twig:Field:Label for="vm-z4k">
                <twig:Field orientation="horizontal">
                    <twig:Field:Content>
                        <twig:Field:Title>Virtual Machine</twig:Field:Title>
                        <twig:Field:Description>Access a cluster to run GPU workloads.</twig:Field:Description>
                    </twig:Field:Content>
                    <twig:RadioGroup:Item name="compute" value="vm" id="vm-z4k" />
                </twig:Field>
            </twig:Field:Label>
        </twig:RadioGroup>
    </twig:Field:Set>
</twig:Field:Group>
```

### Field Group

```twig {"preview":true}
<div class="w-full max-w-md">
    <twig:Field:Group>
        <twig:Field:Set>
            <twig:Field:Label>Responses</twig:Field:Label>
            <twig:Field:Description>
                Get notified when ChatGPT responds to requests that take time, like research or image generation.
            </twig:Field:Description>
            <twig:Field:Group data-slot="checkbox-group">
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="push" checked disabled />
                    <twig:Field:Label for="push" class="font-normal">
                        Push notifications
                    </twig:Field:Label>
                </twig:Field>
            </twig:Field:Group>
        </twig:Field:Set>
        <twig:Field:Separator />
        <twig:Field:Set>
            <twig:Field:Label>Tasks</twig:Field:Label>
            <twig:Field:Description>
                Get notified when tasks you've created have updates. <a href="#">Manage tasks</a>
            </twig:Field:Description>
            <twig:Field:Group data-slot="checkbox-group">
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="push-tasks" />
                    <twig:Field:Label for="push-tasks" class="font-normal">
                        Push notifications
                    </twig:Field:Label>
                </twig:Field>
                <twig:Field orientation="horizontal">
                    <twig:Checkbox id="email-tasks" />
                    <twig:Field:Label for="email-tasks" class="font-normal">
                        Email notifications
                    </twig:Field:Label>
                </twig:Field>
            </twig:Field:Group>
        </twig:Field:Set>
    </twig:Field:Group>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8 w-full items-center">
    <div dir="rtl" class="w-full max-w-md">
        <form>
            <twig:Field:Group>
                <twig:Field:Set>
                    <twig:Field:Legend>طريقة الدفع</twig:Field:Legend>
                    <twig:Field:Description>جميع المعاملات آمنة ومشفرة</twig:Field:Description>
                    <twig:Field:Group>
                        <twig:Field>
                            <twig:Field:Label for="rtl-ar-name">الاسم على البطاقة</twig:Field:Label>
                            <twig:Input id="rtl-ar-name" placeholder="Evil Rabbit" required />
                        </twig:Field>
                        <twig:Field>
                            <twig:Field:Label for="rtl-ar-comments">تعليقات</twig:Field:Label>
                            <twig:Textarea id="rtl-ar-comments" placeholder="أضف أي تعليقات إضافية" class="resize-none" />
                        </twig:Field>
                    </twig:Field:Group>
                </twig:Field:Set>
                <twig:Field orientation="horizontal">
                    <twig:Button type="submit">إرسال</twig:Button>
                    <twig:Button variant="outline" type="button">إلغاء</twig:Button>
                </twig:Field>
            </twig:Field:Group>
        </form>
    </div>

    <div dir="rtl" class="w-full max-w-md">
        <form>
            <twig:Field:Group>
                <twig:Field:Set>
                    <twig:Field:Legend>שיטת תשלום</twig:Field:Legend>
                    <twig:Field:Description>כל העסקאות מאובטחות ומוגנות</twig:Field:Description>
                    <twig:Field:Group>
                        <twig:Field>
                            <twig:Field:Label for="rtl-he-name">שם מלא</twig:Field:Label>
                            <twig:Input id="rtl-he-name" placeholder="Evil Rabbit" required />
                        </twig:Field>
                        <twig:Field>
                            <twig:Field:Label for="rtl-he-comments">הערות</twig:Field:Label>
                            <twig:Textarea id="rtl-he-comments" placeholder="כתוב הערות" class="resize-none" />
                        </twig:Field>
                    </twig:Field:Group>
                </twig:Field:Set>
                <twig:Field orientation="horizontal">
                    <twig:Button type="submit">שלח</twig:Button>
                    <twig:Button variant="outline" type="button">בטל</twig:Button>
                </twig:Field>
            </twig:Field:Group>
        </form>
    </div>
</div>
```

## API Reference

::: api-reference
