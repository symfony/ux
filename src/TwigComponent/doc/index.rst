Twig Components
===============

Twig components give you the power to bind an object to a template,
making it easier to render and re-use small template "units" - like an
"alert", markup for a modal, or a category sidebar:

Every component consists of (1) a class::

    // src/Components/Alert.php
    namespace App\Components;

    use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

    #[AsTwigComponent]
    class Alert
    {
        public string $type = 'success';
        public string $message;
    }

And (2) a corresponding template:

.. code-block:: html+twig

    {# templates/components/Alert.html.twig #}
    <div class="alert alert-{{ type }}">
        {{ message }}
    </div>

Done! Now render it wherever you want:

.. code-block:: twig

    {{ component('Alert', { message: 'Hello Twig Components!' }) }}

Enjoy your new component!

.. image:: images/alert-example.png
   :alt: Example of the Alert Component

   Example of the Alert Component

This brings the familiar "component" system from client-side frameworks
into Symfony. Combine this with `Live Components`_, to create
an interactive frontend with automatic, Ajax-powered rendering.

Want a demo? Check out https://ux.symfony.com/twig-component#demo

Installation
------------

Let's get this thing installed! Run:

.. code-block:: terminal

    $ composer require symfony/ux-twig-component

That's it! We're ready to go!

Creating a Basic Component
--------------------------

Let's create a reusable "alert" element that we can use to show success
or error messages across our site. Step 1 is always to create a
component that has an ``AsTwigComponent`` class attribute. Let's start
as simple as possible::

    // src/Components/Alert.php
    namespace App\Components;

    use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

    #[AsTwigComponent]
    class Alert
    {
    }

Step 2 is to create a template for this component. By default, templates
live in ``templates/components/{component_name}.html.twig``, where
``{component_name}`` matches the class name of the component:

.. code-block:: html+twig

    {# templates/components/Alert.html.twig #}
    <div class="alert alert-success">
        Success! You've created a Twig component!
    </div>

This isn't very interesting yet… since the message is hardcoded into the
template. But it's enough! Celebrate by rendering your component from
any other Twig template:

.. code-block:: twig

    {{ component('Alert') }}

Done! You've just rendered your first Twig Component! Take a moment to
fist pump - then come back!

Naming Your Component
---------------------

.. versionadded:: 2.8

    Before 2.8, passing a name to ``AsTwigComponent`` was required. Now, the
    name is optional and defaults to the class name.

The name of your component is the class name by default. But you can
customize it by passing an argument to ``AsTwigComponent``::

    #[AsTwigComponent('alert')]
    class Alert
    {
    }

Passing Data into your Component
--------------------------------

Good start: but this isn't very interesting yet! To make our ``Alert``
component reusable, we need to make the message and type
(e.g. ``success``, ``danger``, etc) configurable. To do that, create a
public property for each:

.. code-block:: diff

      // src/Components/Alert.php
      // ...

      #[AsTwigComponent]
      class Alert
      {
    +     public string $message;

    +     public string $type = 'success';

          // ...
      }

In the template, the ``Alert`` instance is available via
the ``this`` variable and public properties are available directly.
Use them to render the two new properties:

.. versionadded:: 2.1

    The ability to reference local variables in the template (e.g. ``message``) was added in TwigComponents 2.1.
    Previously, all data needed to be referenced through ``this`` (e.g. ``this.message``).

.. code-block:: html+twig

    <div class="alert alert-{{ type }}">
        {{ message }}

        {# Same as above, but using "this", which is the component object #}
        {{ this.message }}
    </div>

How can we populate the ``message`` and ``type`` properties? By passing
them as a 2nd argument to the ``component()`` function when rendering:

.. code-block:: twig

    {{ component('Alert', { message: 'Successfully created!' }) }}

    {{ component('Alert', {
        type: 'danger',
        message: 'Danger Will Robinson!'
    }) }}

Behind the scenes, a new ``Alert`` will be instantiated and the
``message`` key (and ``type`` if passed) will be set onto the
``$message`` property of the object. Then, the component is rendered! If
a property has a setter method (e.g. ``setMessage()``), that will be
called instead of setting the property directly.

.. note::

    You can disable exposing public properties for a component. When disabled,
    ``this.property`` must be used::

        #[AsTwigComponent(exposePublicProps: false)]
        class Alert
        {
            // ...
        }

Customize the Twig Template
~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can customize the template used to render the component by passing it
as the second argument to the ``AsTwigComponent`` attribute:

.. code-block:: diff

      // src/Components/Alert.php
      // ...

    - #[AsTwigComponent]
    + #[AsTwigComponent(template: 'my/custom/template.html.twig')]
      class Alert
      {
          // ...
      }

Twig Template Namespaces
~~~~~~~~~~~~~~~~~~~~~~~~

You can use a ``:`` in your component's name to indicate a namespace. The default
template will replace the ``:`` with ``/``. For example, a component with the name
``form:input`` will look for a template in ``templates/components/form/input.html.twig``.

The mount() Method
~~~~~~~~~~~~~~~~~~

If, for some reason, you don't want an option to the ``component()``
function to be set directly onto a property, you can, instead, create a
``mount()`` method in your component::

    // src/Components/Alert.php
    // ...

    #[AsTwigComponent]
    class Alert
    {
        public string $message;
        public string $type = 'success';

        public function mount(bool $isSuccess = true)
        {
            $this->type = $isSuccess ? 'success' : 'danger';
        }

        // ...
    }

The ``mount()`` method is called just one time immediately after your
component is instantiated. Because the method has an ``$isSuccess``
argument, we can pass an ``isSuccess`` option when rendering the
component:

.. code-block:: twig

    {{ component('alert', {
        isSuccess: false,
        message: 'Danger Will Robinson!'
    }) }}

If an option name matches an argument name in ``mount()``, the option is
passed as that argument and the component system will *not* try to set
it directly on a property.

PreMount Hook
~~~~~~~~~~~~~

If you need to modify/validate data before it's *mounted* on the
component use a ``PreMount`` hook::

    // src/Components/Alert.php
    use Symfony\UX\TwigComponent\Attribute\PreMount;
    // ...

    #[AsTwigComponent]
    class Alert
    {
        public string $message;
        public string $type = 'success';

        #[PreMount]
        public function preMount(array $data): array
        {
            // validate data
            $resolver = new OptionsResolver();
            $resolver->setDefaults(['type' => 'success']);
            $resolver->setAllowedValues('type', ['success', 'danger']);
            $resolver->setRequired('message');
            $resolver->setAllowedTypes('message', 'string');

            return $resolver->resolve($data);
        }

        // ...
    }

.. note::

    If your component has multiple ``PreMount`` hooks, and you'd like to control
    the order in which they're called, use the ``priority`` attribute parameter:
    ``PreMount(priority: 10)`` (higher called earlier).

PostMount Hook
~~~~~~~~~~~~~~

.. versionadded:: 2.1

    The ``PostMount`` hook was added in TwigComponents 2.1.

After a component is instantiated and its data mounted, you can run extra
code via the ``PostMount`` hook::

    // src/Components/Alert.php
    use Symfony\UX\TwigComponent\Attribute\PostMount;
    // ...

    #[AsTwigComponent]
    class Alert
    {
        #[PostMount]
        public function postMount(): array
        {
            if (str_contains($this->message, 'danger')) {
                $this->type = 'danger';
            }
        }
        // ...
    }

A ``PostMount`` method can also receive an array ``$data`` argument, which
will contain any props passed to the component that have *not* yet been processed,
i.e. because they don't correspond to any property. You can handle these props,
remove them from the ``$data`` and return the array::

    // src/Components/Alert.php
    #[AsTwigComponent]
    class Alert
    {
        public string $message;
        public string $type = 'success';

        #[PostMount]
        public function processAutoChooseType(array $data): array
        {
            if ($data['autoChooseType'] ?? false) {
                if (str_contains($this->message, 'danger')) {
                    $this->type = 'danger';
                }

                // remove the autoChooseType prop from the data array
                unset($data['autoChooseType']);
            }

            // any remaining data will become attributes on the component
            return $data;
        }
        // ...
    }

.. note::

    If your component has multiple ``PostMount`` hooks, and you'd like to control
    the order in which they're called, use the ``priority`` attribute parameter:
    ``PostMount(priority: 10)`` (higher called earlier).

ExposeInTemplate Attribute
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.1

    The ``ExposeInTemplate`` attribute was added in TwigComponents 2.1.

.. versionadded:: 2.3

    The ``ExposeInTemplate`` attribute can now be used on public methods.

All public component properties are available directly in your component
template. You can use the ``ExposeInTemplate`` attribute to expose
private/protected properties and public methods directly in a component
template (``someProp`` vs ``this.someProp``, ``someMethod`` vs ``this.someMethod``).
Properties must be *accessible* (have a getter). Methods *cannot have*
required parameters::

    // ...
    use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

    #[AsTwigComponent]
    class Alert
    {
        #[ExposeInTemplate]
        private string $message; // available as `{{ message }}` in the template

        #[ExposeInTemplate('alert_type')]
        private string $type = 'success'; // available as `{{ alert_type }}` in the template

        #[ExposeInTemplate(name: 'ico', getter: 'fetchIcon')]
        private string $icon = 'ico-warning'; // available as `{{ ico }}` in the template using `fetchIcon()` as the getter

        /**
         * Required to access $this->message
         */
        public function getMessage(): string
        {
            return $this->message;
        }

        /**
         * Required to access $this->type
         */
        public function getType(): string
        {
            return $this->type;
        }

        /**
         * Required to access $this->icon
         */
        public function fetchIcon(): string
        {
            return $this->icon;
        }

        #[ExposeInTemplate]
        public function getActions(): array // available as `{{ actions }}` in the template
        {
            // ...
        }

        #[ExposeInTemplate('dismissable')]
        public function canBeDismissed(): bool // available as `{{ dismissable }}` in the template
        {
            // ...
        }

        // ...
    }

.. note::

    When using ``ExposeInTemplate`` on a method the value is fetched eagerly
    before rendering.

Fetching Services
-----------------

Let's create a more complex example: a "featured products" component.
You *could* choose to pass an array of Product objects into the
``component()`` function and set those on a ``$products`` property. But
instead, let's allow the component to do the work of executing the
query.

How? Components are *services*, which means autowiring works like
normal. This example assumes you have a ``Product`` Doctrine entity and
``ProductRepository``::

    // src/Components/FeaturedProducts.php
    namespace App\Components;

    use App\Repository\ProductRepository;
    use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

    #[AsTwigComponent]
    class FeaturedProducts
    {
        private ProductRepository $productRepository;

        public function __construct(ProductRepository $productRepository)
        {
            $this->productRepository = $productRepository;
        }

        public function getProducts(): array
        {
            // an example method that returns an array of Products
            return $this->productRepository->findFeatured();
        }
    }

In the template, the ``getProducts()`` method can be accessed via
``this.products``:

.. code-block:: html+twig

    {# templates/components/FeaturedProducts.html.twig #}
    <div>
        <h3>Featured Products</h3>

        {% for product in this.products %}
            ...
        {% endfor %}
    </div>

And because this component doesn't have any public properties that we
need to populate, you can render it with:

.. code-block:: twig

    {{ component('FeaturedProducts') }}

.. note::

    Because components are services, normal dependency injection can be used.
    However, each component service is registered with ``shared: false``. That
    means that you can safely render the same component multiple times with
    different data because each component will be an independent instance.

Computed Properties
~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.1

    Computed Properties were added in TwigComponents 2.1.

In the previous example, instead of querying for the featured products
immediately (e.g. in ``__construct()``), we created a ``getProducts()``
method and called that from the template via ``this.products``.

This was done because, as a general rule, you should make your
components as *lazy* as possible and store only the information you need
on its properties (this also helps if you convert your component to a
`live component`_ later). With this setup, the query is only executed if and
when the ``getProducts()`` method is actually called. This is very similar
to the idea of "computed properties" in frameworks like `Vue`_.

But there's no magic with the ``getProducts()`` method: if you call
``this.products`` multiple times in your template, the query would be
executed multiple times.

To make your ``getProducts()`` method act like a true computed property,
call ``computed.products`` in your template. ``computed`` is a proxy
that wraps your component and caches the return of methods. If they
are called additional times, the cached value is used.

.. code-block:: html+twig

    {# templates/components/FeaturedProducts.html.twig #}
    <div>
        <h3>Featured Products</h3>

        {% for product in computed.products %}
            ...
        {% endfor %}

        ...
        {% for product in computed.products %} {# use cache, does not result in a second query #}
            ...
        {% endfor %}
    </div>

.. note::

    Computed methods only work for component methods with no required
    arguments.

Component Attributes
--------------------

.. versionadded:: 2.1

    Component attributes were added in TwigComponents 2.1.

A common need for components is to configure/render attributes for the
root node. Attributes are any data passed to ``component()`` that cannot be
mounted on the component itself. This extra data is added to a
``ComponentAttributes`` that is available as ``attributes`` in your
component's template.

To use, in your component's template, render the ``attributes`` variable in
the root element:

.. code-block:: html+twig

    {# templates/components/MyComponent.html.twig #}
    <div{{ attributes }}>
      My Component!
    </div>

When rendering the component, you can pass an array of html attributes to add:

.. code-block:: html+twig

    {{ component('MyComponent', { class: 'foo', style: 'color:red' }) }}

    {# renders as: #}
    <div class="foo" style="color:red">
      My Component!
    </div>

Set an attribute's value to ``true`` to render just the attribute name:

.. code-block:: html+twig

    {# templates/components/MyComponent.html.twig #}
    <input{{ attributes }}/>

    {# render component #}
    {{ component('MyComponent', { type: 'text', value: '', autofocus: true }) }}

    {# renders as: #}
    <input type="text" value="" autofocus/>

Set an attribute's value to ``false`` to exclude the attribute:

.. code-block:: html+twig

    {# templates/components/MyComponent.html.twig #}
    <input{{ attributes }}/>

    {# render component #}
    {{ component('MyComponent', { type: 'text', value: '', autofocus: false }) }}

    {# renders as: #}
    <input type="text" value=""/>

To add a custom `Stimulus controller`_ to your root component element:

.. versionadded:: 2.9

    The ability to use ``stimulus_controller()`` with ``attributes.defaults()``
    was added in TwigComponents 2.9 and requires ``symfony/stimulus-bundle``.
    Previously, ``stimulus_controller()`` was passed to an ``attributes.add()``
    method.

.. code-block:: html+twig

    <div {{ attributes.defaults(stimulus_controller('my-controller', { someValue: 'foo' })) }}>

.. note::

    You can adjust the attributes variable exposed in your template::

        #[AsTwigComponent(attributesVar: '_attributes')]
        class Alert
        {
            // ...
        }

Defaults & Merging
~~~~~~~~~~~~~~~~~~

In your component template, you can set defaults that are merged with
passed attributes. The passed attributes override the default with
the exception of *class*. For ``class``, the defaults are prepended:

.. code-block:: html+twig

    {# templates/components/MyComponent.html.twig #}
    <button{{ attributes.defaults({ class: 'bar', type: 'button' }) }}>Save</button>

    {# render component #}
    {{ component('MyComponent', { style: 'color:red' }) }}

    {# renders as: #}
    <button class="bar" type="button" style="color:red">Save</button>

    {# render component #}
    {{ component('MyComponent', { class: 'foo', type: 'submit' }) }}

    {# renders as: #}
    <button class="bar foo" type="submit">Save</button>

Only
~~~~

Extract specific attributes and discard the rest:

.. code-block:: html+twig

    {# templates/components/MyComponent.html.twig #}
    <div{{ attributes.only('class') }}>
      My Component!
    </div>

    {# render component #}
    {{ component('MyComponent', { class: 'foo', style: 'color:red' }) }}

    {# renders as: #}
    <div class="foo">
      My Component!
    </div>

Without
~~~~~~~

Exclude specific attributes:

.. code-block:: html+twig

    {# templates/components/MyComponent.html.twig #}
    <div{{ attributes.without('class') }}>
      My Component!
    </div>

    {# render component #}
    {{ component('MyComponent', { class: 'foo', style: 'color:red' }) }}

    {# renders as: #}
    <div style="color:red">
      My Component!
    </div>

PreRenderEvent
--------------

.. versionadded:: 2.1

    The ``PreRenderEvent`` was added in TwigComponents 2.1.

Subscribing to the ``PreRenderEvent`` gives the ability to modify
the twig template and twig variables before components are rendered::

    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\UX\TwigComponent\Event\PreRenderEvent;

    class HookIntoTwigPreRenderSubscriber implements EventSubscriberInterface
    {
        public function onPreRender(PreRenderEvent $event): void
        {
            $event->getComponent(); // the component object
            $event->getTemplate(); // the twig template name that will be rendered
            $event->getVariables(); // the variables that will be available in the template

            $event->setTemplate('some_other_template.html.twig'); // change the template used

            // manipulate the variables:
            $variables = $event->getVariables();
            $variables['custom'] = 'value';

            $event->setVariables($variables); // {{ custom }} will be available in your template
        }

        public static function getSubscribedEvents(): array
        {
            return [PreRenderEvent::class => 'onPreRender'];
        }
    }

PostRenderEvent
---------------

.. versionadded:: 2.5

    The ``PostRenderEvent`` was added in TwigComponents 2.5.

The ``PostRenderEvent`` is called after a component has finished
rendering and contains the ``MountedComponent`` that was just
rendered.

PreCreateForRenderEvent
-----------------------

.. versionadded:: 2.5

    The ``PreCreateForRenderEvent`` was added in TwigComponents 2.5.

Subscribing to the ``PreCreateForRenderEvent`` gives the ability to be
notified before a component object is created or hydrated, at the
very start of the rendering process. You have access to the component
name, input props and can interrupt the process by setting HTML. This
event is not triggered during a re-render.

PreMountEvent and PostMountEvent
--------------------------------

.. versionadded:: 2.1

    The ``PreMountEvent`` and ``PostMountEvent`` ere added in TwigComponents 2.5.

To run code just before or after a component's data is mounted, you can
listen to ``PreMountEvent`` or ``PostMountEvent``.

Nested Components
-----------------

It's totally possible to nest one component into another. When you do
this, there's nothing special to know: both components render
independently. If you're using `Live Components`_, then there
*are* some guidelines related to how the re-rendering of parent and
child components works. Read `Live Nested Components`_.

.. _embedded-components:

Passing Blocks to Components
----------------------------

.. tip::

    The `Component HTML Syntax`_ allows you to pass blocks to components in an
    even more readable way.

You can write your component's Twig template with blocks that can be overridden
when rendering using the ``{% component %}`` syntax. These blocks can be thought of as
*slots* which you may be familiar with from Vue. The ``component`` tag is very
similar to Twig's native `embed tag`_.

Consider a data table component. You pass it headers and rows but can expose
blocks for the cells and an optional footer:

.. code-block:: html+twig

    {# templates/components/DataTable.html.twig #}
    <div{{ attributes.defaults({class: 'data-table'}) }}>
        <table>
            <thead>
                <tr>
                    {% for header in this.headers %}
                        <th class="{% block th_class %}data-table-header{% endblock %}">
                            {{ header }}
                        </th>
                    {% endfor %}
                </tr>
            </thead>
            <tbody>
                {% for row in this.data %}
                    <tr>
                        {% for cell in row %}
                            <td class="{% block td_class %}data-table-cell{% endblock %}">
                                {{ cell }}
                            </td>
                        {% endfor %}
                    </tr>
                {% endfor %}
            </tbody>
        </table>
        {% block footer %}{% endblock %}
    </div>

When rendering, you can override the ``th_class``, ``td_class``, and ``footer`` blocks.
The ``with`` data is what's mounted on the component object.

.. code-block:: html+twig

    {# templates/some_page.html.twig #}
    {% component DataTable with {headers: ['key', 'value'], data: [[1, 2], [3, 4]]} %}
        {% block th_class %}{{ parent() }} text-bold{% endblock %}

        {% block td_class %}{{ parent() }} text-italic{% endblock %}

        {% block footer %}
            <div class="data-table-footer">
                My footer
            </div>
        {% endblock %}
    {% endcomponent %}

.. versionadded:: 2.11

    The ``{% component %}`` syntax can also be used with LiveComponents since 2.11.
    However, there are some caveats related to the context between parent and child
    components during re-rending. Read `Passing Blocks to Live Components`_.

Inheritance & Forwarding "Outer Blocks"
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.10

    The ``outerBlocks`` variable was added in 2.10.

The content inside a ``{% component ... %}`` tag should be viewed as living in
its own, independent template, which extends the component's template. This means that
any blocks that live in the "outer" template are not available inside the ``{% component %}`` tag.
However, a special ``outerBlocks`` variable is added as a way to refer to those blocks:

.. code-block:: html+twig

  {% extends 'base.html.twig' %}

  {% block call_to_action %}<strong>Attention! Free Puppies!</strong>{% endblock %}

  {% block body %}
    {% component Alert %}
      {% block content %}{{ block(outerBlocks.call_to_action) }}{% endblock %}
    {% endcomponent %}
  {% endblock %}

The ``outerBlocks`` variable becomes specially useful with nested components. For example,
imagine we want to create a ``SuccessAlert`` component that's usable like this:

.. code-block:: html+twig

    {# templates/some_page.html.twig #}
    {% component SuccessAlert %}
        {% block content %}We will successfully <em>forward</em> this block content!{% endblock %}
    {% endcomponent %}

But we already have a generic ``Alert`` component, and we want to re-use it:

.. code-block:: html+twig

    {# templates/Alert.html.twig #}
    <div class="alert alert-{{ type }}">
        {% block content %}{% endblock %}
    </div>

To do this, the ``SuccessAlert`` component can grab the ``content`` block that's passed to it
via the ``outerBlocks`` variable and forward it into ``Alert``:

.. code-block:: twig

    {# templates/SuccessAlert.html.twig #}
    {% component Alert with { type: 'success' } %}
        {% block content %}{{ block(outerBlocks.content) }}{% endblock %}
    {% endcomponent %}

Note that to pass a block multiple components down, each component needs to pass it.

Context / Variables Inside of Blocks
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The content inside of the ``{% component ... %}`` should be viewed as living in its own,
independent template, which extends the component's template. This has a few interesting consequences.

First, once you're inside of ``{% component ... %}``, the ``this`` variable represents
the component you're now rendering *and* you have access to all of that component's variables:

.. code-block:: twig

    {# templates/SuccessAlert.html.twig #}
    {{ this.someFunction }} {# this refers to SuccessAlert #}

    {% component Alert with { type: 'success' } %}
        {% block content %}
            {{ this.someFunction }} {# this refers to Alert! #}

            {{ type }} {# references a "type" prop from Alert #}
        {% endblock %}
    {% endcomponent %}

Conveniently, in addition to the variables from the ``Alert`` component, you still have
access to whatever variables are available in the original template:

.. code-block:: twig

    {# templates/SuccessAlert.html.twig #}
    {% set name = 'Fabien' %}
    {% component Alert with { type: 'success' } %}
        {% block content %}
            Hello {{ name }}
        {% endblock %}
    {% endcomponent %}

Note that ALL variables from upper components (e.g. ``SuccessAlert``) are available to lower
components (e.g. ``Alert``). However, because variables are merged, variables with the same name
are overridden by lower components (that's also why ``this`` refers to the embedded, or
"current" component).

The most interesting thing is that the content inside of ``{% component ... %}`` is
executed as if it is "copy-and-pasted" into the block of the target template. This means
you can access variables from the block you're overriding! For example:

.. code-block:: twig

    {# templates/SuccessAlert.html.twig #}
    {% for message in messages %}
        {% block alert_message %}
            A default {{ message }}
        {% endblock %}
    {% endfor %}

When overriding the ``alert_message`` block, you have access to the ``message`` variable:

.. code-block:: twig

    {# templates/some_page.html.twig #}
    {% component SuccessAlert %}
        {% block alert_message %}
            I can override the alert_message block and access the {{ message }} too!
        {% endblock %}
    {% endcomponent %}


.. versionadded:: 2.12

    The ability to refer to the scope of higher components via the ``outerScope`` variable was added in 2.12.

As mentioned before, variables from lower components are merged with those from upper components. When you need
access to some properties or functions from higher components, that can be done via the ``outerScope...`` variable:

.. code-block:: twig

    {# templates/SuccessAlert.html.twig #}
    {% set name = 'Fabien' %}
    {% set message = 'Hello' %}
    {% component Alert with { type: 'success', name: 'Bart' } %}
        Hello {{ name }} {# Hello Bart #}

        {{ message }} {{ outerScope.name }} {# Hello Fabien #}

        {{ outerScope.this.someFunction }} {# this refers to SuccessAlert #}

        {{ outerScope.this.someProp }} {# references a "someProp" prop from SuccessAlert #}
    {% endcomponent %}

You can keep referring to components higher up as well. Just add another ``outerScope``.
Remember though that the ``outerScope`` reference only starts once you're INSIDE the (embedded) component.

.. code-block:: twig

    {# templates/FancyProfileCard.html.twig #}
    {% component Card %}
        {% block header %}
            {% component Alert with { message: outerScope.this.someProp } %} {# not yet INSIDE the Alert template #}
                {% block content %}
                    {{ message }} {# same value as below, indirectly refers to FancyProfileCard::someProp #}
                    {{ outerScope.outerScope.this.someProp }} {# directly refers to FancyProfileCard::someProp #}
                {% endblock %}
            {% endcomponent %}
        {% endblock %}
    {% endcomponent %}

Component HTML Syntax
---------------------

.. versionadded:: 2.8

    This syntax was been introduced in 2.8 and is still experimental: it may change in the future.

Twig Components come with an HTML-like syntax to ease the readability of your template:

.. code-block:: html+twig

    <twig:Alert></twig:Alert>
    // or use a self-closing tag
    <twig:Alert />

Passing Props as HTML Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Passing props is done with HTML attributes. For example if you have this component::

    #[AsTwigComponent]
    class Alert
    {
        public string $message = '';
        public bool $withActions = false;
        public string $type = 'success';
    }

You can pass the ``message``, ``withActions`` or ``type`` props as attributes:

.. code-block:: html+twig

    // withActions will be set to true
    <twig:Alert type="info" message="hello!" withActions />

To pass in a dynamic value, prefix the attribute with ``:`` or use the
normal ``{{ }}`` syntax:

.. code-block:: html+twig

    <twig:Alert message="hello!" :user="user.id" />

    // equal to
    <twig:Alert message="hello!" user="{{ user.id }}" />

    // and pass object, or table, or anything you imagine
    <twig:Alert :foo="['col' => ['foo', 'oof']]" />

To forward attributes to another component, use `{{...}}` spread operator syntax.
This requires Twig 3.7.0 or higher:

.. code-block:: html+twig

    <twig:Alert{{ ...myAttributes }} />

.. _passing-blocks:

Passing Content (Blocks) to Components
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can also pass content directly to your component:

.. code-block:: html+twig

    <twig:Alert type="success">
        <div>Congratulations! You've won a free puppy!</div>
    </twig:Alert>

In your component template, this becomes a block named ``content``:

.. code-block:: html+twig

    <div class="alert alert-{{ type }}">
        {% block content %}
            // the content will appear in here
        {% endblock %}
     </div>

In addition to the default block, you can also add named blocks:

.. code-block:: html+twig

    <twig:Alert type="success">
        <div>Congrats on winning a free puppy!</div>

        <twig:block name="footer">
            <button class="btn btn-primary">Claim your prize</button>
        </twig:block>
    </twig:Alert>

And in your component template you can access your embedded block

.. code-block:: html+twig

    <div class="alert alert-{{ type }}">
        {% block content %}{% endblock %}
        {% block footer %}{% endblock %}
     </div>

Anonymous Components
--------------------

Sometimes a component is simple enough that it doesn't have any complex logic or injected services.
In this case, you can skip the class and only create the template. The component name is determined
by the location of the template (see `Twig Template Namespaces`_):

.. code-block:: html+twig

    {# templates/components/Button/Primary.html.twig #}
    <button {{ attributes.defaults({ class: 'primary' }) }}>
        {% block content %}{% endblock %}
    </button>

Then use your component with ``:`` to navigate through sub-directories (if there are any):

.. code-block:: html+twig

    {# index.html.twig #}
    ...
    <div>
       <twig:Button:Primary>Click Me!</twig:Button:Primary>
    </div>

    {# renders as: #}
    <button class="primary">Click Me!</button>

Like normal, you can pass extra attributes that will be rendered on the element:

.. code-block:: html+twig

    {# index.html.twig #}
    ...
    <div>
       <twig:Button:Primary type="button" name="foo">Click Me!</twig:Button:Primary>
    </div>

    {# renders as: #}
    <button class="primary" type="button" name="foo">Click Me!</button>

You can also pass a variable (prop) into your template:

.. code-block:: html+twig

    {# index.html.twig #}
    ...
    <div>
        <twig:Button icon="fa-plus" type="primary" role="button">Click Me!</twig:Button>
    </div>

To tell the system that ``icon`` and ``type`` are props and not attributes, use the ``{% props %}`` tag at the top of your template.

.. code-block:: html+twig

    {# templates/components/Button.html.twig #}
    {% props icon = null, type = 'primary' %}

    <button {{ attributes.defaults({ class: 'btn btn-'~type }) }}>
        {% block content %}{% endblock %}
        {% if icon %}
            <span class="fa-solid fa-{{ icon }}"></span>
        {% endif %}
    </button>

Debugging Components
--------------------

As your application grows, you'll eventually have a lot of components.
This command will help you to debug some components issues.
First, the debug:twig-component command lists all your application components
who live in ``templates/components``:

.. code-block:: terminal

    $ php bin/console debug:twig-component

    +---------------+-----------------------------+------------------------------------+------+
    | Component     | Class                       | Template                           | Live |
    +---------------+-----------------------------+------------------------------------+------+
    | Coucou        | App\Components\Alert        | components/Coucou.html.twig        |      |
    | RandomNumber  | App\Components\RandomNumber | components/RandomNumber.html.twig  | X    |
    | Test          | App\Components\foo\Test     | components/foo/Test.html.twig      |      |
    | Button        | Anonymous component         | components/Button.html.twig        |      |
    | foo:Anonymous | Anonymous component         | components/foo/Anonymous.html.twig |      |
    +---------------+-----------------------------+------------------------------------+------+

.. tip::

    The Live column show you which component is a LiveComponent.

If you have some components who doesn't live in ``templates/components``,
but in ``templates/bar`` for example you can pass an option:

.. code-block:: terminal

    $ php bin/console debug:twig-component --dir=bar

    +----------------+-------------------------------+------------------------------+------+
    | Component      | Class                         | Template                     | Live |
    +----------------+-------------------------------+------------------------------+------+
    | OtherDirectory | App\Components\OtherDirectory | bar/OtherDirectory.html.twig |      |
    +----------------+-------------------------------+------------------------------+------+

And the name of some component to this argument to print the
component details:

.. code-block:: terminal

    $ php bin/console debug:twig-component RandomNumber

    +---------------------------------------------------+-----------------------------------+
    | Property                                          | Value                             |
    +---------------------------------------------------+-----------------------------------+
    | Component                                         | RandomNumber                      |
    | Live                                              | X                                 |
    | Class                                             | App\Components\RandomNumber       |
    | Template                                          | components/RandomNumber.html.twig |
    | Properties (type / name / default value if exist) | string $name = toto               |
    |                                                   | string $type = test               |
    | Live Properties                                   | int $max = 1000                   |
    |                                                   | int $min = 10                     |
    +---------------------------------------------------+-----------------------------------+

Test Helpers
------------

You can test how your component is mounted and rendered using the
``InteractsWithTwigComponents`` trait::

    use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
    use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

    class MyComponentTest extends KernelTestCase
    {
        use InteractsWithTwigComponents;

        public function testComponentMount(): void
        {
            $component = $this->mountTwigComponent(
                name: 'MyComponent', // can also use FQCN (MyComponent::class)
                data: ['foo' => 'bar'],
            );

            $this->assertInstanceOf(MyComponent::class, $component);
            $this->assertSame('bar', $component->foo);
        }

        public function testComponentRenders(): void
        {
            $rendered = $this->renderTwigComponent(
                name: 'MyComponent', // can also use FQCN (MyComponent::class)
                data: ['foo' => 'bar'],
            );

            $this->assertStringContainsString('bar', $rendered);

            // use the crawler
            $this->assertCount(5, $rendered->crawler('ul li'));
        }

        public function testEmbeddedComponentRenders(): void
        {
            $rendered = $this->renderTwigComponent(
                name: 'MyComponent', // can also use FQCN (MyComponent::class)
                data: ['foo' => 'bar'],
                content: '<div>My content</div>', // "content" (default) block
                blocks: [
                    'header' => '<div>My header</div>',
                    'menu' => $this->renderTwigComponent('Menu'), // can embed other components
                ],
            );

            $this->assertStringContainsString('bar', $rendered);
        }
    }

.. note::

    The ``InteractsWithTwigComponents`` trait can only be used in tests that extend
    ``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase``.

Contributing
------------

Interested in contributing? Visit the main source for this repository:
https://github.com/symfony/ux/tree/main/src/TwigComponent.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`Live Components`: https://symfony.com/bundles/ux-live-component/current/index.html
.. _`live component`: https://symfony.com/bundles/ux-live-component/current/index.html
.. _`Vue`: https://v3.vuejs.org/guide/computed.html
.. _`Live Nested Components`: https://symfony.com/bundles/ux-live-component/current/index.html#nested-components
.. _`Passing Blocks to Live Components`: https://symfony.com/bundles/ux-live-component/current/index.html#passing-blocks
.. _`embed tag`: https://twig.symfony.com/doc/3.x/tags/embed.html
.. _`Stimulus controller`: https://symfony.com/bundles/StimulusBundle/current/index.html
