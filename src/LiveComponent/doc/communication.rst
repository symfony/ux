Communication between Components
================================

Nested Components
-----------------

Need to nest one live component inside another one? No problem! As a
rule of thumb, **each component exists in its own, isolated universe**.
This means that if a parent component re-renders, it won't automatically
cause the child to re-render (but it *can* - keep reading). Or, if
a model in a child updates, it won't also update that model in its parent
(but it *can* - keep reading).

The parent-child system is *smart*. And with a few tricks
(:ref:`such as the key prop for lists of embedded components <rendering-quirks-with-list-of-embedded-components>`),
you can make it behave exactly like you need.

.. _child-component-independent-rerender:

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
    different name and set the ``count`` property via a `mount() <https://symfony.com/bundles/ux-twig-component/current/index.html#the-mount-method>`_ method, the
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
can be done by manually giving your component an ``id`` attribute
that will change if the component should be totally re-rendered:

.. code-block:: html+twig

    {# templates/components/TodoList.html.twig #}
    <div {{ attributes }}>
        <!-- ... -->

        {{ component('TodoFooter', {
            count: todos|length,
            id: 'todo-footer-'~todos|length
        }) }}
    </div>

In this case, if the number of todos change, then the ``id``
attribute of the component will also change. This signals that the
component should re-render itself completely, discarding any writable
LiveProp values.

Actions in a child do not affect the parent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Again, each component is its own, isolated universe! For example,
suppose your child component has:

.. code-block:: html

    <button data-action="live#action" data-live-action-param="save">Save</button>

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
.. _update-parent-model:

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
                dataModel: 'post.content:value',
                label: 'Content',
            }) }}

            <button
                data-action="live#action"
                data-live-action-param="save"
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

.. _rendering-loop-of-elements:

Rendering Quirks with List of Elements
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you're rendering a list of elements in your component, to help LiveComponents
understand which element is which between re-renders (i.e. if something re-orders
or removes some of those elements), you can add a ``id`` attribute to
each element

.. code-block:: html+twig

    {# templates/components/Invoice.html.twig #}
    {% for lineItem in lineItems %}
        <div id="{{ lineItem.id }}">
            {{ lineItem.name }}
        </div>
    {% endfor %}

.. _key-prop:

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

    {# templates/components/InvoiceCreator.html.twig #}
    {% for lineItem in invoice.lineItems %}
        {{ component('InvoiceLineItemForm', {
            lineItem: lineItem,
            key: lineItem.id,
        }) }}
    {% endfor %}

The ``key`` will be used to generate an ``id`` attribute,
which will be used to identify each child component. You can
also pass in a ``id`` attribute directly, but ``key`` is
a bit more convenient.

.. _rendering-loop-new-element:

Tricks with a Loop + a "New" Item
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's get fancier. After looping over the current line items, you
decide to render one more component to create a *new* line item.
In that case, you can pass in a ``key`` set to something like ``new_line_item``:

.. code-block:: twig

    {# templates/components/InvoiceCreator.html.twig #}
    // ... loop and render the existing line item components

    {{ component('InvoiceLineItemForm', {
        key: 'new_line_item',
    }) }}

Imagine you also have a ``LiveAction`` inside of ``InvoiceLineItemForm``
that saves the new line item to the database. To be extra fancy,
it emits a ``lineItem:created`` event to the parent::

    // src/Twig/InvoiceLineItemForm.php
    // ...

    #[AsLiveComponent]
    final class InvoiceLineItemForm
    {
        // ...

        #[LiveProp]
        #[Valid]
        public ?InvoiceLineItem $lineItem = null;

        #[PostMount]
        public function postMount(): void
        {
            if (!$this->lineItem) {
                $this->lineItem = new InvoiceLineItem();
            }
        }

        #[LiveAction]
        public function save(EntityManagerInterface $entityManager)
        {
            if (!$this->lineItem->getId()) {
                $this->emit('lineItem:created', $this->lineItem);
            }

            $entityManager->persist($this->lineItem);
            $entityManager->flush();
        }
    }

Finally, the parent ``InvoiceCreator`` component listens to this
so that it can re-render the line items (which will now contain the
newly-saved item)::

    // src/Twig/InvoiceCreator.php
    // ...

    #[AsLiveComponent]
    final class InvoiceCreator
    {
        // ...

        #[LiveListener('lineItem:created')]
        public function addLineItem()
        {
            // no need to do anything here: the component will re-render
        }
    }

This will work beautifully: when a new line item is saved, the ``InvoiceCreator``
component will re-render and the newly saved line item will be displayed along
with the extra ``new_line_item`` component at the bottom.

But something surprising might happen: the ``new_line_item`` component won't
update! It will *keep* the data and props that were there a moment ago (i.e. the
form fields will still have data in them) instead of rendering a fresh, empty component.

Why? When live components re-renders, it thinks the existing ``key: new_line_item``
component on the page is the *same* new component that it's about to render. And
because the props passed into that component haven't changed, it doesn't see any
reason to re-render it.

To fix this, you have two options:

\1) Make the ``key`` dynamic so it will be different after adding a new item:

.. code-block:: twig

    {{ component('InvoiceLineItemForm', {
        key: 'new_line_item_'~lineItems|length,
    }) }}

\2) Reset the state of the ``InvoiceLineItemForm`` component after it's saved::

    // src/Twig/InvoiceLineItemForm.php
    // ...

    #[AsLiveComponent]
    class InvoiceLineItemForm
    {
        // ...

        #[LiveAction]
        public function save(EntityManagerInterface $entityManager)
        {
            $isNew = null === $this->lineItem->getId();

            $entityManager->persist($this->lineItem);
            $entityManager->flush();

            if ($isNew) {
                // reset the state of this component
                $this->emit('lineItem:created', $this->lineItem);
                $this->lineItem = new InvoiceLineItem();
                // if you're using ValidatableComponentTrait
                $this->clearValidation();
            }
        }
    }

.. _passing-blocks:

Passing Content (Blocks) to Components
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Passing content via blocks to Live components works completely the same way you would `pass content to Twig Components`_.
Except with one important difference: when a component is re-rendered, any variables defined only in the
"outside" template will not be available. For example, this won't work:

.. code-block:: twig

    {# templates/some_page.html.twig #}
    {% set message = 'Variables from the outer part of the template are only available during  the initial render' %}

    {% component Alert %}
        {% block content %}{{ message }}{% endblock %}
    {% endcomponent %}

Local variables do remain available:

.. code-block:: twig

    {# templates/some_page.html.twig #}
    {% component Alert %}
        {% block content %}
            {% set message = 'this works during re-rendering!' %}
            {{ message }}
        {% endblock %}
    {% endcomponent %}

Emitting Events
---------------

.. versionadded:: 2.8

    The ability to emit events was added in Live Components 2.8.

Events allow you to communicate between any two components that live
on your page.

Emitting an Event
~~~~~~~~~~~~~~~~~

There are three ways to emit an event:

.. versionadded:: 2.16

    The ``data-live-event-param`` attribute was added in Live Components 2.16.
    Previously, it was called ``data-event``.

1. From Twig:

   .. code-block:: html+twig

       <button
           data-action="live#emit"
           data-live-event-param="productAdded"
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

To listen to an event, add a method with a ``#[LiveListener]`` above it::

    #[LiveProp]
    public int $productCount = 0;

    #[LiveListener('productAdded')]
    public function incrementProductCount()
    {
        $this->productCount++;
    }

Thanks to this, when any other component emits the ``productAdded`` event, an Ajax
call will be made to call this method and re-render the component.

Behind the scenes, event listeners are also ``LiveActions <actions>``, so you can
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
name with ``#[LiveArg]`` in front::

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
        data-live-event-param="productAdded"
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
        data-live-event-param="name(ProductList)|productAdded"
    >

Or, in PHP::

    $this->emit('productAdded', componentName: 'ProductList');

Emitting only to Yourself
.........................

To emit an event to only yourself, use the ``emitSelf()`` method:

.. code-block:: html+twig

    <button
        data-action="live#emitSelf"
        data-live-event-param="productAdded"
    >

Or, in PHP::

    $this->emitSelf('productAdded');
