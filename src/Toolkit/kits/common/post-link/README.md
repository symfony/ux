# Post Link

A link submitted as a form, with optional HTTP method spoofing, CSRF protection, and a confirmation prompt.

::: example Demo {"height": "190px", "collapseClass": true}

## Installation

::: installation

## Usage

::: example Usage

## Examples

### Custom Method

Set the `method` prop to submit the form with a spoofed HTTP method. A hidden `_method` field is added so Symfony can route the request to the matching controller.

> [!WARNING]
> Method spoofing only works when HTTP method override is enabled in your Symfony app. Set `framework.http_method_override: true` in `config/packages/framework.yaml`.

::: example Custom Method {"height": "300px", "collapseClass": true}

### With Confirmation

Pass a `confirm` message to prompt the user with a native confirmation dialog before the form is submitted.

::: example With Confirmation {"height": "190px", "collapseClass": true}

### With CSRF Protection

Set `csrfTokenId` to add a hidden CSRF token field, protecting the form against cross-site request forgery.

::: example With CSRF Protection {"height": "300px", "collapseClass": true}

## API Reference

::: api-reference
