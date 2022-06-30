Live Components
===============

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Live components work with the `TwigComponent`_ library
to give you the power to automatically update your Twig components on
the frontend as the user interacts with them. Inspired by
`Livewire`_ and `Phoenix LiveView`_.

A real-time product search component might look like this::

    // src/Components/ProductSearchComponent.php
    namespace App\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent('product_search')]
    class ProductSearchComponent
    {
        use DefaultActionTrait;

        #[LiveProp(true)]
        public string $query = '';

        private ProductRepository $productRepository;

        public function __construct(ProductRepository $productRepository)
        {
            $this->productRepository = $productRepository;
        }

        public function getProducts(): array
        {
            // example method that returns an array of Products
            return $this->productRepository->search($this->query);
        }
    }

.. code-block:: twig

    {# templates/components/product_search.html.twig #}
    <div {{ attributes }}>
        <input
            type="search"
            name="query"
            value="{{ query }}"
            data-action="live#update"
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

As a user types into the box, the component will automatically re-render
and show the new results!

Want a demo? Check out https://github.com/weaverryan/live-demo.

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

That's it! We're ready!

Making your Component "Live"
----------------------------

If you haven't already, check out the `Twig Component`_
documentation to get the basics of Twig components.

Suppose you've already built a basic Twig component::

    // src/Components/RandomNumberComponent.php
    namespace App\Components;

    use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

    #[AsTwigComponent('random_number')]
    class RandomNumberComponent
    {
        public function getRandomNumber(): int
        {
            return rand(0, 1000);
        }
    }

.. code-block:: twig

    {# templates/components/random_number.html.twig #}
    <div>
        <strong>{{ this.randomNumber }}</strong>
    </div>

To transform this into a "live" component (i.e. one that can be
re-rendered live on the frontend), replace the component's
``AsTwigComponent`` attribute with ``AsLiveComponent`` and add the
``DefaultActionTrait``:

.. code-block:: diff

      // src/Components/RandomNumberComponent.php

    - use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
    + use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    + use Symfony\UX\LiveComponent\DefaultActionTrait;

    - #[AsTwigComponent('random_number')]
    + #[AsLiveComponent('random_number')]
      class RandomNumberComponent
      {
    +     use DefaultActionTrait;
      }

Then, in the template, make sure there is *one* HTML element around your
entire component and use the ``{{ attributes }}`` variable to initialize
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

.. code-block:: twig

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

LiveProps: Stateful Component Properties
----------------------------------------

Let's make our component more flexible by adding ``$min`` and ``$max``
properties::

    // src/Components/RandomNumberComponent.php
    namespace App\Components;

    // ...
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent('random_number')]
    class RandomNumberComponent
    {
        #[LiveProp]
        public int $min = 0;

        #[LiveProp]
        public int $max = 1000;

        public function getRandomNumber(): int
        {
            return rand($this->min, $this->max);
        }

        // ...
    }

With this change, we can control the ``$min`` and ``$max`` properties
when rendering the component:

.. code-block:: twig

    {{ component('random_number', { min: 5, max: 500 }) }}

But what's up with those ``LiveProp`` attributes? A property with the
``LiveProp`` attribute becomes a "stateful" property for this component.
In other words, each time we click the "Generate a new number!" button,
when the component re-renders, it will *remember* the original values
for the ``$min`` and ``$max`` properties and generate a random number
between 5 and 500. If you forgot to add ``LiveProp``, when the component
re-rendered, those two values would *not* be set on the object.

In short: LiveProps are "stateful properties": they will always be set
when rendering. Most properties will be LiveProps, with common
exceptions being properties that hold services (these don't need to be
stateful because they will be autowired each time before the component
is rendered) and `properties used for computed properties`_.

Component Attributes
--------------------

.. versionadded:: 2.1

    Component attributes were added in TwigComponents 2.1.

`Component attributes`_ allows you to render your components with extra
props that are are converted to html attributes and made available in
your component's template as an ``attributes`` variable. When used on
live components, these props are persisted between renders.

When rendering your component, you can pass html attributes as props and
these will be added to ``attributes``:

.. code-block:: twig

    {{ component('random_number', { min: 5, max: 500, class: 'widget', style: 'color: black;' }) }}

    {# renders as: #}
    <div class="widget" style="color: black;" <!-- other live attributes -->>
        <!-- ... -->

data-action="live#update": Re-rendering on LiveProp Change
----------------------------------------------------------

Could we allow the user to *choose* the ``$min`` and ``$max`` values and
automatically re-render the component when they do? Definitely! And
*that* is where live components really shine.

Let's add two inputs to our template:

.. code-block:: twig

    {# templates/components/random_number.html.twig #}
    <div {{ attributes }}>
        <input
            type="number"
            value="{{ min }}"
            data-model="min"
            data-action="live#update"
        >

        <input
            type="number"
            value="{{ max }}"
            data-model="max"
            data-action="live#update"
        >

        Generating a number between {{ min }} and {{ max }}
        <strong>{{ this.randomNumber }}</strong>
    </div>

Notice the ``data-action="live#update"`` on each ``input``. When the
user types, live components reads the ``data-model`` attribute
(e.g. ``min``) and re-renders the component using the *new* value for
that field! Yes, as you type in a box, the component automatically
updates to reflect the new number!

Well, actually, we're missing one step. By default, a ``LiveProp`` is
"read only". For security purposes, a user cannot change the value of a
``LiveProp`` and re-render the component unless you allow it with the
``writable=true`` option:

.. code-block:: diff

      // src/Components/RandomNumberComponent.php
      // ...

      class RandomNumberComponent
      {
    -     #[LiveProp]
    +     #[LiveProp(writable: true)]
          public int $min = 0;

    -     #[LiveProp]
    +     #[LiveProp(writable: true)]
          public int $max = 1000;

          // ...
      }

Now it works: as you type into the ``min`` or ``max`` boxes, the
component will re-render with a new random number between that range!

Debouncing
~~~~~~~~~~

If the user types 5 characters really quickly into an ``input``, we
don't want to send 5 Ajax requests. Fortunately, the ``live#update``
method has built-in debouncing: it waits for a 150ms pause before
sending an Ajax request to re-render. This is built in, so you don't
need to think about it.

Lazy Updating on "blur" or "change" of a Field
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes, you might want a field to re-render only after the user has
changed an input *and* moved to another field. Browsers dispatch a
``change`` event in this situation. To re-render when this event
happens, add it to the ``data-action`` call:

.. code-block:: diff

      <input
          type="number"
          value="{{ max }}"
          data-model="max"
    -     data-action="live#update"
    +     data-action="change->live#update"
      >

The ``data-action="change->live#update"`` syntax is standard Stimulus
syntax, which says:

   When the "change" event occurs, call the ``update`` method on the
   ``live`` controller.

.. _deferring-a-re-render-until-later:

Deferring a Re-Render Until Later
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Other times, you might want to update the internal value of a property,
but wait until later to re-render the component (e.g. until a button is
clicked). To do that, use the ``updateDefer`` method:

.. code-block:: diff

      <input
          type="number"
          value="{{ max }}"
          data-model="max"
    -     data-action="live#update"
    +     data-action="live#updateDefer"
      >

Now, as you type, the ``max`` "model" will be updated in JavaScript, but
it won't, yet, make an Ajax call to re-render the component. Whenever
the next re-render *does* happen, the updated ``max`` value will be
used.

Using name="" instead of data-model
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of communicating the property name of a field via
``data-model``, you can communicate it via the standard ``name``
property. The following code works identically to the previous example:

.. code-block:: diff

      <div {{ attributes }}>
          <input
              type="number"
              value="{{ min }}"
    -         data-model="min"
    +         name="min"
              data-action="live#update"
          >

          // ...
      </div>

If an element has *both* ``data-model`` and ``name`` attributes, the
``data-model`` attribute takes precedence.

Loading States
--------------

Often, you'll want to show (or hide) an element while a component is
re-rendering or an :ref:`action <actions>` is processing. For example:

.. code-block:: twig

    <!-- show only when the component is loading -->
    <span data-loading>Loading</span>

    <!-- equivalent, longer syntax -->
    <span data-loading="show">Loading</span>

Or, to *hide* an element while the component is loading:

.. code-block:: twig

    <!-- hide when the component is loading -->
    <span
        data-loading="hide"
    >Saved!</span>

Adding and Removing Classes or Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of hiding or showing an entire element, you could add or remove
a class:

.. code-block:: twig

    <!-- add this class when loading -->
    <div data-loading="addClass(opacity-50)">...</div>

    <!-- remove this class when loading -->
    <div data-loading="removeClass(opacity-50)">...</div>

    <!-- add multiple classes when loading -->
    <div data-loading="addClass(opacity-50 disabled)">...</div>

Sometimes you may want to add or remove an attribute when loading. That
can be accomplished with ``addAttribute`` or ``removeAttribute``:

.. code-block:: twig

    <!-- add the "disabled" attribute when loading -->
    <div data-loading="addAttribute(disabled)">...</div>

You can also combine any number of directives by separating them with a
space:

.. code-block:: twig

    <div data-loading="addClass(opacity-50) addAttribute(disabled)">...</div>

Finally, you can add the ``delay`` modifier to not trigger the loading
changes until loading has taken longer than a certain amount of time:

.. code-block:: twig

    <!-- Add class after 200ms of loading -->
    <div data-loading="delay|addClass(opacity-50)">...</div>

    <!-- Show after 200ms of loading -->
    <div data-loading="delay|show">Loading</div>

    <!-- Show after 500ms of loading -->
    <div data-loading="delay(500)|show">Loading</div>

.. _actions:

Actions
-------

Live components require a single "default action" that is used to
re-render it. By default, this is an empty ``__invoke()`` method and can
be added with the ``DefaultActionTrait``. Live components are actually
Symfony controllers so you can add the normal controller
attributes/annotations (ie ``@Cache``/``@Security``) to either the
entire class just a single action.

You can also trigger custom actions on your component. Let's pretend we
want to add a "Reset Min/Max" button to our "random number" component
that, when clicked, sets the min/max numbers back to a default value.

First, add a method with a ``LiveAction`` attribute above it that does
the work::

    // src/Components/RandomNumberComponent.php
    namespace App\Components;

    // ...
    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    class RandomNumberComponent
    {
        // ...

        #[LiveAction]
        public function resetMinMax()
        {
            $this->min = 0;
            $this->max = 1000;
        }

        // ...
    }

To call this, add ``data-action="live#action"`` and ``data-action-name``
to an element (e.g. a button or form):

.. code-block:: twig

    <button
        data-action="live#action"
        data-action-name="resetMinMax"
    >Reset Min/Max</button>

Done! When the user clicks this button, a POST request will be sent that
will trigger the ``resetMinMax()`` method! After calling that method,
the component will re-render like normal, using the new ``$min`` and
``$max`` properties!

You can also add several "modifiers" to the action:

.. code-block:: twig

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
^^^^^^^^^^^^^^^^^^

One really neat thing about component actions is that they are *real*
Symfony controllers. Internally, they are processed identically to a
normal controller method that you would create with a route.

This means that, for example, you can use action autowiring::

    // src/Components/RandomNumberComponent.php
    namespace App\Components;

    // ...
    use Psr\Log\LoggerInterface;

    class RandomNumberComponent
    {
        // ...

        #[LiveAction]
        public function resetMinMax(LoggerInterface $logger)
        {
            $this->min = 0;
            $this->max = 1000;
            $logger->debug('The min/max were reset!');
        }

        // ...
    }

Actions & Arguments
^^^^^^^^^^^^^^^^^^^

.. versionadded:: 2.1

    The ability to pass arguments to actions was added in version 2.1.

You can also provide custom arguments to your action::

.. code-block:: twig
    <form>
        <button data-action="live#action" data-action-name="addItem(id={{ item.id }}, name=CustomItem)">Add Item</button>
    </form>

In component for custom arguments to be injected we need to use `#[LiveArg()]` attribute, otherwise it would be
ignored. Optionally you can provide `name` argument like: `[#LiveArg('itemName')]` so it will use custom name from
args but inject to your defined parameter with another name.::

    // src/Components/ItemComponent.php
    namespace App\Components;

    // ...
    use Symfony\UX\LiveComponent\Attribute\LiveArg;
    use Psr\Log\LoggerInterface;

    class ItemComponent
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

    #[AsLiveComponent('my_live_component', csrf: false)]
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

    // src/Components/RandomNumberComponent.php
    namespace App\Components;

    // ...
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class RandomNumberComponent extends AbstractController
    {
        // ...

        #[LiveAction]
        public function resetMinMax()
        {
            // ...

            $this->addFlash('success', 'Min/Max have been reset!');

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
        /**
         * #[Route('/admin/post/{id}/edit', name: 'app_post_edit')]
         */
        public function edit(Request $request, Post $post): Response
        {
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('app_post_index');
            }

            // renderForm() is new in Symfony 5.3.
            // Use render() and call $form->createView() if on a lower version
            return $this->renderForm('post/edit.html.twig', [
                'post' => $post,
                'form' => $form,
            ]);
        }
    }

Great! In the template, instead of rendering the form, let's render a
``post_form`` component that we will create next:

.. code-block:: twig

    {# templates/post/edit.html.twig #}

    {% extends 'base.html.twig' %}

    {% block body %}
        <h1>Edit Post</h1>

        {{ component('post_form', {
            post: post,
            form: form
        }) }}
    {% endblock %}

Ok: time to build that ``post_form`` component! The Live Components
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

    #[AsLiveComponent('post_form')]
    class PostFormComponent extends AbstractController
    {
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
        public ?Post $post = null;

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

.. code-block:: twig

    {# templates/components/post_form.html.twig #}
    <div
        {{ attributes }}
        {#
            Automatically catch all "change" events from the fields
            below and re-render the component.

            Another common value is "input", which renders whenever
            the "input" event fires (e.g. as you type in a field).
            Note: if you use "input", Symfony's form system trims empty
            spaces. This means that if the user types a space, then waits,
            the re-render will remove the space. Set the "trim" option
            to false on any fields with this problem.
        #}
        data-action="change->live#update"
    >
        {{ form_start(form) }}
            {{ form_row(form.title) }}
            {{ form_row(form.slug) }}
            {{ form_row(form.content) }}

            <button>Save</button>
        {{ form_end(form) }}
    </div>

Mostly, this is a pretty boring template! It includes the normal
``attributes`` and then you render the form however you want.

But the result is incredible! As you finish changing each field, the
component automatically re-renders - including showing any validation
errors for that field! Amazing!

This is possible thanks to a few interesting pieces:

-  ``data-action="change->live#update"``: instead of adding
   ``data-action`` to *every* field, you can place this on a parent
   element. Thanks to this, as you change or type into fields (i.e. the
   ``input`` event), the model for that field will update and the
   component will re-render.

-  The fields in our form do not have a ``data-model=""`` attribute. But
   that's ok! When that is absent, the ``name`` attribute is used
   instead. ``ComponentWithFormTrait`` has a modifiable ``LiveProp``
   that captures these and submits the form using them. That's right:
   each render time the component re-renders, the form is *submitted*
   using the values. However, if a field has not been modified yet by
   the user, its validation errors are cleared so that they aren't
   rendered.

Handling "Cannot dehydrate an unpersisted entity" Errors.
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you're building a form to create a *new* entity, then when you render
the component, you may be passing in a new, non-persisted entity.

For example, imagine you create a ``new Post()`` in your controller,
pass this "not-yet-persisted" entity into your template as a ``post``
variable and pass *that* into your component:

.. code-block:: twig

    {{ component('post_form', {
        post: post,
        form: form
    }) }}

If you do this, you'll likely see this error:

    Cannot dehydrate an unpersisted entity
    App\Entity\Post. If you want to allow
    this, add a dehydrateWith= option to LiveProp

The problem is that the Live component system doesn't know how to
transform this object into something that can be sent to the frontend,
called "dehydration". If an entity has already been saved to the
database, its "id" is sent to the frontend. But if the entity hasn't
been saved yet, that's not possible.

The solution is to pass ``null`` into your component instead of a
non-persisted entity object. If you need to, you can re-create your
``new Post()`` inside of your component:

.. code-block:: diff

      {{ component('post_form', {
    -     post: post,
    +     post: post.id ? post : null,
          form: form
      }) }}

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

Notice that, while we *could* add a ``save()`` :ref:`component action <actions>` that handles the form submit through the component,
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

.. code-block:: twig

    {# templates/post/edit.html.twig #}

    <h1>Edit Post</h1>

    {{ component('post_form', {
        post: post
    }) }}

When you do *not* pass a ``form`` into a component that uses
``ComponentWithFormTrait``, the form will be created for you
automatically. Let's add the ``save()`` action to the component::

    // ...
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    class PostFormComponent extends AbstractController
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

    {# templates/components/post_form.html.twig #}
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

.. note::

    Make sure that each time the user changes a field, you update the
    component's model. If you don't do this, when you trigger the action, it
    will *not* contain the form's data because the data in the fields and the
    component's data will be out of sync.

An easy way to accomplish this (explained more in the :ref:`Forms <forms>`
section above) is to add:

.. code-block:: diff

      <div
          {{ attributes }}
    +     data-action="change->live#update"
      >

Using Actions to Change your Form: CollectionType
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony's `CollectionType`_ can be used to embed a collection of
embedded forms including allowing the user to dynamically add or remove
them. Live components can accomplish make this all possible while
writing zero JavaScript.

For example, imagine a "Blog Post" form with an embedded "Comment" forms
via the ``CollectionType``::

    namespace App\Form;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\CollectionType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use App\Entity\BlogPost;

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

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\FormInterface;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\ComponentWithFormTrait;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use App\Entity\BlogPost;
    use App\Entity\Comment;
    use App\Form\BlogPostFormType;

    #[AsLiveComponent('blog_post_collection_type')]
    class BlogPostCollectionTypeComponent extends AbstractController
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

.. code-block:: twig

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

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
    use App\Entity\BlogPost;

    class BlogPostFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder
                ->add('title', TextType::class)
                // ...
                ->add('comments', LiveCollectionType::class, [
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

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\Form\FormInterface;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\LiveComponent\LiveCollectionTrait;
    use App\Entity\BlogPost;
    use App\Form\BlogPostFormType;

    #[AsLiveComponent('blog_post_collection_type')]
    class BlogPostCollectionTypeComponent extends AbstractController
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

.. code-block:: twig

    <div {{ attributes }} data-action="change->live#update">
        {{ form(form) }}
    </div>

The ``add`` and ``delete`` buttons rendered as separate ``ButtonType`` form
types and can be customized like a normal form type via the ``live_collection_button_add``
and ``live_collection_button_delete`` respectively:

.. code-block:: twig

    {% block live_collection_button_add_widget %}
        {% set attr = attr|merge({'class': attr.class|default('btn btn-ghost')}) %}
        {% set translation_domin = false %}
        {% set label_html = true %}
        {%- set label -%}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ 'form.collection.button.add.label'|trans({}, 'forms') }}
        {%- endset -%}
        {{ block('button_widget') }}
    {% endblock live_collection_button_add_widget %}

Modifying Embedded Properties with the "exposed" Option
-------------------------------------------------------

If your component will render a form, you don't need to use the Symfony
form component. Let's build an ``EditPostComponent`` without a form.
This will need one ``LiveProp``: the ``Post`` object that is being
edited::

    namespace App\Twig\Components;

    use App\Entity\Post;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent('edit_post')]
    class EditPostComponent
    {
        #[LiveProp]
        public Post $post;
    }

In the template, let's render an HTML form *and* a "preview" area where
the user can see, as they type, what the post will look like (including
rendered the ``content`` through a Markdown filter from the
``twig/markdown-extra`` library):

.. code-block:: twig

    <div {{ attributes }}>
        <input
            type="text"
            value="{{ post.title }}"
            data-model="post.title"
            data-action="live#update"
        >

           <textarea
               data-model="post.content"
               data-action="live#update"
           >{{post.content}}</textarea>

            <div className="markdown-preview" data-loading="addClass(low-opacity)">
                <h3>{{post.title}}</h3>
                {{post.content | markdown_to_html}}
            </div>
    </div>

This is pretty straightforward, except for one thing: the ``data-model``
attributes aren't targeting properties on the component class itself,
they're targeting *embedded* properties within the ``$post`` property.

Out-of-the-box, modifying embedded properties is *not* allowed. However,
you can enable it via the ``exposed`` option:

.. code-block:: diff

      // ...

      class EditPostComponent
      {
    -     #[LiveProp]
    +     #[LiveProp(exposed: ['title', 'content'])]
          public Post $post;

          // ...
      }

With this, both the ``title`` and the ``content`` properties of the
``$post`` property *can* be modified by the user. However, notice that
the ``LiveProp`` does *not* have ``writable=true``. This means that
while the ``title`` and ``content`` properties can be changed, the
``Post`` object itself **cannot** be changed. In other words, if the
component was originally created with a Post object with id=2, a bad
user could *not* make a request that renders the component with id=3.
Your component is protected from someone changing to see the form for a
different ``Post`` object, unless you added ``writable=true`` to this
property.

Validation (without a Form)
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    If your component :ref:`contains a form <forms>`, then validation
    is built-in automatically. Follow those docs for more details.

If you're building some sort of form *without* using Symfony's form
component, you *can* still validate your data.

First use the ``ValidatableComponentTrait`` and add any constraints you
need::

    use App\Entity\User;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\ValidatableComponentTrait;
    use Symfony\Component\Validator\Constraints as Assert;

    #[AsLiveComponent('edit_user')]
    class EditUserComponent
    {
        use ValidatableComponentTrait;

        #[LiveProp(exposed: ['email', 'plainPassword'])]
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
track of which models have been updated
(e.g. ``data-action="live#update"``) and only stores the errors for
those fields on re-render.

You can also trigger validation of your *entire* object manually in an
action::

    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    #[AsLiveComponent('edit_user')]
    class EditUserComponent
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
re-rendered. In your template, render errors using the ``getError()``
method:

.. code-block:: twig

    {% if this.getError('post.content') %}
        <div class="error">
            {{ this.getError('post.content').message }}
        </div>
    {% endif %}
    <textarea
        data-model="post.content"
        data-action="live#update"
        class="{{ this.getError('post.content') ? 'has-error' : '' }}"
    >{{ post.content }}</textarea>

Once a component has been validated, the component will "rememeber" that
it has been validated. This means that, if you edit a field and the
component re-renders, it will be validated again.

Real Time Validation
--------------------

As soon as you enable validation, each field will automatically be
validated when its model is updated. For example, if you want a single
field to be validated "on change" (when you change the field and then
blur the field), update the model via the ``change`` event:

.. code-block:: twig

    <textarea
        data-model="post.content"
        data-action="change->live#update"
        class="{{ this.getError('post.content') ? 'has-error' : '' }}"
    >{{ post.content }}</textarea>

When the component re-renders, it will signal to the server that this
one field should be validated. Like with normal validation, once an
individual field has been validated, the component "remembers" that, and
re-validates it on each render.

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

.. code-block:: twig

    <div
        {{ attributes }}
        data-poll="delay(500)|$render"
    >

You can also trigger a specific "action" instead of a normal re-render:

.. code-block:: twig

    <div
        {{ attributes }}

        data-poll="save"
        {#
        Or add a delay() modifier:
        data-poll="delay(2000)|save"
        #}
    >

Nested Components
-----------------

Need to nest one live component inside another one? No problem! As a
rule of thumb, **each component exists in its own, isolated universe**.
This means that nesting one component inside another could be really
simple or a bit more complex, depending on how inter-connected you want
your components to be.

Here are a few helpful things to know:

Each component re-renders independent of one another
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If a parent component re-renders, the child component will *not* (most
of the time) be updated, even though it lives inside the parent. Each
component is its own, isolated universe.

But this is not always what you want. For example, suppose you have a
parent component that renders a form and a child component that renders
one field in that form. When you click a "Save" button on the parent
component, that validates the form and re-renders with errors -
including a new ``error`` value that it passes into the child:

.. code-block:: twig

    {# templates/components/post_form.html.twig #}

    {{ component('textarea_field', {
        value: this.content,
        error: this.getError('content')
    }) }}

In this situation, when the parent component re-renders after clicking
"Save", you *do* want the updated child component (with the validation
error) to be rendered. And this *will* happen automatically. Why?
because the live component system detects that the **parent component
has changed how it's rendering the child**.

This may not always be perfect, and if your child component has its own
``LiveProp`` that has changed since it was first rendered, that value
will be lost when the parent component causes the child to re-render. If
you have this situation, use ``data-model-map`` to map that child
``LiveProp`` to a ``LiveProp`` in the parent component, and pass it into
the child when rendering.

Actions, methods and model updates in a child do not affect the parent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Again, each component is its own, isolated universe! For example,
suppose your child component has:

.. code-block:: html

    <button data-action="live#action" data-action-name="save">Save</button>

When the user clicks that button, it will attempt to call the ``save``
action in the *child* component only, even if the ``save`` action
actually only exists in the parent. The same is true for ``data-model``,
though there is some special handling for this case (see next point).

If a child model updates, it will attempt to update the parent model
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Suppose a child component has a:

.. code-block:: html

    <textarea data-model="markdown_value" data-action="live#update">

When the user changes this field, this will *only* update the
``markdown_value`` field in the *child* component… because (yup, we're
saying it again): each component is its own, isolated universe.

However, sometimes this isn't what you want! Sometimes, in addition to
updating the child component's model, you *also* want to update a model
on the *parent* component.

To help with this, whenever a model updates, a ``live:update-model``
event is dispatched. All components automatically listen to this event.
This means that, when the ``markdown_value`` model is updated in the
child component, *if* the parent component *also* has a model called
``markdown_value`` it will *also* be updated. This is done as a
"deferred" update
(i.e. :ref:`updateDefer() <deferring-a-re-render-until-later>`).

If the model name in your child component (e.g. ``markdown_value``) is
*different* than the model name in your parent component
(e.g. ``post.content``), you have two options. First, you can make sure
both are set by leveraging both the ``data-model`` and ``name``
attributes:

.. code-block:: twig

    <textarea
        data-model="markdown_value"
        name="post[content]"
        data-action="live#update"
    >

In this situation, the ``markdown_value`` model will be updated on the
child component (because ``data-model`` takes precedence over ``name``).
But if any parent components have a ``markdown_value`` model *or* a
``post.content`` model (normalized from ``post[content``]`), their model
will also be updated.

A second option is to wrap your child element in a special
``data-model-map`` element:

.. code-block:: twig

    {# templates/components/post_form.html.twig #}

    <div data-model-map="from(markdown_value)|post.content">
        {{ component('textarea_field', {
            value: this.content,
            error: this.getError('content')
        }) }}
    </div>

Thanks to the ``data-model-map``, whenever the ``markdown_value`` model
updates in the child component, the ``post.content`` model will be
updated in the parent component.

.. note::

    If you *change* a ``LiveProp`` of a child component on the server
    (e.g. during re-rendering or via an action), that change will
    *not* be reflected on any parent components that share that model.

Full Embedded Component Example
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's look at a full, complex example of an embedded component. Suppose
you have an ``EditPostComponent``::

    namespace App\Twig\Components;

    use App\Entity\Post;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent('edit_post')]
    final class EditPostComponent extends AbstractController
    {
        #[LiveProp(exposed: ['title', 'content'])]
        public Post $post;

        #[LiveAction]
        public function save(EntityManagerInterface $entityManager)
        {
            $entityManager->flush();

            return $this->redirectToRoute('some_route');
        }
    }

And a ``MarkdownTextareaComponent``::

    namespace App\Twig\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;

    #[AsLiveComponent('markdown_textarea')]
    final class MarkdownTextareaComponent
    {
        #[LiveProp]
        public string $label;

        #[LiveProp]
        public string $name;

        #[LiveProp(writable: true)]
        public string $value = '';
    }

In the ``EditPostComponent`` template, you render the
``MarkdownTextareaComponent``:

.. code-block:: twig

    {# templates/components/edit_post.html.twig #}
    <div {{ attributes }}>
        <input
            type="text"
            name="post[title]"
            data-action="live#update"
            value="{{ post.title }}"
        >

        {{ component('markdown_textarea', {
            name: 'post[content]',
            label: 'Content',
            value: post.content
        }) }}

        <button
            data-action="live#action"
            data-action-name="save"
        >Save</button>
    </div>

.. code-block:: twig

    <div {{ attributes }} class="mb-3">
        <textarea
            name="{{ name }}"
            data-model="value"
            data-action="live#update"
        >{{ value }}</textarea>

        <div class="markdown-preview">
            {{ value|markdown_to_html }}
        </div>
    </div>

Notice that ``MarkdownTextareaComponent`` allows a dynamic ``name``
attribute to be passed in. This makes that component re-usable in any
form. But it also makes sure that when the ``textarea`` changes, both
the ``value`` model in ``MarkdownTextareaComponent`` *and* the
``post.content`` model in ``EditPostcomponent`` will be updated.

Rendering Quirks with List of Embedded Components
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Imagine your component renders a list of embedded components and
that list is updated as the user types into a search box. Most of the
time, this works *fine*. But in some cases, as the list of items
changes, a child component will re-render even though it was there
before *and* after the list changed. This can cause that child component
to lose some state (i.e. it re-renders with its original live props data).

To fix this, add a unique ``id`` attribute to the root component of each
child element. This will helps LiveComponent identify each item in the
list and correctly determine if a re-render is necessary, or not.

Skipping Updating Certain Elements
----------------------------------

Sometimes you may have an element inside a component that you do *not* want to
change whenever your component re-renders. For example, some elements managed by
third-party JavaScript or a form element that is not bound to a model... where you
don't want a re-render to reset the data the user has entered.

To handle this, add the ``data-live-ignore`` attribute to the element:

.. code-block:: html

    <input name="favorite_color" data-live-ignore>

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
.. _`properties used for computed properties`: https://symfony.com/bundles/ux-live-component/current/index.html#computed-properties
.. _`Symfony form`: https://symfony.com/doc/current/forms.html
.. _`experimental`: https://symfony.com/doc/current/contributing/code/experimental.html
.. _`dependent form fields`: https://symfony.com/doc/current/form/dynamic_form_modification.html#dynamic-generation-for-submitted-forms
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
.. _`Component attributes`: https://symfony.com/bundles/ux-twig-component/current/index.html#component-attributes
.. _`CollectionType`: https://symfony.com/doc/current/form/form_collections.html
