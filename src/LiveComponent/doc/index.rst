Live Components
===============

Live components builds on top of the `TwigComponent`_ library
to give you the power to automatically update your Twig components on
the frontend as the user interacts with them. Inspired by
`Livewire`_ and `Phoenix LiveView`_.

If you're not familiar with Twig components yet, it's worth taking a few minutes
to familiarize yourself in the `TwigComponent documentation`_.

A real-time product search component might look like this::

    // src/Twig/Components/ProductSearch.php
    namespace App\Twig\Components;

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

Done! Now render it wherever you want:

.. code-block:: twig

    {{ component('ProductSearch') }}

As a user types into the box, the component will automatically re-render
and show the new results!

Want some demos? Check out https://ux.symfony.com/live-component#demo

Installation
------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-live-component

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

If your project is localized in different languages (either via the `locale route parameter`_
or by `setting the locale in the request`_) add the ``{_locale}`` attribute to
the UX Live Components route definition to keep the locale between re-renders:

.. code-block:: diff

      # config/routes/ux_live_component.yaml
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

    // src/Twig/Components/RandomNumber.php
    namespace App\Twig\Components;

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

      // src/Twig/Components/RandomNumber.php
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

    // src/Twig/Components/RandomNumber.php
    namespace App\Twig\Components;

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
DateTime objects, Doctrine entity objects, DTOs, or array of DTOs.

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

        Generating a number between 0 and {{ max }}
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

      // src/Twig/Components/RandomNumber.php
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

For a form using ``ComponentWithFormTrait``, override the ``getDataModelValue()`` method::

    private function getDataModelValue(): ?string
    {
        return 'norender|*';
    }

.. tip::

    You can also define this value inside Twig::

    .. code-block:: twig

        {{ form_start(form, {attr: {'data-model': 'norender|*'}}) }}

Now, as you type, the ``max`` "model" will be updated in JavaScript, but
it won't, yet, make an Ajax call to re-render the component. Whenever
the next re-render *does* happen, the updated ``max`` value will be
used.

This can be useful along with a button that triggers a render on click:

.. code-block:: html+twig

    <input data-model="norender|coupon">
    <button data-action="live#$render">Apply</button>

Forcing a Re-Render Explicitly
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In some cases, you might want to force a component re-render explicitly. For
example, consider a checkout component that provides a coupon input that must
only be used when clicking on the associated "Apply coupon" button:

.. code-block:: html+twig

    <input data-model="norender|coupon">
    <button data-action="live#$render">Apply coupon</button>

The ``norender`` option on the input ensures that the component won't re-render
when this input changes. The ``live#$render`` action is a special built-in action
that triggers a re-render.

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
``change`` event on the ``data-model`` field). See
:ref:`manually trigger an element change <javascript-manual-element-change>`.

.. _hydration:

LiveProp for Entities & More Complex Data
-----------------------------------------

``LiveProp`` data must be simple scalar values, with a few exception,
like ``DateTime`` objects, enums & Doctrine entity objects. When ``LiveProp``s
are sent to the frontend, they are "dehydrated". When Ajax requests are sent
from the frontend, the dehydrated data is then "hydrated" back into the original.
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

Collection type extraction from the docblock requires the ``phpdocumentor/reflection-docblock``
library. Make sure it is installed in you application:

.. code-block:: terminal

    $ composer require phpdocumentor/reflection-docblock

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

    <input type="checkbox" data-model="foods[]" value="pizza">
    <input type="checkbox" data-model="foods[]" value="tacos">
    <input type="checkbox" data-model="foods[]" value="sushi">

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

Using DTO's on a LiveProp
~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.12

    The automatic (de)hydration of DTO objects was introduced in LiveComponents 2.12.

You can also use a DTO (i.e. data transfer object / any simple class) with LiveProp as long as the property has the correct type::

    class ComponentWithAddressDto
    {
        #[LiveProp]
        public AddressDto $addressDto;
    }

To work with a collection of DTOs, specify the collection type inside PHPDoc::

    class ComponentWithAddressDto
    {
        /**
         * @var AddressDto[]
         */
        #[LiveProp]
        public array $addressDtoCollection;
    }

Collection type extraction from the docblock requires the ``phpdocumentor/reflection-docblock`` library. Make sure it is installed in you application:

.. code-block:: terminal

    $ composer require phpdocumentor/reflection-docblock

Here is how the (de)hydration of DTO objects works:

- All "properties" (public properties or fake properties via
  getter/setter methods) are read & dehydrated. If a property is settable
  but not gettable (or vice versa), an error will be thrown.
- The PropertyAccess component is used to get/set the value, which means
  getter and setter methods are supported, in addition to public properties.
- The DTO cannot have any constructor arguments.

If this solution doesn't fit your need there are two others options to
make this work:

Hydrating with the Serializer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

Hydrating with Methods: hydrateWith & dehydrateWith
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can take full control of the hydration process by setting the ``hydrateWith``
and ``dehydrateWith`` options on ``LiveProp``::

    class ComponentWithAddressDto
    {
        #[LiveProp(hydrateWith: 'hydrateAddress', dehydrateWith: 'dehydrateAddress')]
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

        public function hydrate(mixed $value, string $className): ?object
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
            // then, trigger a re-render to get the fresh HTML
            this.component.render();

            // or call an action
            this.component.action('save', { arg1: 'value1' });
        }
    }

You can also access the ``Component`` object via a special property
on the root component element, though ``getComponent()`` is the
recommended way, as it will work even if the component is not yet
initialized:

.. code-block:: javascript

    const component = document.getElementById('id-of-your-element').__component;
    component.mode = 'editing';

.. _javascript-manual-element-change:

Finally, you can also set the value of a model field directly. However,
be sure to *also* trigger a ``change`` event so that live components is notified
of the change:

.. code-block:: javascript

    const input = document.getElementById('favorite-food');
    input.value = 'sushi';

    input.dispatchEvent(new Event('change', { bubbles: true }));

Adding a Stimulus Controller to your Component Root Element
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.9

    The ability to use the ``defaults()`` method with ``stimulus_controller()``
    was added in TwigComponents 2.9 and requires ``symfony/stimulus-bundle``.
    Previously, ``stimulus_controller()`` was passed to ``attributes.add()``.

To add a custom Stimulus controller to your root component element:

.. code-block:: html+twig

    <div {{ attributes.defaults(stimulus_controller('some-custom', { someValue: 'foo' })) }}>

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

.. note::

    The ``render:started`` and ``render:finished`` events are only dispatched
    when the component is **re**-rendered (via an action or a model change).

The following hooks are available (along with the arguments that are passed):

* ``connect`` args ``(component: Component)``
* ``disconnect`` args ``(component: Component)``
* ``render:started`` args ``(html: string, response: BackendResponse, controls: { shouldRender: boolean })``
* ``render:finished`` args ``(component: Component)``
* ``response:error`` args ``(backendResponse: BackendResponse, controls: { displayError: boolean })``
* ``loading.state:started`` args ``(element: HTMLElement, request: BackendRequest)``
* ``loading.state:finished`` args ``(element: HTMLElement)``
* ``model:set`` args ``(model: string, value: any, component: Component)``

Dispatching Browser/JavaScript Events
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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
    import Modal from 'bootstrap/js/dist/modal';

    export default class extends Controller {
        modal = null;

        initialize() {
            this.modal = Modal.getOrCreateInstance(this.element);
            window.addEventListener('modal:close', () => this.modal.hide());
        }
    }

Just make sure this controller is attached to the modal element:

.. code-block:: html+twig

    <div class="modal fade" {{ stimulus_controller('bootstrap-modal') }}>
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
    <div data-loading="addClass(opacity-50 text-muted)">...</div>

Sometimes you may want to add or remove an HTML attribute when loading. That
can be accomplished with ``addAttribute`` or ``removeAttribute``:

.. code-block:: html+twig

    <!-- add the "disabled" attribute when loading -->
    <div data-loading="addAttribute(disabled)">...</div>

.. caution::

    The ``addAttribute()`` and ``removeAttribute()`` functions only work with
    empty HTML attributes (``disabled``, ``readonly``, ``required``, etc.) and
    not with attributes that define their values (e.g. this won't work: ``addAttribute(style='color: red')``).

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
attributes/annotations (i.e. ``#[Cache]``/``#[Security]``) to either the
entire class just a single action.

You can also trigger custom actions on your component. Let's pretend we
want to add a "Reset Max" button to our "random number" component
that, when clicked, sets the min/max numbers back to a default value.

First, add a method with a ``LiveAction`` attribute above it that does
the work::

    // src/Twig/Components/RandomNumber.php
    namespace App\Twig\Components;

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

.. versionadded:: 2.16

    The ``data-live-action-param`` attribute way of specifying the action
    was added in Live Components 2.16. Previously, this was done with
    ``data-action-name``.

To call this, trigger the ``action`` method on the ``live`` Stimulus
controller and pass ``resetMax`` as a `Stimulus action parameter`_ called
``action``:

.. code-block:: html+twig

    <button
        data-action="live#action"
        data-live-action-param="resetMax"
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
            data-live-action-param="debounce(300)|save"
        >Save</button>
    </form>

The ``debounce(300)`` adds 300ms of "debouncing" before the action is executed.
In other words, if you click really fast 5 times, only one Ajax request will be made!

You can also use the ``live_action`` twig helper function to render the attributes:

.. code-block:: html+twig

    <button {{ live_action('resetMax') }}>Reset Min/Max</button>

    {# with modifiers #}

    <button {{ live_action('save', {}, {'debounce': 300}) }}>Save</button>


Actions & Services
~~~~~~~~~~~~~~~~~~

One really neat thing about component actions is that they are *real*
Symfony controllers. Internally, they are processed identically to a
normal controller method that you would create with a route.

This means that, for example, you can use action autowiring::

    // src/Twig/Components/RandomNumber.php
    namespace App\Twig\Components;

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

.. versionadded:: 2.16

    The ``data-live-{NAME}-param`` attribute way of specifying action
    arguments was added in Live Components 2.16. Previously, this was done
    inside the ``data-action-name`` attribute.

You can also pass arguments to your action by adding each as a
`Stimulus action parameter`_:

.. code-block:: html+twig

    <form>
        <button
            data-action="live#action"
            data-live-action-param="addItem"

            data-live-id-param="{{ item.id }}"
            data-live-item-name-param="CustomItem"
        >Add Item</button>
    </form>

    {# or #}

    <form>
        <button {{ live_action('addItem', {'id': item.id, 'itemName': 'CustomItem' }) }}>Add Item</button>
    </form>

In your component, to allow each argument to be passed, add
the ``#[LiveArg()]`` attribute::

    // src/Twig/Components/ItemList.php
    namespace App\Twig\Components;

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

    // src/Twig/Components/RandomNumber.php
    namespace App\Twig\Components;

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

.. _files:

Uploading files
---------------

.. versionadded:: 2.11

    The ability to upload files to actions was added in version 2.11.

Files aren't sent to the component by default. You need to use a live action
to handle the files and tell the component when the file should be sent:

.. code-block:: html+twig

    <input type="file" name="my_file" />
    <button
        data-action="live#action"
        data-live-action-param="files|my_action"
    />

To send a file (or files) with an action use ``files`` modifier.
Without an argument it will send all pending files to your action.
You can also specify a modifier parameter to choose which files should be upload.


.. code-block:: html+twig

    <p>
        <input type="file" name="my_file" />
        <input type="file" name="multiple[]" multiple />

        {# Send only file from first input #}
        <button data-action="live#action" data-live-action-param="files(my_file)|myAction" />
        {# You can chain modifiers to send multiple files #}
        <button data-action="live#action" data-live-action-param="files(my_file)|files(multiple[])|myAction" />
        {# Or send all pending files #}
        <button data-action="live#action" data-live-action-param="files|myAction" />
    </p>

The files will be available in a regular ``$request->files`` files bag::

    // src/Twig/Components/FileUpload.php
    namespace App\Twig\Components;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    #[AsLiveComponent]
    class FileUpload
    {
        use DefaultActionTrait;

        #[LiveAction]
        public function myAction(Request $request)
        {
            $file = $request->files->get('my_file');
            $multiple = $request->files->all('multiple');

            // Handle files
        }
    }

.. tip::

    Remember that in order to send multiple files from a single input you
    need to specify ``multiple`` attribute on HTML element and end ``name``
    with ``[]``.

.. _validation:

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
        data-action="live#action:prevent"
        data-live-action-param="save"
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

Deferring / Lazy Loading Components
-----------------------------------

When a page loads, all components are rendered immediately. If a component is
heavy to render, you can defer its rendering until after the page has loaded.
This is done by making an Ajax call to load the component's real content either
as soon as the page loads (``defer``) or when the component becomes visible
(``lazy``).

.. note::

    Behind the scenes, your component *is* created & mounted during the initial
    page load, but its template isn't rendered. So keep your heavy work to
    methods in your component (e.g. ``getProducts()``) that are only called
    from the component's template.

Loading "defer" (Ajax on Load)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.13.0

    The ability to defer loading a component was added in Live Components 2.13.

If a component is heavy to render, you can defer rendering it until after
the page has loaded. To do this, add a ``loading="defer"`` attribute:

.. code-block:: html+twig

    {# With the HTML syntax #}
    <twig:SomeHeavyComponent loading="defer" />

.. code-block:: twig

    {# With the component function #}
    {{ component('SomeHeavyComponent', { loading: 'defer' }) }}

This renders an empty ``<div>`` tag, but triggers an Ajax call to render the
real component once the page has loaded.

Loading "lazy" (Ajax when Visible)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.17.0

    The ability to load a component "lazily" was added in Live Components 2.17.

The ``lazy`` option is similar to ``defer``, but it defers the loading of
the component until it's in the viewport. This is useful for components that
are far down the page and are not needed until the user scrolls to them.

To use this, set a ``loading="lazy"`` attribute to your component:

.. code-block:: html+twig

    {# With the HTML syntax #}
    <twig:Acme foo="bar" loading="lazy" />

.. code-block:: twig

    {# With the Twig syntax #}
    {{ component('SomeHeavyComponent', { loading: 'lazy' }) }}

This renders an empty ``<div>`` tag. The real component is only rendered when
it appears in the viewport.

Defer or Lazy?
~~~~~~~~~~~~~~

The ``defer`` and ``lazy`` options may seem similar, but they serve different
purposes:
* ``defer`` is useful for components that are heavy to render but are required
    when the page loads.
* ``lazy`` is useful for components that are not needed until the user scrolls
    to them (and may even never be rendered).

Loading content
~~~~~~~~~~~~~~~

You can define some content to be rendered while the component is loading, either
inside the component template (the ``placeholder`` macro) or from the calling template
(the ``loading-template`` attribute and the ``loadingContent`` block).

.. versionadded:: 2.16.0

    Defining a placeholder macro into the component template was added in Live Components 2.16.0.

In the component template, define a ``placeholder`` macro, outside of the
component's main content. This macro will be called when the component is deferred:

.. code-block:: html+twig

    {# templates/recommended-products.html.twig #}
    <div {{ attributes }}>
        {# This will be rendered when the component is fully loaded #}
        {% for product in this.products %}
            <div>{{ product.name }}</div>
        {% endfor %}
    </div>

    {% macro placeholder(props) %}
        {# This content will (only) be rendered as loading content #}
        <span class="loading-row"></span>
    {% endmacro %}

The ``props`` argument contains the props passed to the component.
You can use it to customize the placeholder content. Let's say your
component shows a certain number of products (defined with the ``size``
prop). You can use it to define a placeholder that shows the same
number of rows:

.. code-block:: html+twig

    {# In the calling template #}
    <twig:RecommendedProducts size="3" loading="defer" />

.. code-block:: html+twig

    {# In the component template #}
    {% macro placeholder(props) %}
        {% for i in 1..props.size %}
            <div class="loading-product">
                ...
            </div>
        {% endfor %}
    {% endmacro %}

To customize the loading content from the calling template, you can use
the ``loading-template`` option to point to a template:

.. code-block:: html+twig

    {# With the HTML syntax #}
    <twig:SomeHeavyComponent loading="defer" loading-template="spinning-wheel.html.twig" />

    {# With the component function #}
    {{ component('SomeHeavyComponent', { loading: 'defer', loading-template: 'spinning-wheel.html.twig' }) }}

Or override the ``loadingContent`` block:

.. code-block:: html+twig

    {# With the HTML syntax #}
    <twig:SomeHeavyComponent loading="defer">
        <twig:block name="loadingContent">Custom Loading Content...</twig:block>
    </twig:SomeHeavyComponent>

    {# With the component tag #}
    {% component SomeHeavyComponent with { loading: 'defer' } %}
        {% block loadingContent %}Loading...{% endblock %}
    {% endcomponent %}

When ``loading-template`` or ``loadingContent`` is defined, the ``placeholder``
macro is ignored.

To change the initial tag from a ``div`` to something else, use the ``loading-tag`` option:

.. code-block:: twig

    {{ component('SomeHeavyComponent', { loading: 'defer', loading-tag: 'span' }) }}

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

Changing the URL when a LiveProp changes
----------------------------------------

.. versionadded:: 2.14

    The ``url`` option was introduced in Live Components 2.14.

If you want the URL to update when a ``LiveProp`` changes, you can do that with the ``url`` option::

    // src/Twig/Components/SearchModule.php
    namespace App\Twig\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    #[AsLiveComponent]
    class SearchModule
    {
        use DefaultActionTrait;

        #[LiveProp(writable: true, url: true)]
        public string $query = '';
    }

Now, when the user changes the value of the ``query`` prop, a query parameter in the URL will be updated to reflect the
new state of your component, for example: ``https://my.domain/search?query=my+search+string``.

If you load this URL in your browser, the ``LiveProp`` value will be initialized using the query string
(e.g. ``my search string``).

.. note::

    The URL is changed via ``history.replaceState()``. So no new entry is added.

Supported Data Types
~~~~~~~~~~~~~~~~~~~~

You can use scalars, arrays and objects in your URL bindings:

============================================  =================================================
JavaScript ``prop`` value                     URL representation
============================================  =================================================
``'some search string'``                      ``prop=some+search+string``
``42``                                        ``prop=42``
``['foo', 'bar']``                            ``prop[0]=foo&prop[1]=bar``
``{ foo: 'bar', baz: 42 }``                   ``prop[foo]=bar&prop[baz]=42``


When a page is loaded with a query parameter that's bound to a ``LiveProp`` (e.g. ``/search?query=my+search+string``),
the value - ``my search string`` - goes through the hydration system before it's set onto the property. If a value can't
be hydrated, it will be ignored.

Multiple Query Parameter Bindings
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can use as many URL bindings as you want in your component. To ensure the state is fully represented in the URL,
all bound props will be set as query parameters, even if their values didn't change.

For example, if you declare the following bindings::

    // ...
    #[AsLiveComponent]
    class SearchModule
    {
        #[LiveProp(writable: true, url: true)]
        public string $query = '';

        #[LiveProp(writable: true, url: true)]
        public string $mode = 'fulltext';

        // ...
    }


And you only set the ``query`` value, then your URL will be updated to
``https://my.domain/search?query=my+query+string&mode=fulltext``.

Controlling the Query Parameter Name
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.17

    The ``as`` option was added in LiveComponents 2.17.


Instead of using the prop's field name as the query parameter name, you can use the ``as`` option in your ``LiveProp``
definition::

    // ...
    use Symfony\UX\LiveComponent\Metadata\UrlMapping;

    #[AsLiveComponent]
    class SearchModule
    {
        #[LiveProp(writable: true, url: new UrlMapping(as: 'q'))]
        public string $query = '';

        // ...
    }

Then the ``query`` value will appear in the URL like ``https://my.domain/search?q=my+query+string``.

If you need to change the parameter name on a specific page, you can leverage the :ref:`modifier <modifier>` option::

    // ...
    use Symfony\UX\LiveComponent\Metadata\UrlMapping;

    #[AsLiveComponent]
    class SearchModule
    {
        #[LiveProp(writable: true, url: true, modifier: 'modifyQueryProp')]
        public string $query = '';

        #[LiveProp]
        public ?string $alias = null;

        public function modifyQueryProp(LiveProp $liveProp): LiveProp
        {
            if ($this->alias) {
                $liveProp = $liveProp->withUrl(new UrlMapping(as: $this->alias));
            }
            return $liveProp;
        }
    }

.. code-block:: html+twig

    <twig:SearchModule alias="q" />

This way you can also use the component multiple times in the same page and avoid collisions in parameter names:

.. code-block:: html+twig

    <twig:SearchModule alias="q1" />
    <twig:SearchModule alias="q2" />

Validating the Query Parameter Values
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Like any writable ``LiveProp``, because the user can modify this value, you should consider adding
:ref:`validation <validation>`. When you bind a ``LiveProp`` to the URL, the initial value is not automatically
validated. To validate it, you have to set up a `PostMount hook`_::

    // ...
    use Symfony\Component\Validator\Constraints as Assert;
    use Symfony\UX\LiveComponent\ValidatableComponentTrait;
    use Symfony\UX\TwigComponent\Attribute\PostMount;

    #[AsLiveComponent]
    class SearchModule
    {
        use ValidatableComponentTrait;

        #[LiveProp(writable: true, url: true)]
        public string $query = '';

        #[LiveProp(writable: true, url: true)]
        #[Assert\NotBlank]
        public string $mode = 'fulltext';

        #[PostMount]
        public function postMount(): void
        {
            // Validate 'mode' field without throwing an exception, so the component can
            // be mounted anyway and a validation error can be shown to the user
            if (!$this->validateField('mode', false)) {
                // Do something when validation fails
            }
        }

        // ...
    }

.. note::

    You can use `validation groups`_ if you want to use specific validation rules only in the PostMount hook.

.. _emit:

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

The Mystical id Attribute
~~~~~~~~~~~~~~~~~~~~~~~~~

The ``id`` attribute is mentioned several times throughout the documentation
to solve various problems. It's usually not needed, but can be the key to solving
certain complex problems. But what is it?

.. note::

    The :ref:`key prop <key-prop>` is used to create a ``id`` attribute
    on child components. So everything in this section applies equally to the
    ``key`` prop.

The ``id`` attribute is a unique identifier for an element or a component.
It's used during the morphing process when a component re-renders: it helps the
`morphing library`_ "connect" elements or components in the existing HTML with the new
HTML.

Skipping Updating Certain Elements
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have an element inside a component that you do *not* want to change
when your component re-renders, you can add a ``data-live-ignore`` attribute:

.. code-block:: html

    <input name="favorite_color" data-live-ignore>

But you should need this rarely if ever. Even if you write JavaScript that modifies
an element, that changes is preserved (see :ref:`smart-rerender-algorithm`).

.. note::

    To *force* an ignored element to re-render, give its parent element an
    ``id`` attribute. During a re-render, if this value changes, all
    of the children of the element will be re-rendered, even those with ``data-live-ignore``.

Overwrite HTML Instead of Morphing
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Normally, when a component re-renders, the new HTML is "morphed" onto the existing
elements on the page. In some rare cases, you may want to simply overwrite the existing
inner HTML of an element with the new HTML instead of morphing it. This can be done by adding a
``data-skip-morph`` attribute:

.. code-block:: html

    <select data-skip-morph>
        <option>...</option>
    </select>

In this case, any changes to the ``<select>`` element attributes will still be
"morphed" onto the existing element, but the inner HTML will be overwritten.

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

    // src/Twig/Components/RandomNumber.php
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\DefaultActionTrait;

    - #[AsLiveComponent]
    + #[AsLiveComponent(route: 'live_component_admin')]
      class RandomNumber
      {
          use DefaultActionTrait;
      }

.. versionadded:: 2.14

    The ``urlReferenceType`` option  was added in LiveComponents 2.14.

You can also control the type of the generated URL:

.. code-block:: diff

      // src/Twig/Components/RandomNumber.php
    + use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
      use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
      use Symfony\UX\LiveComponent\DefaultActionTrait;

    - #[AsLiveComponent]
    + #[AsLiveComponent(urlReferenceType: UrlGeneratorInterface::ABSOLUTE_URL)]
      class RandomNumber
      {
          use DefaultActionTrait;
      }

Add a Hook on LiveProp Update
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.12

    The ``onUpdated`` option was added in LiveComponents 2.12.

If you want to run custom code after a specific LiveProp is updated,
you can do it by adding an ``onUpdated`` option set to a public method name
on the component::

    #[AsLiveComponent]
    class ProductSearch
    {
        #[LiveProp(writable: true, onUpdated: 'onQueryUpdated')]
        public string $query = '';

        // ...

        public function onQueryUpdated($previousValue): void
        {
            // $this->query already contains a new value
            // and its previous value is passed as an argument
        }
    }

As soon as the ``query`` LiveProp is updated, the ``onQueryUpdated()`` method
will be called. The previous value is passed there as the first argument.

If you're allowing object properties to be writable, you can also listen to
the change of one specific key::

    use App\Entity\Post;

    #[AsLiveComponent]
    class EditPost
    {
        #[LiveProp(writable: ['title', 'content'], onUpdated: ['title' => 'onTitleUpdated'])]
        public Post $post;

        // ...

        public function onTitleUpdated($previousValue): void
        {
            // ...
        }
    }

.. _modifier:

Set LiveProp Options Dynamically
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 2.17

    The ``modifier`` option was added in LiveComponents 2.17.


If you need to configure a LiveProp's options dynamically, you can use the ``modifier`` option to use a custom
method in your component that returns a modified version of your LiveProp::


    #[AsLiveComponent]
    class ProductSearch
    {
        #[LiveProp(writable: true, modifier: 'modifyAddedDate')]
        public ?\DateTimeImmutable $addedDate = null;

        #[LiveProp]
        public string $dateFormat = 'Y-m-d';

        // ...

        public function modifyAddedDate(LiveProp $prop): LiveProp
        {
            return $prop->withFormat($this->dateFormat);
        }
    }

Then, when using your component in a template, you can change the date format used for ``$addedDate``:

.. code-block:: twig

    {{ component('ProductSearch', {
        dateFormat: 'd/m/Y'
    }) }}


All ``LiveProp::with*`` methods are immutable, so you need to use their return value as your new LiveProp.

.. caution::

    Avoid relying on props that also use a modifier in other modifiers methods. For example, if the ``$dateFormat``
    property above also had a ``modifier`` option, then it wouldn't be safe to reference it from the ``modifyAddedDate``
    modifier method. This is because the ``$dateFormat`` property may not have been hydrated by this point.


Debugging Components
--------------------

Need to list or debug some component issues.
The `Twig Component debug command`_ can help you.

Test Helper
-----------

.. versionadded:: 2.11

    The test helper was added in LiveComponents 2.11.

For testing, you can use the ``InteractsWithLiveComponents`` trait which
uses Symfony's test client to render and make requests to your components::

    use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
    use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

    class MyComponentTest extends KernelTestCase
    {
        use InteractsWithLiveComponents;

        public function testCanRenderAndInteract(): void
        {
            $testComponent = $this->createLiveComponent(
                name: 'MyComponent', // can also use FQCN (MyComponent::class)
                data: ['foo' => 'bar'],
            );

            // render the component html
            $this->assertStringContainsString('Count: 0', $testComponent->render());

            // call live actions
            $testComponent
                ->call('increase')
                ->call('increase', ['amount' => 2]) // call a live action with arguments
            ;

            $this->assertStringContainsString('Count: 3', $testComponent->render());

            // call live action with file uploads
            $testComponent
                ->call('processUpload', files: ['file' => new UploadedFile(...)]);

            // emit live events
            $testComponent
                ->emit('increaseEvent')
                ->emit('increaseEvent', ['amount' => 2]) // emit a live event with arguments
            ;

            // set live props
            $testComponent
                ->set('count', 99)
            ;

            // Submit form data
            $testComponent
                ->submitForm(['form' => ['input' => 'value']], 'save');

            $this->assertStringContainsString('Count: 99', $testComponent->render());

            // refresh the component
            $testComponent->refresh();

            // access the component object (in its current state)
            $component = $testComponent->component(); // MyComponent

            $this->assertSame(99, $component->count);

            // test a live action that redirects
            $response = $testComponent->call('redirect')->response(); // Symfony\Component\HttpFoundation\Response

            $this->assertSame(302, $response->getStatusCode());

            // authenticate a user ($user is instance of UserInterface)
            $testComponent->actingAs($user);

            // set the '_locale' route parameter (if the component route is localized)  
            $testComponent->setRouteLocale('fr');

            // customize the test client
            $client = self::getContainer()->get('test.client');

            // do some stuff with the client (ie login user via form)

            $testComponent = $this->createLiveComponent(
                name: 'MyComponent',
                data: ['foo' => 'bar'],
                client: $client,
            );
        }
    }

.. note::

    The ``InteractsWithLiveComponents`` trait can only be used in tests that extend
    ``Symfony\Bundle\FrameworkBundle\Test\KernelTestCase``.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

For JavaScript files, the public API (i.e. documented features and exports
from the main JavaScript file) is protected by the backward compatibility
promise. However, any internal implementation in the JavaScript files
(i.e. exports from internal files) is not protected.

.. _`TwigComponent`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _TwigComponent documentation: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`Livewire`: https://livewire.laravel.com
.. _`Phoenix LiveView`: https://hexdocs.pm/phoenix_live_view/Phoenix.LiveView.html
.. _`Twig Component`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`Twig Component mount documentation`: https://symfony.com/bundles/ux-twig-component/current/index.html#the-mount-method
.. _`Symfony form`: https://symfony.com/doc/current/forms.html
.. _`dependent form fields`: https://ux.symfony.com/live-component/demos/dependent-form-fields
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`attributes variable`: https://symfony.com/bundles/ux-twig-component/current/index.html#component-attributes
.. _`CollectionType`: https://symfony.com/doc/current/form/form_collections.html
.. _`the traditional collection type`: https://symfony.com/doc/current/form/form_themes.html#fragment-naming-for-collections
.. _`How to Work with Form Themes`: https://symfony.com/doc/current/form/form_themes.html
.. _`Symfony's built-in form theming techniques`: https://symfony.com/doc/current/form/form_themes.html
.. _`pass content to Twig Components`: https://symfony.com/bundles/ux-twig-component/current/index.html#passing-blocks
.. _`Twig Component debug command`: https://symfony.com/bundles/ux-twig-component/current/index.html#debugging-components
.. _`PostMount hook`: https://symfony.com/bundles/ux-twig-component/current/index.html#postmount-hook
.. _`validation groups`: https://symfony.com/doc/current/form/validation_groups.html
.. _morphing library: https://github.com/bigskysoftware/idiomorph
.. _`locale route parameter`: https://symfony.com/doc/current/translation.html#the-locale-and-the-url
.. _`setting the locale in the request`: https://symfony.com/doc/current/translation.html#translation-locale
.. _`Stimulus action parameter`: https://stimulus.hotwired.dev/reference/actions#action-parameters

Learn more
----------

How to:
    .. toctree::
        :maxdepth: 1

        /form
        /communication