# Sonner

A Sonner-style stacked toast notification system with auto-dismiss, swipe-to-dismiss, rich colors, and JS or server-render support.

```twig {"preview":true}
<twig:Sonner closeButton="true" expand="true">
    <div class="flex flex-wrap gap-2">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Event has been created"
            data-sonner-description-param="Sunday, December 03, 2023 at 9:00 AM"
        >Default</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="success"
            data-sonner-title-param="Profile updated successfully"
        >Success</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="error"
            data-sonner-title-param="Something went wrong"
            data-sonner-description-param="Please try again later."
        >Error</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="warning"
            data-sonner-title-param="Storage limit approaching"
            data-sonner-description-param="You have used 90% of your storage."
        >Warning</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="info"
            data-sonner-title-param="New update available"
        >Info</twig:Button>
    </div>
</twig:Sonner>
```

## Installation

::: installation

## Usage

```twig
<twig:Sonner>
    <twig:Button
        data-action="click->sonner#fire"
        data-sonner-title-param="Event has been created"
    >
        Show Sonner
    </twig:Button>
</twig:Sonner>
```

## Examples

### Types

```twig {"preview":true}
<twig:Sonner>
    <div class="flex flex-wrap gap-2">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Default notification"
        >Default</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="success"
            data-sonner-title-param="Changes saved"
        >Success</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="error"
            data-sonner-title-param="Failed to save changes"
        >Error</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="warning"
            data-sonner-title-param="Unsaved changes"
        >Warning</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="info"
            data-sonner-title-param="New message received"
        >Info</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="loading"
            data-sonner-title-param="Uploading file…"
            data-sonner-duration-param="0"
        >Loading</twig:Button>
    </div>
</twig:Sonner>
```

### With Description

```twig {"preview":true}
<twig:Sonner>
    <twig:Button variant="outline"
        data-action="click->sonner#fire"
        data-sonner-title-param="Event has been created"
        data-sonner-description-param="Sunday, December 03, 2023 at 9:00 AM"
    >
        Show Sonner
    </twig:Button>
</twig:Sonner>
```

### With Action

```twig {"preview":true}
<twig:Sonner>
    <twig:Button variant="outline"
        data-action="click->sonner#fire"
        data-sonner-title-param="Event has been created"
        data-sonner-action-label-param="Undo"
        data-sonner-action-url-param="#"
    >
        Show Sonner
    </twig:Button>
</twig:Sonner>
```

### Rich Colors

```twig {"preview":true}
<twig:Sonner richColors="true">
    <div class="flex flex-wrap gap-2">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="success"
            data-sonner-title-param="Profile updated successfully"
        >Success</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="error"
            data-sonner-title-param="Something went wrong"
        >Error</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="warning"
            data-sonner-title-param="Storage limit approaching"
        >Warning</twig:Button>
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-type-param="info"
            data-sonner-title-param="New update available"
        >Info</twig:Button>
    </div>
</twig:Sonner>
```

### Position

```twig {"preview":true}
<div class="flex flex-wrap justify-center gap-2">
    <twig:Sonner position="top-left">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Top-left notification"
        >
            Top Left
        </twig:Button>
    </twig:Sonner>
    <twig:Sonner position="top-center">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Top-center notification"
        >
            Top Center
        </twig:Button>
    </twig:Sonner>
    <twig:Sonner position="top-right">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Top-right notification"
        >
            Top Right
        </twig:Button>
    </twig:Sonner>
    <twig:Sonner position="bottom-left">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Bottom-left notification"
        >
            Bottom Left
        </twig:Button>
    </twig:Sonner>
    <twig:Sonner position="bottom-center">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Bottom-center notification"
        >
            Bottom Center
        </twig:Button>
    </twig:Sonner>
    <twig:Sonner position="bottom-right">
        <twig:Button variant="outline"
            data-action="click->sonner#fire"
            data-sonner-title-param="Bottom-right notification"
        >
            Bottom Right
        </twig:Button>
    </twig:Sonner>
</div>
```

### Server Rendered

```twig {"preview":true}
<twig:Sonner closeButton="true">
    <twig:Toast type="success" title="Profile saved" description="Your changes have been applied." duration="0" />
    <twig:Toast type="info" title="Welcome back, Alice!" duration="0" />
</twig:Sonner>
```

## API Reference

::: api-reference
