# Symfony UX Design Tokens

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

A design token is a named design decision. Write it once in a
[DTCG 2025.10](https://www.designtokens.org/tr/2025.10/) file and this bundle hands
the resolved value to CSS, Twig, PHP and your other build tools. Resolution
happens on the server, so a theme per user, per brand or per tenant is a
Resolver input rather than a rebuild.

## Installation

```bash
composer require symfony/ux-design-tokens
```

## Usage

Write the tokens in a DTCG file, `design/brand.tokens.json`. Keep it outside
`assets/`, where AssetMapper would publish it:

```json
{
    "color": {
        "action": {
            "$type": "color",
            "primary": {
                "$value": { "colorSpace": "oklch", "components": [0.62, 0.18, 255] }
            }
        }
    },
    "dimension": {
        "radius": {
            "$type": "dimension",
            "control": {
                "$value": { "value": 0.375, "unit": "rem" }
            }
        }
    }
}
```

Point the bundle at it. The configuration is required: without it,
`ux_token_css()` renders an empty `:root {}` block.

```yaml
# config/packages/ux_design_tokens.yaml
ux_design_tokens:
    paths:
        - '%kernel.project_dir%/design/brand.tokens.json'
```

Render the variables once, in the document head. With TwigBundle enabled:

```twig
{# templates/base.html.twig #}
{{ ux_token_css() }}
```

```html
<style>
    @property --dt-color-action-primary {
        syntax: '\3C color>';
        inherits: true;
        initial-value: oklch(62% 0.18 255);
    }

    :root {
        --dt-color-action-primary: oklch(62% 0.18 255);
        --dt-dimension-radius-control: 0.375rem;
    }
</style>
```

Stylesheets read those variables and never mention a token file. Changing the
source changes the interface without touching CSS. When the sheet is the same
for every visitor, `ux_token_stylesheet()` links it as a versioned AssetMapper
asset instead of inlining it.

Custom properties do not reach everywhere: an email, a PDF, an SVG attribute,
or PHP that needs the value itself. The registry serves the same tokens, typed:

```php
// src/Mailer/BrandMailer.php
namespace App\Mailer;

use Symfony\UX\DesignTokens\TokenRegistryInterface;

final class BrandMailer
{
    public function __construct(private readonly TokenRegistryInterface $tokens)
    {
    }

    public function accent(): string
    {
        // "oklch(62% 0.18 255)"
        return (string) $this->tokens->get('color.action.primary');
    }
}
```

Casting is the output edge. Before it the value keeps its DTCG structure, so a
consumer reads the color space and the components instead of parsing a string.

## What it provides

- DTCG Format, Color and Resolver Module 2025.10 support
- Typed PHP value objects and a lazy, cached `TokenRegistryInterface` service
- Twig functions, generated CSS custom properties and versioned AssetMapper
  assets
- Light and dark color schemes in one stylesheet: the dark block holds only
  the custom properties that change
- Lint, fix, import and export commands, and a debug command
  naming the source file behind every resolved value
- DTCG, CSS, JavaScript, Tailwind CSS v4 and DESIGN.md exports
- Tailwind CSS v4 `@theme` import that lists what DTCG cannot represent
- Executable conformance fixtures derived from the reports

## Interoperability

The reference is the [DTCG 2025.10](https://www.designtokens.org/tr/2025.10/)
specification, in its Format, Color and Resolver modules. Other design tools
and build pipelines read the same files.

## Documentation

Read the [documentation](doc/index.rst) in this repository.

> [!IMPORTANT]
> **This repository is a READ-ONLY sub-tree split**.\
> See https://github.com/symfony/ux to create issues or submit pull requests.

## Resources

- [Documentation](https://symfony.com/bundles/ux-design-tokens/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
