# Textarea

The textarea component is a multi-line text field input that can be used to receive longer chunks of text from the user in the form of a comment box, description field, and more.

```twig {"preview":true}
<form class="w-full">
    <twig:Label for="message" class="mb-2.5">Your message</twig:Label>
    <twig:Textarea id="message" rows="4" placeholder="Write your thoughts here..." />
</form>
```

## Installation

::: installation

## Usage

```twig
<twig:Textarea />
```

## Examples

### Comment Box

Most often the textarea component is used as the main text field input element in comment sections. Use this example to also apply a helper text and buttons below the textarea itself.

```twig {"preview":true}
<form class="w-full">
    <div class="w-full mb-4 border border-default-medium rounded-base bg-neutral-secondary-medium shadow-xs">
        <div class="px-4 py-2 bg-neutral-secondary-medium rounded-t-base">
            <label for="comment" class="sr-only">Your comment</label>
            <twig:Textarea id="comment" rows="4" class="px-0 border-0 shadow-none focus:ring-0" placeholder="Write a comment..." required />
        </div>
        <div class="flex items-center px-3 py-2 border-t border-default-medium">
            <twig:Button type="submit" size="sm">Post comment</twig:Button>
            <div class="flex ps-0 space-x-1 rtl:space-x-reverse sm:ps-2">
                <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                    <twig:ux:icon name="flowbite:face-grin-outline" class="size-5" aria-hidden="true" />
                    <span class="sr-only">Add emoji</span>
                </twig:Button>
                <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                    <twig:ux:icon name="flowbite:paper-clip-outline" class="size-5" aria-hidden="true" />
                    <span class="sr-only">Attach file</span>
                </twig:Button>
                <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                    <twig:ux:icon name="flowbite:map-pin-alt-outline" class="size-5" aria-hidden="true" />
                    <span class="sr-only">Set location</span>
                </twig:Button>
                <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                    <twig:ux:icon name="flowbite:file-image-outline" class="size-5" aria-hidden="true" />
                    <span class="sr-only">Upload image</span>
                </twig:Button>
            </div>
        </div>
    </div>
</form>
```

### WYSIWYG Editor

Use this example to add action buttons alongside a textarea for rich text editing capabilities.

```twig {"preview":true,"height":"400px"}
<form class="w-full">
    <div class="w-full mb-4 border border-default-medium rounded-base bg-neutral-secondary-medium shadow-xs">
        <div class="flex items-center justify-between px-3 py-2 border-b border-default-medium">
            <div class="flex flex-wrap items-center divide-default-medium sm:divide-x sm:rtl:divide-x-reverse">
                <div class="flex items-center space-x-1 rtl:space-x-reverse sm:pe-4">
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:paper-clip-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Attach file</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:map-pin-alt-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Embed map</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:file-image-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Upload image</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:file-code-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Format code</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:face-grin-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Add emoji</span>
                    </twig:Button>
                </div>
                <div class="flex flex-wrap items-center space-x-1 rtl:space-x-reverse sm:ps-4">
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:ordered-list-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Add list</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:adjustments-vertical-outline" class="size-5"
                                      aria-hidden="true"/>
                        <span class="sr-only">Settings</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:calendar-edit-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Timeline</span>
                    </twig:Button>
                    <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                        <twig:ux:icon name="flowbite:download-outline" class="size-5" aria-hidden="true"/>
                        <span class="sr-only">Download</span>
                    </twig:Button>
                </div>
            </div>
            <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
                <twig:ux:icon name="flowbite:expand-outline" class="size-5" aria-hidden="true"/>
                <span class="sr-only">Full screen</span>
            </twig:Button>
        </div>
        <div class="px-4 py-2 bg-neutral-secondary-medium rounded-b-base">
            <label for="editor" class="sr-only">Publish post</label>
            <twig:Textarea id="editor" rows="8" class="px-0 border-0 shadow-none focus:ring-0"
                           placeholder="Write an article..." required/>
        </div>
    </div>
    <twig:Button type="submit">Publish post</twig:Button>
</form>
```

### Chatroom Input

If you want to build a chatroom component you will usually want to use a textarea element to allow users to write multi-line chunks of text.

```twig {"preview":true}
<form class="w-full">
    <label for="chat" class="sr-only">Your message</label>
    <div class="flex items-center px-3 py-2 rounded-base bg-neutral-secondary-soft">
        <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
            <twig:ux:icon name="flowbite:paper-clip-outline" class="size-5" aria-hidden="true" />
            <span class="sr-only">Attach file</span>
        </twig:Button>
        <twig:Button type="button" size="icon-sm" variant="ghost" class="shrink-0 text-body cursor-pointer hover:bg-neutral-tertiary-medium hover:text-heading">
            <twig:ux:icon name="flowbite:file-image-outline" class="size-5" aria-hidden="true" />
            <span class="sr-only">Upload image</span>
        </twig:Button>
        <twig:Textarea id="chat" rows="1" class="mx-4 bg-neutral-primary-medium py-2.5 px-3" placeholder="Your message..." />
        <twig:Button type="button" size="icon" variant="ghost" class="shrink-0 text-fg-brand rounded-full cursor-pointer hover:bg-brand-softer">
            <twig:ux:icon name="flowbite:paper-plane-outline" class="size-6 rotate-90 rtl:-rotate-90" aria-hidden="true" />
            <span class="sr-only">Send message</span>
        </twig:Button>
    </div>
</form>
```

## API Reference

::: api-reference
