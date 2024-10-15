Forms in Live Components
========================

A Live Component can also help render a `Symfony form`_, either the entire
form (useful for automatic validation as you type) or just one or some
fields (e.g. a markdown preview for a ``textarea`` or `dependent form fields`_.

Rendering an Entire Form in a Component
---------------------------------------

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

Great! In the template for some page (e.g. an "Edit post" page), render a
``PostForm`` component that we will create next:

.. code-block:: html+twig

    {# templates/post/edit.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <h1>Edit Post</h1>

        {{ component('PostForm', {
            initialFormData: post,
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
         */
        #[LiveProp]
        public ?Post $initialFormData = null;

        protected function instantiateForm(): FormInterface
        {
            // we can extend AbstractController to get the normal shortcuts
            return $this->createForm(PostType::class, $this->initialFormData);
        }
    }

The trait forces you to create an ``instantiateForm()`` method, which is
used each time the component is rendered via AJAX. To recreate the *same*
form as the original, we pass in the ``initialFormData`` property and set it
as a ``LiveProp``.

The template for this component will render the form, which is available
as ``form`` thanks to the trait:

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

That's it! The result is incredible! As you finish changing each field, the
component automatically re-renders - including showing any validation
errors for that field! Amazing!

How this works:

#. The ``ComponentWithFormTrait`` has a ``$formValues`` writable ``LiveProp``
   containing the value for every field in your form.
#. When the user changes a field, that key in ``$formValues`` is updated and
   an Ajax request is sent to re-render.
#. During that Ajax call, the form is submitted using ``$formValues``, the
   form re-renders, and the page is updated.

Build the "New Post" Form Component
-----------------------------------

The previous component can already be used to edit an existing post or create
a new post. For a new post, either pass in a new ``Post`` object to ``initialFormData``,
or omit it entirely to let the ``initialFormData`` property default to ``null``:

.. code-block:: twig

    {# templates/post/new.html.twig #}
    {# ... #}

    {{ component('PostForm', {
        form: form
    }) }}

Submitting the Form via a LiveAction
------------------------------------

The simplest way to handle your form submit is directly in your component via
a :ref:`LiveAction <actions>`::

    // ...
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;

    class PostForm extends AbstractController
    {
        // ...

        #[LiveAction]
        public function save(EntityManagerInterface $entityManager)
        {
            // Submit the form! If validation fails, an exception is thrown
            // and the component is automatically re-rendered with the errors
            $this->submitForm();

            /** @var Post $post */
            $post = $this->getForm()->getData();
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post saved!');

            return $this->redirectToRoute('app_post_show', [
                'id' => $post->getId(),
            ]);
        }
    }

Next, tell the ``form`` element to use this action:

.. code-block:: twig

    {# templates/components/PostForm.html.twig #}
    {# ... #}

    {{ form_start(form, {
        attr: {
            'data-action': 'live#action:prevent',
            'data-live-action-param': 'save'
        }
    }) }}

Now, when the form is submitted, it will execute the ``save()`` method
via Ajax. If the form fails validation, it will re-render with the
errors. And if it's successful, it will redirect.

Submitting with a Normal Symfony Controller
-------------------------------------------

If you prefer, you can submit the form via a Symfony controller. To do
this, create your controller like normal, including the submit logic::

    // src/Controller/PostController.php
    class PostController extends AbstractController
    {
        #[Route('/admin/post/{id}/edit', name: 'app_post_edit')]
        public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
        {
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // save, redirect, etc
            }

            return $this->render('post/edit.html.twig', [
                'post' => $post,
                'form' => $form, // use $form->createView() in Symfony <6.2
            ]);
        }
    }

If validation fails, you'll want the live component to render with the form
errors instead of creating a fresh form. To do that, pass the ``form`` variable
into the component:

.. code-block:: twig

    {# templates/post/edit.html.twig #}
    {{ component('PostForm', {
        initialFormData: post,
        form: form
    }) }}

Using Form Data in a LiveAction
-------------------------------

Each time an Ajax call is made to re-render the live component the form is
automatically submitted using the latest data.

However, there are two important things to know:

#. When a ``LiveAction`` is executed, the form has **not** yet been submitted.
#. The ``initialFormData`` property is **not** updated until after the form is
   submitted.

If you need to access the latest data in a ``LiveAction``, you can manually submit
the form::

    // ...

    #[LiveAction]
    public function save()
    {
        // $this->initialFormData will *not* contain the latest data yet!

        // submit the form
        $this->submitForm();

        // now you can access the latest data
        $post = $this->getForm()->getData();
        // (same as above)
        $post = $this->initialFormData;
    }

.. tip::

    If you don't call ``$this->submitForm()``, it's called automatically
    before the component is re-rendered.

Dynamically Updating the Form In a LiveAction
---------------------------------------------

When an Ajax call is made to re-render the live component (whether that's
due to a model change or a LiveAction), the form is submitted using a
``$formValues`` property from ``ComponentWithFormTrait`` that contains the
latest data from the form.

Sometimes, you need to update something on the form dynamically from a ``LiveAction``.
For example, suppose you have a "Generate Title" button that, when clicked, will
generate a title based on the content of the post.

To do this, you **must** update the ``$this->formValues`` property directly
before the form is submitted::

    // ...

    #[LiveAction]
    public function generateTitle()
    {
        // this works!
        // (the form will be submitted automatically after this method, now with the new title)
        $this->formValues['title'] = '... some auto-generated-title';

        // this would *not* work
        // $this->submitForm();
        // $post = $this->getForm()->getData();
        // $post->setTitle('... some auto-generated-title');
    }

This is tricky. The ``$this->formValues`` property is an array of the raw form
data on the frontend and contains only scalar values (e.g. strings, integers, booleans
and arrays). By updating this property, the form will submit as *if* the user had
typed the new ``title`` into the form. The form will then be re-rendered with the
new data.

.. note::

    If the field you're updating is an object in your code - like an entity object
    corresponding to an ``EntityType`` field - you need to use the value that's
    used on the frontend of your form. For an entity, that's the ``id``::

        $this->formValues['author'] = $author->getId();

Why not just update the ``$post`` object directly? Once you submit the form, the
"form view" (data, errors, etc for the frontend) has already been created. Changing
the ``$post`` object has no effect. Even modifying ``$this->initialFormData``
before submitting the form has no effect: the actual, submitted ``title`` would
override that.

Form Rendering Problems
-----------------------

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

Resetting the Form
------------------

.. versionadded:: 2.10

    The ``resetForm()`` method was added in LiveComponent 2.10.

After submitting a form via an action, you might want to "reset" the form
back to its initial state so you can use it again. Do that by calling
``resetForm()`` in your action instead of redirecting::

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager)
    {
        // ...

        $this->resetForm();
    }

Using Actions to Change your Form: CollectionType
-------------------------------------------------

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
        public Post $initialFormData;

        protected function instantiateForm(): FormInterface
        {
            return $this->createForm(BlogPostFormType::class, $this->initialFormData);
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
                    data-live-action-param="removeComment"
                    data-live-index-param="{{ key }}"
                    type="button"
                >X</button>

                {{ form_widget(commentForm) }}
            {% endfor %}

            {# avoid an extra label for this field #}
            {% do form.comments.setRendered %}

            <button
                data-action="live#action"
                data-live-action-param="addComment"
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
------------------------

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
        public BlogPost $initialFormData;

        protected function instantiateForm(): FormInterface
        {
            return $this->createForm(BlogPostFormType::class, $this->initialFormData);
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
            {{ form_row(form.title) }}

            <h3>Comments:</h3>
            {% for key, commentForm in form.comments %}
                {# render a delete button for every row #}
                {{ form_row(commentForm.vars.button_delete, { label: 'X', attr: { class: 'btn btn-outline-danger' } }) }}

                {# render rest of the comment form #}
                {{ form_row(commentForm, { label: false }) }}
            {% endfor %}

            {# render the add button #}
            {{ form_widget(form.comments.vars.button_add, { label: '+ Add comment', attr: { class: 'btn btn-outline-primary' } }) }}

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
        {% set translation_domain = false %}
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