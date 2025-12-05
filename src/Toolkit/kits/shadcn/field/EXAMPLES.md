# Examples

## Input

```twig {"preview":true,"height":"500px"}
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
                <twig:Input id="password" type="password" placeholder="********" />
            </twig:Field>
        </twig:Field:Group>
    </twig:Field:Set>
</div>
```

## Textarea

```twig {"preview":true,"height":"400px"}
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

## Select

```twig {"preview":true,"height":"360px"}
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

## Field set

```twig {"preview":true,"height":"400px"}
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

## Checkbox

```twig {"preview":true,"height":"520px"}
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

## Switch

```twig {"preview":true,"height":"240px"}
<div class="w-full max-w-md">
    <twig:Field orientation="horizontal">
        <twig:Field:Content>
            <twig:Field:Label for="2fa">Multi-factor authentication</twig:Field:Label>
            <twig:Field:Description>
                Enable multi-factor authentication. If you do not have a two-factor device, you can use a one-time code sent to your email.
            </twig:Field:Description>
        </twig:Field:Content>
        <twig:Switch id="2fa" />
    </twig:Field>
</div>
```

## Field group

```twig {"preview":true,"height":"520px"}
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
