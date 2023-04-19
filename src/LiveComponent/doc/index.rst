Live Components
===============

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Live components work with the `TwigComponent`_ library
to give you the power to automatically update your Twig components on
the frontend as the user interacts with them. Inspired by
`Livewire`_ and `Phoenix LiveView`_.

A real-time product search component might look like this::

    // src/Components/ProductSearch.php
    namespace App\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    #[AsLiveComponent]
    class ProductSearch
    {
        use DefaultActionTrait;

        #[LiveProp(writable: true)]
        public string $query = '';

        public function __construct(private ProductRepository $productRepository)
        {
        }

        public function getProducts(): array
        {
            // example method that returns an array of Products
            return $this->productRepository->search($this->query);
        }
    }

.. code-block:: html+twig

    {# templates/components/ProductSearch.html.twig #}
    {# for the Live Component to work, there must be a single root element
       (e.g. a <div>) where the attributes are applied to #}
    <div {{ attributes }}>
        <input
            type="search"
            data-model="query"
        >

        <ul>
            {% for product in this.products %}
                <li>{{ product.name }}</li>
            {% endfor %}
        </ul>
    </div>

.. versionadded:: 2.1

    The ability to reference local variables in the template (e.g. ``query``) was added in TwigComponents 2.1.
    Previously, all data needed to be referenced through ``this`` (e.g. ``this.query``).

.. versionadded:: 2.1

    The ability to initialize live component with the ``attributes`` twig variable was added in LiveComponents
    2.1. Previously, the ``init_live_component()`` function was required (this function was removed in 2.1).

Done! Now render it wherever you want:

.. code-block:: twig

    {{ component('ProductSearch') }}

As a user types into the box, the component will automatically re-render
and show the new results!

Want some demos? Check out https://ux.symfony.com/live-component#demo

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Now install the library with:

.. code-block:: terminal

    $ composer require symfony/ux-live-component

    # Don't forget to install the JavaScript dependencies as well and compile
    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

Also make sure you have at least version 3.2 of
``@symfony/stimulus-bridge`` in your ``package.json`` file.

In case your project `localizes its URLs`_ by adding the special
``{_locale}`` parameter to the paths of its route definitions,
you need to do the same with the UX Live Components route definition:

.. code-block:: diff

      // config/routes/ux_live_component.yaml

      live_component:
          resource: '@LiveComponentBundle/config/routes.php'
    -     prefix: /_components
    +     prefix: /{_locale}/_components

That's it! We're ready!

Making your Component "Live"
----------------------------

If you haven't already, check out the `Twig Component`_
documentation to get the basics of Twig components.

Suppose you've already built a basic Twig component::

    // src/Components/RandomNumber.php
    namespace App\Components;

    use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

    #[AsTwigComponent()]
    class RandomNumber
    {
        public function getRandomNumber(): int
        {
            return rand(0, 1000);
        }
    }

.. code-block:: html+twig

    {# templates/components/RandomNumber.html.twig #}
    <div>
        <strong>{{ this.randomNumber }}</strong>
    </div>

To transform this into a "live" component (i.e. one that can be
re-rendered live on the frontend), replace the component's
``AsTwigComponent`` attribute with ``AsLiveComponent`` and add the
``DefaultActionTrait``:

.. code-block:: diff

      // src/Components/RandomNumber.php
    - use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
    + use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    + use Symfony\UX\LiveComponent\DefaultActionTrait;

    - #[AsTwigComponent()]
    + #[AsLiveComponent]
      class RandomNumber
      {
    +     use DefaultActionTrait;
      }

Then, in the template, make sure there is *one* HTML element around your
entire component and use the `attributes variable`_ to initialize
the Stimulus controller:

.. code-block:: diff

    - <div>
    + <div {{ attributes }}>
          <strong>{{ this.randomNumber }}</strong>
      </div>

Your component is now a live component… except that we haven't added
anything that would cause the component to update. Let's start simple,
by adding a button that - when clicked - will re-render the component
and give the user a new random number:

.. code-block:: html+twig

    <div {{ attributes }}>
        <strong>{{ this.randomNumber }}</strong>

        <button
            data-action="live#$render"
        >Generate a new number!</button>
    </div>

That's it! When you click the button, an Ajax call will be made to get a
fresh copy of our component. That HTML will replace the current HTML. In
other words, you just generated a new random number! That's cool, but
let's keep going because… things get cooler.

.. tip::

    Need to do some extra data initialization on your component? Create
    a ``mount()`` method or use the ``PostMount`` hook: `Twig Component mount documentation`_.

LiveProps: Stateful Component Properties
----------------------------------------

Let's make our component more flexible by adding a ``$max`` property::

    // src/Components/RandomNumber.php
    namespace App\Components;

    // ...
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent]
    class RandomNumber
    {
        #[LiveProp]
        public int $max = 1000;

        public function getRandomNumber(): int
        {
            return rand(0, $this->max);
        }

        // ...
    }

With this change, we can control the ``$max`` property when rendering
the component:

.. code-block:: twig

    {{ component('RandomNumber', { max: 500 }) }}

But what's up with the ``LiveProp`` attribute? A property with the
``LiveProp`` attribute becomes a "stateful" property for this component.
In other words, each time we click the "Generate a new number!" button,
when the component re-renders, it will *remember* the original values
for the ``$max`` property and generate a random number between 0 and 500.
If you forgot to add ``LiveProp``, when the component re-rendered,
those two values would *not* be set on the object.

In short: LiveProps are "stateful properties": they will always be set
when rendering. Most properties will be LiveProps, with common
exceptions being properties that hold services (these don't need to be
stateful because they will be autowired each time before the component
is rendered).

LiveProp Data Types
~~~~~~~~~~~~~~~~~~~

LiveProps must be a value that can be sent to JavaScript. Supported values
are scalars (int, float, string, bool, null), arrays (of scalar values), enums,
DateTime objects & Doctrine entity objects.

See :ref:`hydration` for handling more complex data.

Data Binding
------------

One of the best parts of frontend frameworks like React or Vue is
"data binding". If you're not familiar, this is where you "bind"
the value of some HTML element (e.g. an ``<input>``) with a property
on your component object.

For example, could we allow the user to *change* the ``$max``
property and then re-render the component when they do? Definitely! And
*that* is where live components really shine.

Add an input to the template:

.. code-block:: html+twig

    {# templates/components/RandomNumber.html.twig #}
    <div {{ attributes }}>
        <input type="number" data-model="max">

        Generating a number between 9 and {{ max }}
        <strong>{{ this.randomNumber }}</strong>
    </div>

.. versionadded:: 2.5

    Before version 2.5, you needed to also set ``value="{{ max }}"``
    on the ``<input>``. That is now set automatically for all
    "data-model" fields.

The key is the ``data-model`` attribute. Thanks
to that, when the user types, the ``$max`` property on
the component will automatically update!

.. versionadded:: 2.3

    Before version 2.3, you also needed a ``data-action="live#update"``
    attribute. That attribute should now be removed.

How? Live components *listens* to the ``input`` event and
sends an Ajax request to re-render the component with the
new data!

Well, actually, we're missing one step. By default, a ``LiveProp`` is
"read only". For security purposes, a user cannot change the value of a
``LiveProp`` and re-render the component unless you allow it with the
``writable=true`` option:

.. code-block:: diff

      // src/Components/RandomNumber.php
      // ...

      class RandomNumber
      {
          // ...

    -     #[LiveProp]
    +     #[LiveProp(writable: true)]
          public int $max = 1000;

          // ...
      }

Now it works: as you type into the ``max`` box, the
component will re-render with a new random in that range.

Debouncing
~~~~~~~~~~

If the user types 5 characters really quickly, we don't want
to send 5 Ajax requests. Fortunately, live components adds
automatic debouncing: it waits for a 150ms pause between
typing before sending an Ajax request to re-render. This is
built in, so you don't need to think about it. But, you can
delay via the ``debounce`` modifier:

.. code-block:: html+twig

        <input data-model="debounce(100)|max">

Lazy Updating on "change" of a Field
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes, you might want a field to re-render only after the user has
changed an input *and* moved to another field. Browsers dispatch a
``change`` event in this situation. To re-render when this event
happens, use the ``on(change)`` modifier:

.. code-block:: html+twig

    <input data-model="on(change)|max">

.. _deferring-a-re-render-until-later:

Deferring a Re-Render Until Later
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Other times, you might want to update the internal value of a property,
but wait until later to re-render the component (e.g. until a button is
clicked). To do that, use ``norender`` modifier:

.. code-block:: html+twig

    <input data-model="norender|max">

Now, as you type, the ``max`` "model" will be updated in JavaScript, but
it won't, yet, make an Ajax call to re-render the component. Whenever
the next re-render *does* happen, the updated ``max`` value will be
used.

.. _name-attribute-model:

Using name="" instead of data-model
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you're building a form (:ref:`more on forms later <forms>`),
instead of adding ``data-model`` to every field, you can instead
rely on the ``name`` attribute.

.. versionadded:: 2.3

    The ``data-model`` attribute on the ``form`` is required since version 2.3.

To activate this, you must add a ``data-model`` attribute to
the ``<form>`` element:

.. code-block:: html+twig

    <div {{ attributes }}>
        <form data-model="*">
            <input
                name="max"
                value="{{ max }}"
            >

            // ...
        </form>
    </div>

The ``*`` value of ``data-model`` is not necessary, but is
commonly used. You can also use the normal modifiers, like
``data-model="on(change)|*"`` to, for example, only send
model updates for the ``change`` event of each field inside.

Model Updates don't work when External JavaScript Changes a Field
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Suppose you use a JavaScript library that sets the value of a field
*for* you: for example a "date picker" library that hides
the native ``<input data-model="publishAt">`` field and sets it
behind-the-scenes when the user selects a date.

In this case, the model (e.g. ``publishAt``) will probably *not*
update correctly because JavaScript doesn't trigger the normal
``change`` event. To fix this, you'll need to "hook" into the
JavaScript library and set the model directly (or trigger a
``change`` event on the ``data-model`` field). See :ref:`working-in-javascript`.

.. _hydration:

LiveProp for Entities & More Complex Data
-----------------------------------------

``LiveProp`` data must be simple scalar values, with a few exception,
like ``DateTime`` objects, enums & Doctrine entity objects. When ``LiveProp``s
are sent to the frontend, they are "dehydrated". When Ajax requests are sent
to the frontend, the dehydrated data is then "hydrated" back into the original.
Doctrine entity objects are a special case for ``LiveProp``::

    use App\Entity\Post;

    #[AsLiveComponent]
    class EditPost
    {
        #[LiveProp]
        public Post $post;
    }

If the ``Post`` object is persisted, its dehydrated to the entity's ``id`` and then
hydrated back by querying the database. If the object is unpersisted, it's dehydrated
to an empty array, then hydrated back by creating an *empty* object
(i.e. ``new Post()``).

Arrays of Doctrine entities and other "simple" values like ``DateTime`` are also
supported, as long as the ``LiveProp`` has proper PHPDoc that LiveComponents
can read::

    /** @var Product[] */
    public $products = [];

Writable Object Properties or Array Keys
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the user can't change the *properties* of an entity ``LiveProp``
You can allow this by setting ``writable`` to property names that *should* be writable.
This also works as a way to make only *some* keys of an array writable::

    use App\Entity\Post;

    #[AsLiveComponent]
    class EditPost
    {
        #[LiveProp(writable: ['title', 'content'])]
        public Post $post;

        #[LiveProp(writable: ['allow_markdown'])]
        public array $options = ['allow_markdown' => true, 'allow_html' => false];
    }

Now ``post.title``, ``post.content`` or ``options.allow_markdown`` can be used like
normal model names:

.. code-block:: html+twig

    <div {{ attributes }}>
        <input data-model="post.title">
        <textarea data-model="post.content"></textarea>

        Allow Markdown?
        <input type="checkbox" data-model="options.allow_markdown">

        Preview:
        <div>
            <h3>{{ post.title }}</h3>
            {{ post.content|markdown_to_html }}
        </div>
    </div>

Any other properties on the object (or keys on the array) will be read-only.

For arrays, you can set ``writable: true`` to allow *any* key in the array to be
changed, added or removed::

    #[AsLiveComponent]
    class EditPost
    {
        // ...

        #[LiveProp(writable: true)]
        public array $options = ['allow_markdown' => true, 'allow_html' => false];

        #[LiveProp(writable: true)]
        public array $todoItems = ['Train tiger', 'Feed tiger', 'Pet tiger'];
    }

.. note::

    Writable path values are dehydrated/hydrated using the same process as the top-level
    properties (i.e. Symfony's serializer).

Checkboxes, Select Elements Radios & Arrays
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.8

    The ability to use checkboxes to set boolean values was added in LiveComponent 2.8.

Checkboxes can be used to set a boolean or an array of strings::

    #[AsLiveComponent]
    class EditPost
    {
        #[LiveProp(writable: true)]
        public bool $agreeToTerms = false;

        #[LiveProp(writable: true)]
        public array $foods = ['pizza', 'tacos'];
    }

In the template, setting a ``value`` attribute on the checkbox will set that
value on checked. If no ``value`` is set, the checkbox will set a boolean value:

.. code-block:: html+twig

    <input type="checkbox" data-model="agreeToTerms">

    <input type="checkbox" data-model="foods" value="pizza">
    <input type="checkbox" data-model="foods" value="tacos">
    <input type="checkbox" data-model="foods" value="sushi">

``select`` and ``radio`` elements are a bit easier: use these to either set a
single value or an array of values::

    #[AsLiveComponent]
    class EditPost
    {
        // ...

        #[LiveProp(writable: true)]
        public string $meal = 'lunch';

        #[LiveProp(writable: true)]
        public array $foods = ['pizza', 'tacos'];
    }

.. code-block:: html+twig

    <input type="radio" data-model="meal" value="breakfast">
    <input type="radio" data-model="meal" value="lunch">
    <input type="radio" data-model="meal" value="dinner">

    <select data-model="foods" multiple>
        <option value="pizza">Pizza</option>
        <option value="tacos">Tacos</option>
        <option value="sushi">Sushi</option>
    </select>

LiveProp Date Formats
~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.8

    The ``format`` option was introduced in Live Components 2.8.

If you have a writable ``LiveProp`` that is some sort of ``DateTime`` instance,
you can control the format of the model on the frontend with the ``format``
option::

    #[LiveProp(writable: true, format: 'Y-m-d')]
    public ?\DateTime $publishOn = null;

Now you can bind this to a field on the frontend that uses that same format:

.. code-block:: html+twig

    <input type="date" data-model="publishOn">

Allowing an Entity to be Changed to Another
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

What if, instead of changing a *property* on an entity, you want to allow
the user to switch the *entity* to another? For example:

.. code-block:: html+twig

    <select data-model="post">
        {% for post in posts %}
            <option data-model="{{ post.id }}">{{ post.title }}</option>
        {% endfor %}
    </select>

To make the ``post`` property itself writable, use ``writable: true``::

    use App\Entity\Post;

    #[AsLiveComponent]
    class EditPost
    {
        #[LiveProp(writable: true)]
        public Post $post;
    }

.. caution::

    This will allow the user to change the ``Post`` to *any* entity in
    the database. See: https://github.com/symfony/ux/issues/424 for more
    info.

If you want the user to be able to change the ``Post`` *and* certain
properties, use the special ``LiveProp::IDENTITY`` constant::

    use App\Entity\Post;

    #[AsLiveComponent]
    class EditPost
    {
        #[LiveProp(writable: [LiveProp::IDENTITY, 'title', 'content'])]
        public Post $post;
    }

Note that being able to change the "identity" of an object is something
that works only for objects that are dehydrated to a scalar value (like
persisted entities, which dehydrate to an ``id``).

Hydration, DTO's & the Serializer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you try to use a ``LiveProp`` for some unsupported type (e.g.a DTO object),
it will fail. A best practice is to use simple data.

But there are two options to make this work:

1) Hydrating with the Serializer
................................

.. versionadded:: 2.8

    The ``useSerializerForHydration`` option was added in LiveComponent 2.8.

To hydrate/dehydrate through Symfony's serializer, use the ``useSerializerForHydration``
option::

    class ComponentWithAddressDto
    {
        #[LiveProp(useSerializerForHydration: true)]
        public AddressDto $addressDto;
    }

You can also set a ``serializationContext`` option on the ``LiveProp``.

2) Hydrating with Methods: hydrateWith & dehydrateWith
......................................................

You can take full control of the hydration process by setting the ``hydrateWith``
and ``dehydrateWith`` options on ``LiveProp``::

    class ComponentWithAddressDto
    {
        #[LiveProp(dehydrateWith: 'dehydrateAddress', hydrateWith: 'hydrateAddress')]
        public AddressDto $addressDto;

        public function dehydrateAddress(AddressDto $address)
        {
            return [
                'street' => $address->street,
                'city' => $address->city,
                'state' => $address->state,
            ];
        }

        public function hydrateAddress($data): AddressDto
        {
            return new AddressDto($data['street'], $data['city'], $data['state']);
        }
    }

Hydration Extensions
~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.8

    The ``HydrationExtensionInterface`` system was added in LiveComponents 2.8.

If you frequently hydrate/dehydrate the same type of object, you can create a custom
hydration extension to make this easier. For example, if you frequently hydrate
a custom ``Food`` object, a hydration extension might look like this::

    use App\Model\Food;
    use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

    class FoodHydrationExtension implements HydrationExtensionInterface
    {
        public function supports(string $className): bool
        {
            return is_subclass_of($className, Food::class);
        }

        public function hydrate($value)
        {
            return new Food($value['name'], $value['isCooked']);
        }

        public function dehydrate(object $object): mixed
        {
            return [
                'name' => $object->getName(),
                'isCooked' => $object->isCooked(),
            ];
        }
    }

If you're using autoconfiguration, you're done! Otherwise, tag the service
with ``live_component.hydration_extension``.

.. tip::

    Internally, Doctrine entity objects use the ``DoctrineEntityHydrationExtension``
    to control the custom (de)hydration of entity objects.

Updating a Model Manually
-------------------------

You can also change the value of a model more directly, without
using a form field:

.. code-block:: html+twig

    <button
        type="button"
        data-model="mode"
        data-value="edit"
        data-action="live#update"
    >Edit</button>

In this example, clicking the button will change a ``mode``
live property on your component to the value ``edit``. The
``data-action="live#update"`` is Stimulus code that triggers
the update.

.. _working-in-javascript:

Working with the Component in JavaScript
----------------------------------------

Want to change the value of a model or even trigger an action from your
own custom JavaScript? No problem, thanks to a JavaScript ``Component``
object, which is attached to each root component element.

For example, to write your custom JavaScript, you create a Stimulus
controller and put it around (or attached to) your root component element:

.. code-block:: javascript

    // assets/controllers/some-custom-controller.js
    // ...
    import { getComponent } from '@symfony/ux-live-component';

    export default class extends Controller {
        async initialize() {
            this.component = await getComponent(this.element);
        }

        // some Stimulus action triggered, for example, on user click
        toggleMode() {
            // e.g. set some live property called "mode" on your component
            this.component.set('mode', 'editing');
            // you can also say
            this.component.mode = 'editing';

            // or call an action
            this.component.action('save', { arg1: 'value1' });
            // you can also say:
            this.component.save({ arg1: 'value1'});
        }
    }

You can also access the ``Component`` object via a special property
on the root component element, though ``getComponent()`` is the
recommended way, as it will work even if the component is not yet
initialized:

.. code-block:: javascript

    const component = document.getElementById('id-of-your-element').__component;
    component.mode = 'editing';

Finally, you can also set the value of a model field directly. However,
be sure to *also* trigger a ``change`` event so that live components is notified
of the change:

.. code-block:: javascript

    const rootElement = document.getElementById('favorite-food');
    input.value = 'sushi';

    input.dispatchEvent(new Event('change', { bubbles: true }));

JavaScript Component Hooks
~~~~~~~~~~~~~~~~~~~~~~~~~~

The JavaScript ``Component`` object has a number of hooks that you can
use to run code during the lifecycle of your component. To hook into the
component system from Stimulus:

.. code-block:: javascript

    // assets/controllers/some-custom-controller.js
    // ...
    import { getComponent } from '@symfony/ux-live-component';

    export default class extends Controller {
        async initialize() {
            this.component = await getComponent(this.element);

            this.component.on('render:finished', (component) => {
                // do something after the component re-renders
            });
        }
    }

The following hooks are available (along with the arguments that are passed):

* ``connect`` args ``(component: Component)``
* ``disconnect`` args ``(component: Component)``
* ``render:started`` args ``(html: string, response: BackendResponse, controls: { shouldRender: boolean })``
* ``render:finished`` args ``(component: Component)``
* ``response:error`` args ``(backendResponse: BackendResponse, controls: { displayError: boolean })``
* ``loading.state:started`` args ``(element: HTMLElement, request: BackendRequest)``
* ``loading.state:finished`` args ``(element: HTMLElement)``
* ``model:set`` args ``(model: string, value: any, component: Component)``

Adding a Stimulus Controller to your Component Root Element
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.7

    The ``add()`` method was introduced in TwigComponents 2.7.

To add a custom Stimulus controller to your root component element:

.. code-block:: html+twig

    <div {{ attributes.add(stimulus_controller('my-controller', { someValue: 'foo' })) }}>

Loading States
--------------

Often, you'll want to show (or hide) an element while a component is
re-rendering or an :ref:`action <actions>` is processing. For example:

.. code-block:: html+twig

    <!-- show only when the component is loading -->
    <span data-loading>Loading</span>

    <!-- equivalent, longer syntax -->
    <span data-loading="show">Loading</span>

Or, to *hide* an element while the component is loading:

.. code-block:: html+twig

    <!-- hide when the component is loading -->
    <span data-loading="hide">Saved!</span>

Adding and Removing Classes or Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of hiding or showing an entire element, you could add or remove
a class:

.. code-block:: html+twig

    <!-- add this class when loading -->
    <div data-loading="addClass(opacity-50)">...</div>

    <!-- remove this class when loading -->
    <div data-loading="removeClass(opacity-50)">...</div>

    <!-- add multiple classes when loading -->
    <div data-loading="addClass(opacity-50 disabled)">...</div>

Sometimes you may want to add or remove an attribute when loading. That
can be accomplished with ``addAttribute`` or ``removeAttribute``:

.. code-block:: html+twig

    <!-- add the "disabled" attribute when loading -->
    <div data-loading="addAttribute(disabled)">...</div>

You can also combine any number of directives by separating them with a
space:

.. code-block:: html+twig

    <div data-loading="addClass(opacity-50) addAttribute(disabled)">...</div>

Finally, you can add the ``delay`` modifier to not trigger the loading
changes until loading has taken longer than a certain amount of time:

.. code-block:: html+twig

    <!-- Add class after 200ms of loading -->
    <div data-loading="delay|addClass(opacity-50)">...</div>

    <!-- Show after 200ms of loading -->
    <div data-loading="delay|show">Loading</div>

    <!-- Show after 500ms of loading -->
    <div data-loading="delay(500)|show">Loading</div>

Targeting Loading for a Specific Action
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.5

    The ``action()`` modifier was introduced in Live Components 2.5.

To only toggle the loading behavior when a specific action is triggered,
use the ``action()`` modifier with the name of the action - e.g. ``saveForm()``:

.. code-block:: html+twig

    <!-- show only when the "saveForm" action is triggering -->
    <span data-loading="action(saveForm)|show">Loading</span>
    <!-- multiple modifiers -->
    <div data-loading="action(saveForm)|delay|addClass(opacity-50)">...</div>

Targeting Loading When a Specific Model Changes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.5

    The ``model()`` modifier was introduced in Live Components 2.5.

You can also toggle the loading behavior only if a specific model value
was just changed using the ``model()`` modifier:

.. code-block:: html+twig

    <input data-model="email" type="email">

    <span data-loading="model(email)|show">
        Checking if email is available...
    </span>

    <!-- multiple modifiers & child properties -->
    <span data-loading="model(user.email)|delay|addClass(opacity-50)">...</span>

.. _actions:

Actions
-------

Live components require a single "default action" that is used to
re-render it. By default, this is an empty ``__invoke()`` method and can
be added with the ``DefaultActionTrait``. Live components are actually
Symfony controllers so you can add the normal controller
attributes/annotations (ie ``#[Cache]``/``#[Security]``) to either the
entire class just a single action.

You can also trigger custom actions on your component. Let's pretend we
want to add a "Reset Max" button to our "random number" component
that, when clicked, sets the min/max numbers back to a default value.

First, add a method with a ``LiveAction`` attribute above it that does
the work::

    // src/Components/RandomNumber.php
    namespace App\Components;

    // ...
    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    class RandomNumber
    {
        // ...

        #[LiveAction]
        public function resetMax()
        {
            $this->max = 1000;
        }

        // ...
    }

To call this, add ``data-action="live#action"`` and ``data-action-name``
to an element (e.g. a button or form):

.. code-block:: html+twig

    <button
        data-action="live#action"
        data-action-name="resetMax"
    >Reset Min/Max</button>

Done! When the user clicks this button, a POST request will be sent that
will trigger the ``resetMax()`` method! After calling that method,
the component will re-render like normal, using the new ``$max``
property value!

You can also add several "modifiers" to the action:

.. code-block:: html+twig

    <form>
        <button
            data-action="live#action"
            data-action-name="prevent|debounce(300)|save"
        >Save</button>
    </form>

The ``prevent`` modifier would prevent the form from submitting
(``event.preventDefault()``). The ``debounce(300)`` modifier will add
300ms of "debouncing" before the action is executed. In other words, if
you click really fast 5 times, only one Ajax request will be made!

Actions & Services
~~~~~~~~~~~~~~~~~~

One really neat thing about component actions is that they are *real*
Symfony controllers. Internally, they are processed identically to a
normal controller method that you would create with a route.

This means that, for example, you can use action autowiring::

    // src/Components/RandomNumber.php
    namespace App\Components;

    // ...
    use Psr\Log\LoggerInterface;

    class RandomNumber
    {
        // ...

        #[LiveAction]
        public function resetMax(LoggerInterface $logger)
        {
            $this->max = 1000;
            $logger->debug('The min/max were reset!');
        }

        // ...
    }

Actions & Arguments
~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.1

    The ability to pass arguments to actions was added in version 2.1.

You can also provide custom arguments to your action:

.. code-block:: html+twig

    <form>
        <button
            data-action="live#action"
            data-action-name="addItem(id={{ item.id }}, itemName=CustomItem)"
        >Add Item</button>
    </form>

In your component, to allow each argument to be passed, we need to add
the ``#[LiveArg()]`` attribute::

    // src/Components/ItemList.php
    namespace App\Components;

    // ...
    use Psr\Log\LoggerInterface;
    use Symfony\UX\LiveComponent\Attribute\LiveArg;

    class ItemList
    {
        // ...
        #[LiveAction]
        public function addItem(#[LiveArg] int $id, #[LiveArg('itemName')] string $name)
        {
            $this->id = $id;
            $this->name = $name;
        }
    }

Normally, the argument name in PHP - e.g. ``$id`` - should match the
argument named used in Twig ``id={{ item.id }}``. But if they don't
match, you can pass an argument to ``LiveArg``, like we did with ``itemName``.

Actions and CSRF Protection
~~~~~~~~~~~~~~~~~~~~~~~~~~~

When you trigger an action, a POST request is sent that contains a
``X-CSRF-TOKEN`` header. This header is automatically populated and
validated. In other words… you get CSRF protection without any work.

Your only job is to make sure that the CSRF component is installed:

.. code-block:: terminal

    $ composer require symfony/security-csrf

If you want to disable CSRF for a single component you can set
``csrf`` option to ``false``::

    namespace App\Twig\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent(csrf: false)]
    class MyLiveComponent
    {
        // ...
    }

Actions, Redirecting and AbstractController
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes, you may want to redirect after an action is executed
(e.g. your action saves a form and then you want to redirect to another
page). You can do that by returning a ``RedirectResponse`` from your
action::

    // src/Components/RandomNumber.php
    namespace App\Components;

    // ...
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class RandomNumber extends AbstractController
    {
        // ...

        #[LiveAction]
        public function resetMax()
        {
            // ...

            $this->addFlash('success', 'Max has been reset!');

            return $this->redirectToRoute('app_random_number');
        }

        // ...
    }

You probably noticed one interesting trick: to make redirecting easier,
the component now extends ``AbstractController``! That is totally
allowed, and gives you access to all of your normal controller
shortcuts. We even added a flash message!

.. _forms:

Forms
-----

A component can also help render a `Symfony form`_, either the entire
form (useful for automatic validation as you type) or just one or some
fields (e.g. a markdown preview for a ``textarea`` or `dependent form fields`_.

Rendering an Entire Form in a Component
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Suppose you have a ``PostType`` form class that's bound to a ``Post``
entity and you'd like to render this in a component so that you can get
instant validation as the user types::

    namespace App\Form;

    use App\Entity\Post;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class PostType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder
                ->add('title')
                ->add('slug')
                ->add('content')
            ;
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults([
                'data_class' => Post::class,
            ]);
        }
    }

Before you start thinking about the component, make sure that you have
your controller set up so you can handle the form submit. There's
nothing special about this controller: it's written however you normally
write your form controller logic::

    namespace App\Controller;

    use App\Entity\Post;
    use App\Form\PostType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;

    class PostController extends AbstractController
    {
        #[Route('/admin/post/{id}/edit', name: 'app_post_edit')]
        public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
        {
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                return $this->redirectToRoute('app_post_index');
            }

            return $this->render('post/edit.html.twig', [
                'post' => $post,
                'form' => $form,
            ]);
        }
    }

Great! In the template, instead of rendering the form, let's render a
``PostForm`` component that we will create next:

.. code-block:: html+twig

    {# templates/post/edit.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <h1>Edit Post</h1>

        {{ component('PostForm', {
            post: post,
            form: form
        }) }}
    {% endblock %}

Ok: time to build that ``PostForm`` component! The Live Components
package comes with a special trait - ``ComponentWithFormTrait`` - to
make it easy to deal with forms::

    namespace App\Twig\Components;

    use App\Entity\Post;
    use App\Form\PostType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\FormInterface;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\ComponentWithFormTrait;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    #[AsLiveComponent]
    class PostForm extends AbstractController
    {
        use DefaultActionTrait;
        use ComponentWithFormTrait;

        /**
         * The initial data used to create the form.
         *
         * Needed so the same form can be re-created
         * when the component is re-rendered via Ajax.
         *
         * The `fieldName` option is needed in this situation because
         * the form renders fields with names like `name="post[title]"`.
         * We set `fieldName: ''` so that this live prop doesn't collide
         * with that data. The value - data - could be anything.
         */
        #[LiveProp(fieldName: 'data')]
        public Post $post;

        /**
         * Used to re-create the PostType form for re-rendering.
         */
        protected function instantiateForm(): FormInterface
        {
            // we can extend AbstractController to get the normal shortcuts
            return $this->createForm(PostType::class, $this->post);
        }
    }

The trait forces you to create an ``instantiateForm()`` method, which is
used when the component is rendered via AJAX. Notice that, in order to
recreate the *same* form, we pass in the ``Post`` object and set it as a
``LiveProp``.

The template for this component will render the form, which is available
as ``form`` thanks to the trait:

.. versionadded:: 2.1

    The ability to access ``form`` directly in your component's template
    was added in LiveComponents 2.1. Previously ``this.form`` was required.

.. code-block:: html+twig

    {# templates/components/PostForm.html.twig #}
    <div {{ attributes }}>
        {{ form_start(form) }}
            {{ form_row(form.title) }}
            {{ form_row(form.slug) }}
            {{ form_row(form.content) }}

            <button>Save</button>
        {{ form_end(form) }}
    </div>

That's a pretty boring template! It includes the normal
``attributes`` and then you render the form however you want.

But the result is incredible! As you finish changing each field, the
component automatically re-renders - including showing any validation
errors for that field! Amazing!

.. versionadded:: 2.3

    Before version 2.3, a ``data-action="live#update"`` was required
    on a parent element of the form to trigger updates. That should
    now be removed.

This is possible thanks to the team work of two pieces:

-  ``ComponentWithFormTrait`` adds a ``data-model="on(change)|*"``
   attribute to your ``<form>`` tag. This causes each field to become
   a "model" that will update on "change"
   (override the ``getDataModelValue()`` method to control this).
   See ":ref:`name-attribute-model`".

-  ``ComponentWithFormTrait`` has a modifiable ``LiveProp`` that
   holds the form data and is updated each time a field changes.
   On each re-render, these values are used to "submit" the form,
   triggering validation! However, if a field has not been modified
   yet by the user, its validation errors are cleared so that they
   aren't displayed.

Making the Post Object Optional for a "New Form" Component
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The previous component could be used to edit an existing post or create
a new post. But either way, the component *requires* you to pass it
a ``post`` property.

Tou can make that optional by adding a ``mount()`` method::

    #[AsLiveComponent]
    class PostForm extends AbstractController
    {
        // ...
        #[LiveProp(fieldName: 'data')]
        public Post $post;

        public function mount(Post $post = null)
        {
            $this->post = $post ?? new Post();
        }
    }

If a ``post`` variable is passed to ``component()``, then it will
be passed to the ``mount()`` method where you either use it, or
create a new ``Post``.

Form Rendering Problems
~~~~~~~~~~~~~~~~~~~~~~~

For the most part, rendering a form inside a component works
beautifully. But there are a few situations when your form may not
behave how you want.

**A) Text Boxes Removing Trailing Spaces**

If you're re-rendering a field on the ``input`` event (that's the
default event on a field, which is fired each time you type in a text
box), then if you type a "space" and pause for a moment, the space will
disappear!

This is because Symfony text fields "trim spaces" automatically. When
your component re-renders, the space will disappear… as the user is
typing! To fix this, either re-render on the ``change`` event (which
fires after the text box loses focus) or set the ``trim`` option of your
field to ``false``::

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('content', TextareaType::class, [
                'trim' => false,
            ])
        ;
    }

**B) ``PasswordType`` loses the password on re-render**

If you're using the ``PasswordType``, when the component re-renders, the
input will become blank! That's because, by default, the
``PasswordType`` does not re-fill the ``<input type="password">`` after
a submit.

To fix this, set the ``always_empty`` option to ``false`` in your form::

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ...
            ->add('plainPassword', PasswordType::class, [
                'always_empty' => false,
            ])
        ;
    }

Submitting the Form via an action()
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Notice that, while we *could* add a ``save()`` :ref:`component action <actions>`
that handles the form submit through the component,
we've chosen not to do that so far. The reason is simple: by creating a
normal route & controller that handles the submit, our form continues to
work without JavaScript.

However, you *can* do this if you'd like. In that case, you wouldn't
need any form logic in your controller::

    #[Route('/admin/post/{id}/edit', name: 'app_post_edit')]
    public function edit(Post $post): Response
    {
        return $this->render('post/edit.html.twig', [
            'post' => $post,
        ]);
    }

And you wouldn't pass any ``form`` into the component:

.. code-block:: html+twig

    {# templates/post/edit.html.twig #}
    <h1>Edit Post</h1>

    {{ component('PostForm', {
        post: post
    }) }}

When you do *not* pass a ``form`` into a component that uses
``ComponentWithFormTrait``, the form will be created for you
automatically. Let's add the ``save()`` action to the component::

    // ...
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    class PostForm extends AbstractController
    {
        // ...

        #[LiveAction]
        public function save(EntityManagerInterface $entityManager)
        {
            // shortcut to submit the form with form values
            // if any validation fails, an exception is thrown automatically
            // and the component will be re-rendered with the form errors
            $this->submitForm();

            /** @var Post $post */
            $post = $this->getFormInstance()->getData();
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post saved!');

            return $this->redirectToRoute('app_post_show', [
                'id' => $this->post->getId(),
            ]);
        }
    }

Finally, tell the ``form`` element to use this action:

.. code-block:: twig

    {# templates/components/PostForm.html.twig #}
    {# ... #}

    {{ form_start(form, {
        attr: {
            'data-action': 'live#action',
            'data-action-name': 'prevent|save'
        }
    }) }}

Now, when the form is submitted, it will execute the ``save()`` method
via Ajax. If the form fails validation, it will re-render with the
errors. And if it's successful, it will redirect.

Using Actions to Change your Form: CollectionType
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony's `CollectionType`_ can be used to embed a collection of
embedded forms including allowing the user to dynamically add or remove
them. Live components make this all possible while
writing zero JavaScript.

For example, imagine a "Blog Post" form with an embedded "Comment" forms
via the ``CollectionType``::

    namespace App\Form;

    use App\Entity\BlogPost;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\CollectionType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class BlogPostFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder
                ->add('title', TextType::class)
                // ...
                ->add('comments', CollectionType::class, [
                    'entry_type' => CommentFormType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                ])
            ;
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults(['data_class' => BlogPost::class]);
        }
    }

Now, create a Twig component to render the form::

    namespace App\Twig;

    use App\Entity\BlogPost;
    use App\Entity\Comment;
    use App\Form\BlogPostFormType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\FormInterface;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\ComponentWithFormTrait;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    #[AsLiveComponent]
    class BlogPostCollectionType extends AbstractController
    {
        use ComponentWithFormTrait;
        use DefaultActionTrait;

        #[LiveProp]
        public BlogPost $post;

        protected function instantiateForm(): FormInterface
        {
            return $this->createForm(BlogPostFormType::class, $this->post);
        }

        #[LiveAction]
        public function addComment()
        {
            // "formValues" represents the current data in the form
            // this modifies the form to add an extra comment
            // the result: another embedded comment form!
            // change "comments" to the name of the field that uses CollectionType
            $this->formValues['comments'][] = [];
        }

        #[LiveAction]
        public function removeComment(#[LiveArg] int $index)
        {
            unset($this->formValues['comments'][$index]);
        }
    }

The template for this component has two jobs: (1) render the form
like normal and (2) include links that trigger the ``addComment()``
and ``removeComment()`` actions:

.. code-block:: html+twig

    <div{{ attributes }}>
        {{ form_start(form) }}
            {{ form_row(form.title) }}

            <h3>Comments:</h3>
            {% for key, commentForm in form.comments %}
                <button
                    data-action="live#action"
                    data-action-name="removeComment(index={{ key }})"
                    type="button"
                >X</button>

                {{ form_widget(commentForm) }}
            {% endfor %}
            </div>

            {# avoid an extra label for this field #}
            {% do form.comments.setRendered %}

            <button
                data-action="live#action"
                data-action-name="addComment"
                type="button"
            >+ Add Comment</button>

            <button type="submit" >Save</button>
        {{ form_end(form) }}
    </div>

Done! Behind the scenes, it works like this:

A) When the user clicks "+ Add Comment", an Ajax request is sent that
triggers the ``addComment()`` action.

B) ``addComment()`` modifies ``formValues``, which you can think of as
the raw "POST" data of your form.

C) Still during the Ajax request, the ``formValues`` are "submitted"
into your form. The new key inside of ``$this->formValues['comments']``
tells the ``CollectionType`` that you want a new, embedded form.

D) The form is rendered - now with another embedded form! - and the
Ajax call returns with the form (with the new embedded form).

When the user clicks ``removeComment()``, a similar process happens.

.. note::

    When working with Doctrine entities, add ``orphanRemoval: true``
    and ``cascade={"persist"}`` to your ``OneToMany`` relationship.
    In this example, these options would be added to the ``OneToMany``
    attribute above the ``Post.comments`` property. These help new
    items save and deletes any items whose embedded forms are removed.

Using LiveCollectionType
~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.2

    The ``LiveCollectionType`` and the ``LiveCollectionTrait`` was added in LiveComponent 2.2.

The ``LiveCollectionType`` uses the same method described above, but in
a generic way, so it needs even less code. This form type adds an 'Add'
and a 'Delete' button for each row by default, which work out of the box
thanks to the ``LiveCollectionTrait``.

Let's take the same example as before, a "Blog Post" form with an embedded "Comment" forms
via the ``LiveCollectionType``::

    namespace App\Form;

    use App\Entity\BlogPost;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

    class BlogPostFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder
                ->add('title', TextType::class)
                // ...
                ->add('comments', LiveCollectionType::class, [
                    'entry_type' => CommentFormType::class,
                ])
            ;
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults(['data_class' => BlogPost::class]);
        }
    }

Now, create a Twig component to render the form::

    namespace App\Twig;

    use App\Entity\BlogPost;
    use App\Form\BlogPostFormType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\FormInterface;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\LiveComponent\LiveCollectionTrait;

    #[AsLiveComponent]
    class BlogPostCollectionType extends AbstractController
    {
        use LiveCollectionTrait;
        use DefaultActionTrait;

        #[LiveProp]
        public BlogPost $post;

        protected function instantiateForm(): FormInterface
        {
            return $this->createForm(BlogPostFormType::class, $this->post);
        }
    }

There is no need for a custom template just render the form as usual:

.. code-block:: html+twig

    <div {{ attributes }}>
        {{ form(form) }}
    </div>

This automatically renders add and delete buttons that are connected to the live component.
If you want to customize how the buttons and the collection rows are rendered, you can use
`Symfony's built-in form theming techniques`_, but you should note that, the buttons are not
part of the form tree.

.. note::

    Under the hood, ``LiveCollectionType`` adds ``button_add`` and
    ``button_delete`` fields to the form in a special way. These fields
    are not added as regular form fields, so they are not part of the form
    tree, but only the form view. The ``button_add`` is added to the
    collection view variables and a ``button_delete`` is added to each
    item view variables.

Here are some examples of these techniques.

If you only want to customize some attributes, the simplest to use the options in the form type::

    // ...
    $builder
        // ...
        ->add('comments', LiveCollectionType::class, [
            'entry_type' => CommentFormType::class,
            'label' => false,
            'button_delete_options' => [
                'label' => 'X',
                'attr' => [
                    'class' => 'btn btn-outline-danger',
                ],
            ]
        ])
    ;

Inline rendering:

.. code-block:: html+twig

    <div {{ attributes }}>
        {{ form_start(form) }}
            {{ form_row(form.title)

            <h3>Comments:</h3>
            {% for key, commentForm in form.comments %}
                {# render a delete button for every row #}
                {{ form_row(commentForm.vars.button_delete, { label: 'X', attr: { class: 'btn btn-outline-danger' } }) }}

                {# render rest of the comment form #}
                {{ form_row(commentForm, { label: false }) }}
            {% endfor %}

            {# render the add button #}
            {{ form_widget(form.comments.vars.button_add, { label: '+ Add comment', class: 'btn btn-outline-primary' }) }}

            {# render rest of the form #}
            {{ form_row(form) }}

            <button type="submit" >Save</button>
        {{ form_end(form) }}
    </div>

Override the specific block for comment items:

.. code-block:: html+twig

    {% form_theme form 'components/_form_theme_comment_list.html.twig' %}

    <div {{ attributes }}>
        {{ form_start(form) }}

        {{ form_start(form) }}
            {{ form_row(form.title)

            <h3>Comments:</h3>
            <ul>
                {{ form_row(form.comments, { skip_add_button: true }) }}
            </ul>

            {# render rest of the form #}
            {{ form_row(form) }}

            <button type="submit" >Save</button>
        {{ form_end(form) }}
    </div>


.. code-block:: html+twig

    {# templates/components/_form_theme_comment_list.html.twig #}
    {%- block _blog_post_form_comments_entry_row -%}
        <li class="...">
            {{ form_row(form.content, { label: false }) }}
            {{ form_row(button_delete, { label: 'X', attr: { class: 'btn btn-outline-danger' } }) }}
        </li>
    {% endblock %}

.. note::

    You may put the form theme into the component template and use ``{% form_theme form _self %}``. However,
    because the component template doesn't extend anything, it will not work as expected, you must point
    ``form_theme`` to a separate template. See `How to Work with Form Themes`_.

Override the generic buttons and collection entry:

The ``add`` and ``delete`` buttons are rendered as separate ``ButtonType`` form
types and can be customized like a normal form type via the ``live_collection_button_add``
and ``live_collection_button_delete`` block prefix respectively:

.. code-block:: html+twig

    {% block live_collection_button_add_widget %}
        {% set attr = attr|merge({'class': attr.class|default('btn btn-ghost')}) %}
        {% set translation_domin = false %}
        {% set label_html = true %}
        {%- set label -%}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            {{ 'form.collection.button.add.label'|trans({}, 'forms') }}
        {%- endset -%}
        {{ block('button_widget') }}
    {% endblock live_collection_button_add_widget %}

To control how each row is rendered you can override the blocks related to the ``LiveCollectionType``. This
works the same way as `the traditional collection type`_, but you should use ``live_collection_*``
and ``live_collection_entry_*`` as prefixes instead.

For example, by default the add button is placed after the items (the comments in our case). Let's move it before them.

.. code-block:: twig

    {%- block live_collection_widget -%}
        {%- if button_add is defined and not button_add.rendered -%}
            {{ form_row(button_add) }}
        {%- endif -%}
        {{ block('form_widget') }}
    {%- endblock -%}

Now add a div around each row:

.. code-block:: html+twig

    {%- block live_collection_entry_row -%}
        <div>
            {{ block('form_row') }}
            {%- if button_delete is defined and not button_delete.rendered -%}
                {{ form_row(button_delete) }}
            {%- endif -%}
        </div>
    {%- endblock -%}

As another example, let's create a general bootstrap 5 theme for the live
collection type, rendering every item in a table row:

.. code-block:: html+twig

    {%- block live_collection_widget -%}
        <table class="table table-borderless form-no-mb">
            <thead>
            <tr>
                {% for child in form|last %}
                    <td>{{ form_label(child) }}</td>
                {% endfor %}
                <td></td>
            </tr>
            </thead>
            <tbody>
                {{ block('form_widget') }}
            </tbody>
        </table>
        {%- if skip_add_button|default(false) is same as(false) and button_add is defined and not button_add.rendered -%}
            {{ form_widget(button_add, { label: '+ Add Item', attr: { class: 'btn btn-outline-primary' } }) }}
        {%- endif -%}
    {%- endblock -%}

    {%- block live_collection_entry_row -%}
        <tr>
            {% for child in form %}
                <td>{{- form_row(child, { label: false }) -}}</td>
            {% endfor %}
            <td>
                {{- form_row(button_delete, { label: 'X', attr: { class: 'btn btn-outline-danger' } }) -}}
            </td>
        </tr>
    {%- endblock -%}

To render the add button later in the template, you can skip rendering it initially with ``skip_add_button``,
then render it manually after:

.. code-block:: html+twig

    <table class="table table-borderless form-no-mb">
        <thead>
            <tr>
                <td>Item</td>
                <td>Priority</td>
                <td></td>
            </tr>
        </thead>
        <tbody>
            {{ form_row(form.todoItems, { skip_add_button: true }) }}
        </tbody>
    </table>

    {{ form_widget(form.todoItems.vars.button_add, { label: '+ Add Item', attr: { class: 'btn btn-outline-primary' } }) }}

Validation (without a Form)
---------------------------

.. note::

    If your component :ref:`contains a form <forms>`, then validation
    is built-in automatically. Follow those docs for more details.

If you're building a form *without* using Symfony's form
component, you *can* still validate your data.

First use the ``ValidatableComponentTrait`` and add any constraints you
need::

    use App\Entity\User;
    use Symfony\Component\Validator\Constraints as Assert;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\ValidatableComponentTrait;

    #[AsLiveComponent]
    class EditUser
    {
        use ValidatableComponentTrait;

        #[LiveProp(writable: ['email', 'plainPassword'])]
        #[Assert\Valid]
        public User $user;

         #[LiveProp]
         #[Assert\IsTrue]
        public bool $agreeToTerms = false;
    }

Be sure to add the ``IsValid`` attribute/annotation to any property
where you want the object on that property to also be validated.

Thanks to this setup, the component will now be automatically validated
on each render, but in a smart way: a property will only be validated
once its "model" has been updated on the frontend. The system keeps
track of which models have been updated and only stores the errors for
those fields on re-render.

You can also trigger validation of your *entire* object manually in an
action::

    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    #[AsLiveComponent]
    class EditUser
    {
        // ...

        #[LiveAction]
        public function save()
        {
            // this will throw an exception if validation fails
            $this->validate();

            // perform save operations
        }
    }

If validation fails, an exception is thrown, but the component will be
re-rendered. In your template, render errors using an ``_errors`` variable:

.. code-block:: html+twig

    {% if _errors.has('post.content') %}
        <div class="error">
            {{ _errors.get('post.content') }}
        </div>
    {% endif %}
    <textarea
        data-model="post.content"
        class="{{ _errors.has('post.content') ? 'is-invalid' : '' }}"
    ></textarea>

    {% if _errors.has('agreeToTerms') %}
        <div class="error">
            {{ _errors.get('agreeToTerms') }}
        </div>
    {% endif %}
    <input type="checkbox" data-model="agreeToTerms" class="{{ _errors.has('agreeToTerms') ? 'is-invalid' : '' }}"/>

    <button
        type="submit"
        data-action="live#action"
        data-action-name="prevent|save"
    >Save</button>

Once a component has been validated, the component will "remember" that
it has been validated. This means that, if you edit a field and the
component re-renders, it will be validated again.

Resetting Validation Errors
~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to clear validation errors (e.g. so you can reuse the form again),
you can call the ``resetValidation()`` method::

    // ...
    class EditUser
    {
        // ...

        #[LiveAction]
        public function save()
        {
            // validate, save, etc

            // reset your live props to the original state
            $this->user = new User();
            $this->agreeToTerms = false;
            // clear the validation state
            $this->resetValidation();
        }
    }

Real-Time Validation on Change
------------------------------

As soon as validation is enabled, each field will be validated the
moment that its model is updated. By default, that happens in the
``input`` event, so when the user types into text fields. Often,
that's too much (e.g. you want a user to finish typing their full email
address before validating it).

To validate only on "change", use the ``on(change)`` modifier:

.. code-block:: html+twig

    <input
        type="email"
        data-model="on(change)|user.email"
        class="{{ _errors.has('post.content') ? 'is-invalid' : '' }}"
    >

Polling
-------

You can also use "polling" to continually refresh a component. On the
**top-level** element for your component, add ``data-poll``:

.. code-block:: diff

      <div
          {{ attributes }}
    +     data-poll
      >

This will make a request every 2 seconds to re-render the component. You
can change this by adding a ``delay()`` modifier. When you do this, you
need to be specific that you want to call the ``$render`` method. To
delay for 500ms:

.. code-block:: html+twig

    <div
        {{ attributes }}
        data-poll="delay(500)|$render"
    >

You can also trigger a specific "action" instead of a normal re-render:

.. code-block:: html+twig

    <div
        {{ attributes }}

        data-poll="save"
        {#
        Or add a delay() modifier:
        data-poll="delay(2000)|save"
        #}
    >

.. _emit:

Communication Between Components: Emitting Events
-------------------------------------------------

.. versionadded:: 2.8

    The ability to emit events was added in Live Components 2.8.

Events allow you to communicate between any two components that live
on your page.

Emitting an Event
~~~~~~~~~~~~~~~~~

There are three ways to emit an event:

1. From Twig:

.. code-block:: html+twig

    <button
        data-action="live#emit"
        data-event="productAdded"
    >

2. From your PHP component via ``ComponentToolsTrait``::

    use Symfony\UX\LiveComponent\ComponentToolsTrait;

    class MyComponent
    {
        use ComponentToolsTrait;

        #[LiveAction]
        public function saveProduct()
        {
            // ...

            $this->emit('productAdded');
        }
    }

3. :ref:`From JavaScript <working-in-javascript>`, using your component:

.. code-block:: javascript

    this.component.emit('productAdded');

Listen to Events
~~~~~~~~~~~~~~~~

To listen to an event, add a method with a `#[LiveListener]` above it::

    #[LiveProp]
    public int $productCount = 0;

    #[LiveListener('productAdded')]
    public function incrementProductCount()
    {
        $this->productCount++;
    }

Thanks to this, when any other component emits the ``productAdded`` event, an Ajax
call will be made to call this method and re-render the component.

Behind the scenes, event listeners are also `LiveActions <actions>`, so you can
autowire any services you need.

Passing Data to Listeners
~~~~~~~~~~~~~~~~~~~~~~~~~

You can also pass extra (scalar) data to the listeners::

    #[LiveAction]
    public function saveProduct()
    {
        // ...

        $this->emit('productAdded', [
            'product' => $product->getId(),
        ]);
    }

In your listeners, you can access this by adding a matching argument
name with `#[LiveArg]` in front::

    #[LiveListener('productAdded')]
    public function incrementProductCount(#[LiveArg] int $product)
    {
        $this->productCount++;
        $this->lastProduct = $data['product'];
    }

And because event listeners are also actions, you can type-hint an argument
with an entity name, just like you would in a controller::

    #[LiveListener('productAdded')]
    public function incrementProductCount(#[LiveArg] Product $product)
    {
        $this->productCount++;
        $this->lastProduct = $product;
    }

Scoping Events
~~~~~~~~~~~~~~

By default, when an event is emitted, it is sent to *all* components that are
currently on the page. You can scope these in various ways:

Emitting only to Parent Components
..................................

If you want to emit an event to only the parent components, use the
``emitUp()`` method:

.. code-block:: html+twig

    <button
        data-action="live#emitUp"
        data-event="productAdded"
    >

Or, in PHP::

    $this->emitUp('productAdded');

Emitting only to Components with a Specific Name
................................................

If you want to emit an event to only components with a specific name,
use the ``name()`` modifier:

.. code-block:: html+twig

    <button
        data-action="live#emit"
        data-event="name(ProductList)|productAdded"
    >

Or, in PHP::

    $this->emit('productAdded', name: 'ProductList');

Emitting only to Yourself
.........................

To emit an event to only yourself, use the ``emitSelf()`` method:

.. code-block:: html+twig

    <button
        data-action="live#emitSelf"
        data-event="productAdded"
    >

Or, in PHP::

    $this->emitSelf('productAdded');

Dispatching Browser/JavaScript Events
-------------------------------------

Sometimes you may want to dispatch a JavaScript event from your component. You
could use this to signal, for example, that a modal should close::

    use Symfony\UX\LiveComponent\ComponentToolsTrait;
    // ...

    class MyComponent
    {
        use ComponentToolsTrait;

        #[LiveAction]
        public function saveProduct()
        {
            // ...

            $this->dispatchBrowserEvent('modal:close');
        }
    }

This will dispatch a ``modal:close`` event on the top-level element of
your component. It's often handy to listen to this event in a custom
Stimulus controller - like this for Bootstrap's modal:

.. code-block:: javascript

    // assets/controllers/bootstrap-modal-controller.js
    import { Controller } from '@hotwired/stimulus';
    import { Modal } from 'bootstrap';

    export default class extends Controller {
        modal = null;

        initialize() {
            this.modal = Modal.getOrCreateInstance(this.element);
            window.addEventListener('modal:close', () => this.modal.hide());
        }
    }

Just make sure this controller is attached to the modal element:

.. code-block:: html+twig

    <div class="modal fade" {{ stimulus_controller('bootstrap-modal') }}">
        <div class="modal-dialog">
            ... content ...
        </div>
    </div>

You can also pass data to the event::

    $this->dispatchBrowserEvent('product:created', [
        'product' => $product->getId(),
    ]);

This becomes the ``detail`` property of the event:

.. code-block:: javascript

    window.addEventListener('product:created', (event) => {
        console.log(event.detail.product);
    });

Nested Components
-----------------

Need to nest one live component inside another one? No problem! As a
rule of thumb, **each component exists in its own, isolated universe**.
This means that if a parent component re-renders, it won't automatically
cause the child to re-render (but it *can* - keep reading). Or, if
a model in a child updates, it won't also update that model in its parent
(but it *can* - keep reading).

The parent-child system is *smart*. And with a few tricks, you can make
it behave exactly like you need.

Each component re-renders independent of one another
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If a parent component re-renders, this won't, by default, cause any child
components to re-render, but you *can* make it do that. Let's look at an
example of a todo list component with a child that renders the total number of
todo items:

.. code-block:: html+twig

    {# templates/components/TodoList.html.twig #}
    <div {{ attributes }}>
        <input data-model="listName">

        {% for todo in todos %}
            ...
        {% endfor %}

        {{ component('TodoFooter', {
            count: todos|length
        }) }}
    </div>

Suppose the user updates the ``listName`` model and the parent component
re-renders. In this case, the child component will *not* re-render by design:
each component lives in its own universe.

.. versionadded:: 2.8

    The ``updateFromParent`` option was added in Live Components 2.8. Previously,
    a child would re-render when *any* props passed into it changed.

However, if the user adds a *new* todo item then we *do* want the ``TodoFooter``
child component to re-render: using the new ``count`` value. To trigger this,
in the ``TodoFooter`` component, add the ``updateFromParent`` option::

    #[LiveComponent()]
    class TodoFooter
    {
        #[LiveProp(updateFromParent: true)]
        public int $count = 0;
    }

Now, when the parent component re-renders, if the value of the ``count`` prop
changes, the child will make a second Ajax request to re-render itself.

.. note::

    To work, the name of the prop that's passed when rendering the ``TodoFooter``
    component must match the property name that has the ``updateFromParent`` - e.g.
    ``{{ component('TodoFooter', { count: todos|length }) }}``. If you pass in a
    different name and set the ``count`` property via a ref:``mount()`` method, the
    child component will not re-render correctly.

Child components keep their modifiable LiveProp values
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

What if the ``TodoFooter`` component in the previous example also has
an ``isVisible`` ``LiveProp(writable: true)`` property which starts as
``true`` but can be changed (via a link click) to ``false``. Will
re-rendering the child when ``count`` changes cause this to be reset back to its
original value? Nope! When the child component re-renders, it will keep the
current value for all props, except for those that are marked as
``updateFromParent``.

What if you *do* want your entire child component to re-render (including
resetting writable live props) when some value in the parent changes? This
can be done by manually giving your component a ``data-live-id`` attribute
that will change if the component should be totally re-rendered:

.. code-block:: html+twig

    {# templates/components/TodoList.html.twig #}
    <div {{ attributes }}>
        <!-- ... -->

        {{ component('TodoFooter', {
            count: todos|length,
            'data-live-id': 'todo-footer-'~todos|length
        }) }}
    </div>

In this case, if the number of todos change, then the ``data-live-id``
attribute of the component will also change. This signals that the
component should re-render itself completely, discarding any writable
LiveProp values.

Actions in a child do not affect the parent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Again, each component is its own, isolated universe! For example,
suppose your child component has:

.. code-block:: html

    <button data-action="live#action" data-action-name="save">Save</button>

When the user clicks that button, it will attempt to call the ``save``
action in the *child* component only, even if the ``save`` action
actually only exists in the parent. The same is true for ``data-model``,
though there is some special handling for this case (see next point).

Communicating with a Parent Component
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There are two main ways to communicate from a child component to a parent
component:

1. :ref:`Emitting events <emit>`

    The most flexible way to communicate: any information can be sent
    from the child to the parent.

2. :ref:`Updating a parent model from a child <update-parent-model>`

    Useful as a simple way to "synchronize" a child model with a parent
    model: when the child model changes, the parent model will also change.

.. _data-model:

Updating a Parent Model from a Child
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Suppose a child component has a:

.. code-block:: html

    <textarea data-model="value">

When the user changes this field, this will *only* update the
``value`` field in the *child* component… because (yup, we're
saying it again): each component is its own, isolated universe.

However, sometimes this isn't what you want! Sometimes, when a
child model changes, that should also update a model on the
parent. To do this, pass a ``dataModel`` (or ``data-model``)
attribute to the child:

.. code-block:: twig

    {# templates/components/PostForm.html.twig #}
    {{ component('TextareaField', {
        dataModel: 'content',
        error: _errors.get('content'),
    }) }}

This does two things:

#. A prop called ``value`` will be passed into ``TextareaField``
   set to ``content`` from the parent component (i.e. the same
   as manually passing ``value: content`` into the component).

#. When the ``value`` prop changes inside of ``TextareaField``,
   the ``content`` prop will change on the parent component.

This result is that, when ``value`` changes, the parent component
will also re-render, thanks to the fact that its ``content`` prop
changed.

.. note::

    If you change a ``LiveProp`` of a child component on the *server*
    (e.g. during re-rendering or via an action), that change will
    *not* be reflected on any parent components that share that model.

You can also specify the name of the child prop with the ``parentProp:childProp``
syntax. The following is the same as above:

.. code-block:: html+twig

    <!-- same as dataModel: 'content' -->
    {{ component('TextareaField', {
        dataModel: 'content:value',
    }) }}

If your child component has multiple models, separate each with a space:

.. code-block:: twig

    {{ component('TextareaField', {
        dataModel: 'user.firstName:first user.lastName:last',
    }) }}

In this case, the child component will receive ``first`` and ``last``
props. And, when those update, the ``user.firstName`` and ``user.lastName``
models will be updated on the parent.

Full Embedded Component Example
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's look at a full, complex example of an embedded component. Suppose
you have an ``EditPost``::

    namespace App\Twig\Components;

    use App\Entity\Post;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent]
    final class EditPost extends AbstractController
    {
        #[LiveProp(writable: ['title', 'content'])]
        public Post $post;

        #[LiveAction]
        public function save(EntityManagerInterface $entityManager)
        {
            $entityManager->flush();

            return $this->redirectToRoute('some_route');
        }
    }

And a ``MarkdownTextarea``::

    namespace App\Twig\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent]
    final class MarkdownTextarea
    {
        #[LiveProp]
        public string $label;

        #[LiveProp]
        public string $name;

        #[LiveProp(writable: true)]
        public string $value = '';
    }

In the ``EditPost`` template, you render the
``MarkdownTextarea``:

.. code-block:: html+twig

    {# templates/components/EditPost.html.twig #}
    <div {{ attributes }}>
        <form data-model="on(change)|*">
            <input
                type="text"
                name="post[title]"
                value="{{ post.title }}"
            >

            {{ component('MarkdownTextarea', {
                name: 'post[content]',
                dataModel: 'value:post.content',
                label: 'Content',
            }) }}

            <button
                data-action="live#action"
                data-action-name="save"
            >Save</button>
        </form>
    </div>

.. code-block:: html+twig

    <div {{ attributes }} class="mb-3">
        <textarea
            name="{{ name }}"
            data-model="value"
        ></textarea>

        <div class="markdown-preview">
            {{ value|markdown_to_html }}
        </div>
    </div>

Notice that ``MarkdownTextarea`` allows a dynamic ``name``
attribute to be passed in. This makes that component re-usable in any
form.

Rendering Quirks with List of Embedded Components
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Imagine your component renders a list of child components and
the list changes as the user types into a search box... or by clicking
"delete" on an item. In this case, the wrong children may be removed
or existing child components may not disappear when they should.

.. versionadded:: 2.8

    The ``key`` prop was added in Symfony UX Live Component 2.8.

To fix this, add a ``key`` prop to each child component that's unique
to that component:

.. code-block:: twig

    {# templates/components/Invoice.html.twig #}
    {% for lineItem in lineItems %}
        {{ component('invoice_line_item', {
            productId: lineItem.productId,
            key: lineItem.id,
        }) }}
    {% endfor %}

The ``key`` will be used to generate a ``data-live-id`` attribute,
which will be used to identify each child component. You can
also pass in a ``data-live-id`` attribute directly, but ``key`` is
a bit more convenient.

Advanced Functionality
----------------------

.. _`smart-rerender-algorithm`:

The Smart Re-Render Algorithm
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When a component re-renders, the new HTML is "morphed" onto the existing
elements on the page. For example, if the re-render includes a new ``class``
on an existing element, that class will be added to that element.

.. versionadded:: 2.8

    The smart re-render algorithm was introduced in LiveComponent 2.8.

The rendering system is also smart enough to know when an element was changed
by something *outside* of the LiveComponents system: e.g. some JavaScript
that added a class to an element. In this case, the class will be preserved
when the component re-renders.

The system doesn't handle every edge case, so here are some things to keep in mind:

* If JavaScript changes an attribute on an element, that change is **preserved**.
* If JavaScript adds a new element, that element is **preserved**.
* If JavaScript removes an element that was originally rendered by the component,
  that change will be **lost**: the element will be re-added during the next re-render.
* If JavaScript changes the text of an element, that change is **lost**: it will
  be restored to the text from the server during the next re-render.
* If an element is moved from one location in the component to another,
  that change is **lost**: the element will be re-added in its original location
  during the next re-render.

Skipping Updating Certain Elements
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have an element inside a component that you do *not* want to change
when your component re-renders, you can add a ``data-live-ignore`` attribute:

.. code-block:: html

    <input name="favorite_color" data-live-ignore>

But you should need this rarely if ever. Even if you write JavaScript that modifies
an element, that changes is preserved (see :ref:`smart-rerender-algorithm`).

.. note::

    To *force* an ignored element to re-render, give its parent element a
    ``data-live-id`` attribute. During a re-render, if this value changes, all
    of the children of the element will be re-rendered, even those with ``data-live-ignore``.

Define another route for your Component
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.7

    The ``route`` option  was added in LiveComponents 2.7.

The default route for live components is ``/components/{_live_component}/{_live_action}``.
Sometimes it may be useful to customize this URL - e.g. so that the component lives
under a specific firewall.

To use a different route, first declare it:

.. code-block:: yaml

    # config/routes.yaml
    live_component_admin:
        path: /admin/_components/{_live_component}/{_live_action}
        defaults:
            _live_action: 'get'

Then specify this new route on your component:

.. code-block:: diff

    // src/Components/RandomNumber.php
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    - #[AsLiveComponent]
    + #[AsLiveComponent(route: 'live_component_admin')]
      class RandomNumber
      {
          use DefaultActionTrait;
      }

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

However it is currently considered `experimental`_, meaning it is not
bound to Symfony's BC policy for the moment.

.. _`TwigComponent`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`Livewire`: https://laravel-livewire.com
.. _`Phoenix LiveView`: https://hexdocs.pm/phoenix_live_view/Phoenix.LiveView.html
.. _`Twig Component`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`Twig Component mount documentation`: https://symfony.com/bundles/ux-twig-component/current/index.html#the-mount-method
.. _`Symfony form`: https://symfony.com/doc/current/forms.html
.. _`experimental`: https://symfony.com/doc/current/contributing/code/experimental.html
.. _`dependent form fields`: https://ux.symfony.com/live-component/demos/dependent-form-fields
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
.. _`localizes its URLs`: https://symfony.com/doc/current/translation/locale.html#translation-locale-url
.. _`attributes variable`: https://symfony.com/bundles/ux-twig-component/current/index.html#component-attributes
.. _`CollectionType`: https://symfony.com/doc/current/form/form_collections.html
.. _`the traditional collection type`: https://symfony.com/doc/current/form/form_themes.html#fragment-naming-for-collections
.. _`How to Work with Form Themes`: https://symfony.com/doc/current/form/form_themes.html
.. _`Symfony's built-in form theming techniques`: https://symfony.com/doc/current/form/form_themes.html
