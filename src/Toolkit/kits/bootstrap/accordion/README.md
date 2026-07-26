# Accordion

Build vertically collapsing sections powered by Bootstrap's Collapse plugin.

```twig {"preview":true}
<twig:Accordion id="faq-accordion">
    <twig:Accordion:Item id="faq-first" label="What is Symfony UX?" expanded>
        <strong>Symfony UX connects Symfony with modern frontend tools.</strong>
        It provides JavaScript packages, Twig components, and integrations designed to work naturally with Symfony applications.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="faq-second" label="Can I customize these components?">
        Yes. Pass HTML attributes and Bootstrap utility classes directly to the Twig components, or customize Bootstrap through Sass and CSS variables.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="faq-third" label="Does the accordion support multiple open items?">
        Set <code>alwaysOpen</code> on the root accordion to omit Bootstrap's parent constraint.
    </twig:Accordion:Item>
</twig:Accordion>
```

## Installation

::: installation

## Usage

```twig
<twig:Accordion id="account-accordion">
    <twig:Accordion:Item id="account-profile" label="Profile" expanded>
        Update your personal details and contact information.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="account-security" label="Security">
        Review your password and two-factor authentication settings.
    </twig:Accordion:Item>
</twig:Accordion>
```

## Accessibility

Choose a `headingTag` that fits the surrounding document hierarchy. Each heading contains a button with `aria-expanded` and `aria-controls`, and each panel references its heading with `aria-labelledby`.

Bootstrap's transition respects `prefers-reduced-motion`. Keep labels concise and do not hide essential information exclusively inside collapsed sections.

## Examples

Render a conventional accordion where opening one item closes the currently open item.

```twig {"preview":true}
<twig:Accordion id="accordion-example">
    <twig:Accordion:Item id="accordion-item-one" label="Accordion Item #1" expanded>
        <strong>This is the first item's accordion body.</strong>
        It is shown by default until the Collapse plugin updates the appropriate classes and ARIA state.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="accordion-item-two" label="Accordion Item #2">
        <strong>This is the second item's accordion body.</strong>
        It is hidden by default and can contain nearly any HTML content.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="accordion-item-three" label="Accordion Item #3">
        <strong>This is the third item's accordion body.</strong>
        Opening it closes the currently open item.
    </twig:Accordion:Item>
</twig:Accordion>
```

### Flush

Remove outer borders and rounded corners for an edge-to-edge accordion.

```twig {"preview":true}
<twig:Accordion id="accordion-flush-example" flush>
    <twig:Accordion:Item id="flush-item-one" label="Accordion Item #1">
        Placeholder content for this edge-to-edge accordion item.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="flush-item-two" label="Accordion Item #2">
        The flush style removes some borders and rounded corners.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="flush-item-three" label="Accordion Item #3">
        Items retain the same Collapse behavior and accessibility attributes.
    </twig:Accordion:Item>
</twig:Accordion>
```

### Always open

Allow several items to remain expanded by omitting Bootstrap's parent constraint.

```twig {"preview":true}
<twig:Accordion id="accordion-always-open" alwaysOpen>
    <twig:Accordion:Item id="always-open-one" label="Accordion Item #1" expanded>
        This item starts open and remains open when another item is expanded.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="always-open-two" label="Accordion Item #2">
        Multiple items can stay open because no <code>data-bs-parent</code> constraint is rendered.
    </twig:Accordion:Item>
    <twig:Accordion:Item id="always-open-three" label="Accordion Item #3">
        Each button still controls its own panel and synchronizes <code>aria-expanded</code>.
    </twig:Accordion:Item>
</twig:Accordion>
```

## API Reference

::: api-reference
