# Aspect Ratio

Displays content within a desired ratio.

```twig {"preview":true}
<div class="w-full max-w-sm">
    <twig:AspectRatio ratio="16 / 9" class="rounded-lg bg-muted">
        <img
            src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbDpzcGFjZT0icHJlc2VydmUiIHN0eWxlPSJmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtzdHJva2UtbGluZWpvaW46cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6MiIgdmlld0JveD0iMCAwIDY0IDY0Ij48cGF0aCBkPSJNMCAwaDY0djY0SDB6IiBzdHlsZT0iZmlsbDp1cmwoI2EpIi8+PGRlZnM+PHJhZGlhbEdyYWRpZW50IGlkPSJhIiBjeD0iMCIgY3k9IjAiIHI9IjEiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoNzcgNTggLTU4IDc3IDEgMTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojYjRiNGI0O3N0b3Atb3BhY2l0eToxIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojOGY4ZjhmO3N0b3Atb3BhY2l0eToxIi8+PC9yYWRpYWxHcmFkaWVudD48L2RlZnM+PC9zdmc+"
            alt="Photo"
            class="absolute inset-0 h-full w-full rounded-lg object-cover grayscale dark:brightness-20"
        />
    </twig:AspectRatio>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:AspectRatio ratio="16 / 9">
    <img
        src="..."
        alt="Image"
        class="rounded-md object-cover"
    />
</twig:AspectRatio>
```

## Examples

### Square

A square aspect ratio component using the `ratio="1 / 1"` prop. This is useful for displaying images in a square format.

```twig {"preview":true}
<div class="w-full max-w-[12rem]">
    <twig:AspectRatio ratio="1 / 1" class="rounded-lg bg-muted">
        <img
            src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbDpzcGFjZT0icHJlc2VydmUiIHN0eWxlPSJmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtzdHJva2UtbGluZWpvaW46cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6MiIgdmlld0JveD0iMCAwIDY0IDY0Ij48cGF0aCBkPSJNMCAwaDY0djY0SDB6IiBzdHlsZT0iZmlsbDp1cmwoI2EpIi8+PGRlZnM+PHJhZGlhbEdyYWRpZW50IGlkPSJhIiBjeD0iMCIgY3k9IjAiIHI9IjEiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoNzcgNTggLTU4IDc3IDEgMTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojYjRiNGI0O3N0b3Atb3BhY2l0eToxIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojOGY4ZjhmO3N0b3Atb3BhY2l0eToxIi8+PC9yYWRpYWxHcmFkaWVudD48L2RlZnM+PC9zdmc+"
            alt="Photo"
            class="absolute inset-0 h-full w-full rounded-lg object-cover grayscale dark:brightness-20"
        />
    </twig:AspectRatio>
</div>
```

### Portrait

A portrait aspect ratio component using the `ratio="9 / 16"` prop. This is useful for displaying images in a portrait format.

```twig {"preview":true}
<div class="w-full max-w-[10rem]">
    <twig:AspectRatio ratio="9 / 16" class="rounded-lg bg-muted">
        <img
            src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbDpzcGFjZT0icHJlc2VydmUiIHN0eWxlPSJmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtzdHJva2UtbGluZWpvaW46cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6MiIgdmlld0JveD0iMCAwIDY0IDY0Ij48cGF0aCBkPSJNMCAwaDY0djY0SDB6IiBzdHlsZT0iZmlsbDp1cmwoI2EpIi8+PGRlZnM+PHJhZGlhbEdyYWRpZW50IGlkPSJhIiBjeD0iMCIgY3k9IjAiIHI9IjEiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoNzcgNTggLTU4IDc3IDEgMTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojYjRiNGI0O3N0b3Atb3BhY2l0eToxIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojOGY4ZjhmO3N0b3Atb3BhY2l0eToxIi8+PC9yYWRpYWxHcmFkaWVudD48L2RlZnM+PC9zdmc+"
            alt="Photo"
            class="absolute inset-0 h-full w-full rounded-lg object-cover grayscale dark:brightness-20"
        />
    </twig:AspectRatio>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col items-center gap-4">
    <figure class="w-full max-w-sm" dir="rtl">
        <twig:AspectRatio ratio="16 / 9" class="rounded-lg bg-muted">
            <img
                src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbDpzcGFjZT0icHJlc2VydmUiIHN0eWxlPSJmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtzdHJva2UtbGluZWpvaW46cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6MiIgdmlld0JveD0iMCAwIDY0IDY0Ij48cGF0aCBkPSJNMCAwaDY0djY0SDB6IiBzdHlsZT0iZmlsbDp1cmwoI2EpIi8+PGRlZnM+PHJhZGlhbEdyYWRpZW50IGlkPSJhIiBjeD0iMCIgY3k9IjAiIHI9IjEiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoNzcgNTggLTU4IDc3IDEgMTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojYjRiNGI0O3N0b3Atb3BhY2l0eToxIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojOGY4ZjhmO3N0b3Atb3BhY2l0eToxIi8+PC9yYWRpYWxHcmFkaWVudD48L2RlZnM+PC9zdmc+"
                alt="Photo"
                class="absolute inset-0 h-full w-full rounded-lg object-cover grayscale dark:brightness-20"
            />
        </twig:AspectRatio>
        <figcaption class="mt-2 text-center text-sm text-muted-foreground">منظر طبيعي جميل</figcaption>
    </figure>
    <figure class="w-full max-w-sm" dir="rtl">
        <twig:AspectRatio ratio="16 / 9" class="rounded-lg bg-muted">
            <img
                src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbDpzcGFjZT0icHJlc2VydmUiIHN0eWxlPSJmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtzdHJva2UtbGluZWpvaW46cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6MiIgdmlld0JveD0iMCAwIDY0IDY0Ij48cGF0aCBkPSJNMCAwaDY0djY0SDB6IiBzdHlsZT0iZmlsbDp1cmwoI2EpIi8+PGRlZnM+PHJhZGlhbEdyYWRpZW50IGlkPSJhIiBjeD0iMCIgY3k9IjAiIHI9IjEiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoNzcgNTggLTU4IDc3IDEgMTEpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojYjRiNGI0O3N0b3Atb3BhY2l0eToxIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojOGY4ZjhmO3N0b3Atb3BhY2l0eToxIi8+PC9yYWRpYWxHcmFkaWVudD48L2RlZnM+PC9zdmc+"
                alt="Photo"
                class="absolute inset-0 h-full w-full rounded-lg object-cover grayscale dark:brightness-20"
            />
        </twig:AspectRatio>
        <figcaption class="mt-2 text-center text-sm text-muted-foreground">מראה נהדר</figcaption>
    </figure>
</div>
```

## API Reference

::: api-reference
