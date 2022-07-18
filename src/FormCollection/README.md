# UX Form Collection

Symfony UX Form collection is a Symfony bundle providing light UX for collection
in Symfony Forms.

## Installation

UX Form Collection requires PHP 7.2+ and Symfony 4.4+.

Install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-form-collection

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

Also make sure you have at least version 2.0 of [@symfony/stimulus-bridge](https://github.com/symfony/stimulus-bridge)
in your `package.json` file.

## Usage

The most common usage of Form Collection is to use it as a replacement of
the native CollectionType class:

```php
// ...
use Symfony\UX\FormCollection\Form\UXCollectionType;

class BlogFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('comments', UXCollectionType::class, [
            'label' => 'Comments',
            'ux_entry_type' => CommentType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'add_options' => [
                'label' => 'Add comment',
            ],
            'delete_options' => [
                'label' => 'Remove Comment',
            ],
        ]);
    }

    // ...
}
```

You can display it using Twig as you would normally with any form:

```twig
{{ form(form) }}
```

## Theming

### Change position of the entry toolbar

```twig
{%- block ux_collection_entry_widget -%}
    {%- set toolbar -%}
        {{- form_widget(form.toolbar) -}}
    {%- endset -%}

    <div class="mt-4 bg-gray-50 border shadow p-4">
        {{- toolbar -}}
            
        {{- block('form_rows') -}}
        
        <div class="mt-4 flex justify-end flex-wrap">
            {{- toolbar -}}
        </div>
    </div>
{%- endblock -%}
```

### Change entry toolbar

```twig
{%- block ux_collection_entry_toolbar_widget -%}
    <div class="mt-4 flex justify-end flex-wrap">
        {{- block('form_widget') -}}
    </div>
{%- endblock -%}
```

### Change collection toolbar

```twig
{%- block ux_collection_toolbar_widget -%}
    <div class="mt-4 flex justify-center flex-wrap">
        {{- block('form_widget') -}}
    </div>
{%- endblock -%}
```

### Extend the default behavior

Symfony UX Form Collection allows you to extend its default behavior using a custom Stimulus controller:

```js
// mycollection_controller.js

import { Controller } from 'stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('collection:pre-connect', this._onPreConnect);
        this.element.addEventListener('collection:connect', this._onConnect);
        this.element.addEventListener('collection:pre-add', this._onPreAdd);
        this.element.addEventListener('collection:add', this._onAdd);
        this.element.addEventListener('collection:pre-delete', this._onPreDelete);
        this.element.addEventListener('collection:delete', this._onDelete);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('collection:pre-connect', this._onPreConnect);
        this.element.removeEventListener('collection:connect', this._onConnect);
    }

    _onPreConnect(event) {
        // The collection is not yet connected
        console.log(event.detail.allowAdd); // Access to the allow_add option of the form
        console.log(event.detail.allowDelete); // Access to the allow_delete option of the form
    }

    _onConnect(event) {
        // Same as collection:pre-connect event
    }

    _onPreAdd(event) {
        console.log(event.detail.index); // Access to the curent index will be added
        console.log(event.detail.element); // Access to the element will be added
    }

    _onAdd(event) {
        // Same as collection:pre-add event
    }

    _onPreDelete(event) {
        console.log(event.detail.index); // Access to the index will be removed
        console.log(event.detail.element); // Access to the elemnt will be removed
    }

    _onDelete(event) {
        // Same as collection:pre-delete event
    }
}
```

Then in your render call, add your controller as an HTML attribute:

```php
        $builder
            // ...
            ->add('comments', UXCollectionType::class, [
                // ...
                'attr' => [
                    // Change the controller name
                    'data-controller' => 'mycollection'
                ]
            ]);
```
