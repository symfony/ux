<?php

namespace App\Service;

use App\Model\LiveDemo;

class LiveDemoRepository
{
    /**
     * @return array<LiveDemo>
     */
    public function findAll(): array
    {
        return [
            new LiveDemo(
                'auto-validating-form',
                name: 'Auto-Validating Form',
                description: 'Create a form that validates each field in-real-time as the user enters data!',
                route: 'app_demo_live_component_auto_validating_form',
                longDescription: <<<EOF
Enter a bad email or leave the password empty, and see how the
form validates in real time!
<br>
This renders a normal Symfony form but with extras added on top,
all generated from Symfony & Twig.
EOF
            ),
            new LiveDemo(
                'form-collection-type',
                name: 'Embedded CollectionType Form',
                description: 'Create embedded forms with functional "add" and "remove" buttons all in Twig.',
                route: 'app_demo_live_component_form_collection_type',
                longDescription: <<<EOF
Unlock the potential of Symfony's <a href="https://symfony.com/doc/current/reference/forms/types/collection.html"><code>CollectionType</code></a> while
writing zero JavaScript.
<br>
This demo shows off adding and removing items entirely in PHP & Twig.
EOF
            ),
            new LiveDemo(
                'dependent-form-fields',
                name: 'Dependent Form Fields',
                description: 'After selecting the first field, automatically reload the options for a second field.',
                route: 'app_demo_live_component_dependent_form_fields',
                longDescription: <<<EOF
Say goodbye to the hassle of dependent form fields with Live Components.
<br>
Whip up a <a href="https://symfony.com/doc/current/form/dynamic_form_modification.html#form-events-submitted-data">form with dynamic events</a>and then head out for a well-deserved lunch.
EOF
            ),
            new LiveDemo(
                'voting',
                name: 'Up & Down Voting',
                description: 'Save up & down votes live in pure Twig & PHP.',
                route: 'app_demo_live_component_voting',
                longDescription: <<<EOF
With each row as its own component, it's easy to add up & down voting + keep track of which items have been voted on.
<br>
This uses a <a href="https://symfony.com/bundles/ux-live-component/current/index.html#actions">LiveAction</a> to save everything with Ajax.
EOF
            ),
            new LiveDemo(
                'inline-edit',
                name: 'Inline Editing',
                description: 'Activate an inline editing form with real-time validation.',
                route: 'app_demo_live_component_inline_edit',
                longDescription: <<<EOF
Inline editing? Simple. Use LiveComponents to track if you're in "edit" mode, let
the user update any fields on your entity, and save through a <code>LiveAction</code>.
EOF
            ),
            new LiveDemo(
                'chartjs',
                name: 'Auto-Updating Chart',
                description: 'Render & Update a Chart.js chart in real-time.',
                route: 'app_demo_live_component_chartjs',
                longDescription: <<<EOF
What do you get with Live Components + UX Chart.js + UX Autocomplete?
<br>
An auto-updating chart that you will ❤️.
EOF
            ),
            new LiveDemo(
                'invoice',
                name: 'Invoice Creator',
                description: 'Create an invoice + line items that updates as you type.',
                route: 'app_demo_live_component_invoice',
                longDescription: <<<EOF
Create or edit an `Invoice` entity along with child components for each related `InvoiceItem` entity.
<br>
Children components emit events to communicate to the parent and everything is saved in a `saveInvoice` LiveAction method.
EOF
            ),
            new LiveDemo(
                'product-form',
                name: 'Product Form + Category Modal',
                description: 'Create a Category on the fly - from inside a product form - via a modal.',
                route: 'app_demo_live_component_product_form',
                longDescription: <<<EOF
Open a child modal component to create a new Category.
EOF
            ),
            new LiveDemo(
                'upload',
                name: 'Uploading files',
                description: 'Upload file from your live component through a LiveAction.',
                route: 'app_demo_live_component_upload',
                longDescription: <<<EOF
File uploads are tricky. Submit them to a `#[LiveAction]` with the `files` modifier on `data-live-action` then process them.
EOF
            ),
        ];
    }

    public function find(string $identifier): LiveDemo
    {
        $demos = $this->findAll();
        foreach ($demos as $demo) {
            if ($demo->getIdentifier() === $identifier) {
                return $demo;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown demo "%s"', $identifier));
    }
}
