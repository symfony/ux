# Live Components

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

Live components work with the [TwigComponent](https://github.com/symfony/ux-twig-component)
library to give you the power to automatically update your
Twig components on the frontend as the user interacts with them.
Inspired by [Livewire](https://laravel-livewire.com/) and
[Phoenix LiveView](https://hexdocs.pm/phoenix_live_view/Phoenix.LiveView.html).

A real-time product search component might look like this:

```php
// src/Components/ProductSearchComponent.php
namespace App\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent('product_search')]
class ProductSearchComponent
{
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
```

```twig
{# templates/components/product_search.html.twig #}
<div {{ init_live_component(this) }}>
    <input
        type="search"
        name="query"
        value="{{ this.query }}"
        data-action="live#update"
    >

    <ul>
        {% for product in this.products %}
            <li>{{ product.name }}</li>
        {% endfor %}
    </ul>
</div>
```

As a user types into the box, the component will automatically
re-render and show the new results!

Want a demo? Check out https://github.com/weaverryan/live-demo.

## Installation

Let's get started! Install the library with:

```
composer require symfony/ux-live-component
```

This comes with an embedded JavaScript Stimulus controller. Unlike
other Symfony UX packages, this needs to be enabled manually
in your `config/bootstrap.js` file:

```js
// config/bootstrap.js
import LiveController from '@symfony/ux-live-component';
import '@symfony/ux-live-component/styles/live.css';
// ...

app.register('live', LiveController);
```

Finally, reinstall your Node dependencies and restart Encore:

```
yarn install --force
yarn encore dev
```

Oh, and just one more step! Import a routing file from the bundle:

```yaml
# config/routes.yaml
live_component:
    resource: '@LiveComponentBundle/Resources/config/routing/live_component.xml'
```

That's it! We're ready!

## Making your Component "Live"

If you haven't already, check out the [Twig Component](https://github.com/symfony/ux-twig-component)
documentation to get the basics of Twig components.

Suppose you've already built a basic Twig component:

```php
// src/Components/RandomNumberComponent.php
namespace App\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('random_number')]
class RandomNumberComponent
{
    public function getRandomNumber(): string
    {
        return rand(0, 1000);
    }
}
```

```twig
{# templates/components/random_number.html.twig #}
<div>
    <strong>{{ this.randomNumber }}</strong>
</div>
```

To transform this into a "live" component (i.e. one that
can be re-rendered live on the frontend), replace the
component's `AsTwigComponent` attribute with `AsLiveComponent`:

```diff
// src/Components/RandomNumberComponent.php

-use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
+use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

-#[AsTwigComponent('random_number')]
-#[AsLiveComponent('random_number')]
class RandomNumberComponent
{
}
```

Then, in the template, make sure there is _one_ HTML element around
your entire component and use the `{{ init_live_component() }}` function
to initialize the Stimulus controller:

```diff
-<div>
+<div {{ init_live_component(this) }}>
    <strong>{{ this.randomNumber }}</strong>
</div>
```

Your component is now a live component... except that we haven't added
anything that would cause the component to update. Let's start simple,
by adding a button that - when clicked - will re-render the component
and give the user a new random number:

```twig
<div {{ init_live_component(this) }}>
    <strong>{{ this.randomNumber }}</strong>

    <button
        data-action="live#$render"
    >Generate a new number!</button>
</div>
```

That's it! When you click the button, an Ajax call will be made to
get a fresh copy of our component. That HTML will replace the current
HTML. In other words, you just generated a new random number! That's
cool, but let's keep going because... things get cooler.

## LiveProps: Stateful Component Properties

Let's make our component more flexible by adding `$min` and `$max` properties:

```php
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

    public function getRandomNumber(): string
    {
        return rand($this->min, $this->max);
    }

    // ...
}
```

With this change, we can control the `$min` and `$max` properties
when rendering the component:

```
{{ component('random_number', { min: 5, max: 500 }) }}
```

But what's up with those `LiveProp` attributes? A property with
the `LiveProp` attribute becomes a "stateful" property for this
component. In other words, each time we click the "Generate a
new number!" button, when the component re-renders, it will
_remember_ the original values for the `$min` and `$max` properties
and generate a random number between 5 and 500. If you forgot to
add `LiveProp`, when the component re-rendered, those two values
would _not_ be set on the object.

In short: LiveProps are "stateful properties": they will always
be set when rendering. Most properties will be LiveProps, with
common exceptions being properties that hold services (these don't
need to be stateful because they will be autowired each time before
the component is rendered) and
[properties used for computed properties](https://github.com/symfony/ux-twig-component/blob/main/README.md#computed-properties).

## data-action="live#update": Re-rendering on LiveProp Change

Could we allow the user to _choose_ the `$min` and `$max` values
and automatically re-render the component when they do? Definitely!
And _that_ is where live components really shine.

Let's add two inputs to our template:

```twig
{# templates/components/random_number.html.twig #}
<div {{ init_live_component(this) }}>
    <input
        type="number"
        value="{{ this.min }}"
        data-model="min"
        data-action="live#update"
    >

    <input
        type="number"
        value="{{ this.max }}"
        data-model="max"
        data-action="live#update"
    >

    Generating a number between {{ this.min }} and {{ this.max }}
    <strong>{{ this.randomNumber }}</strong>
</div>
```

Notice the `data-action="live#update"` on each `input`. When the
user types, live components reads the `data-model` attribute (e.g. `min`)
and re-renders the component using the _new_ value for that field! Yes,
as you type in a box, the component automatically updates to reflect the
new number!

Well, actually, we're missing one step. By default, a `LiveProp` is
"read only". For security purposes, a user cannot change the value of
a `LiveProp` and re-render the component unless you allow it with
the `writable=true` option:

```diff
// src/Components/RandomNumberComponent.php
// ...

class RandomNumberComponent
{
-    #[LiveProp]
+    #[LiveProp(writable: true)]
    public int $min = 0;

-   #[LiveProp]
+   #[LiveProp(writable: true)]
    public int $max = 1000;

    // ...
}
```

Now it works: as you type into the `min` or `max` boxes, the component
will re-render with a new random number between that range!

### Debouncing

If the user types 5 characters really quickly into an `input`, we
don't want to send 5 Ajax requests. Fortunately, the `live#update`
method has built-in debouncing: it waits for a 150ms pause before
sending an Ajax request to re-render. This is built in, so you
don't need to think about it.

### Lazy Updating on "blur" or "change" of a Field

Sometimes, you might want a field to re-render only after the user
has changed an input _and_ moved to another field. Browsers dispatch
a `change` event in this situation. To re-render when this event
happens, add it to the `data-action` call:

```diff
<input
    type="number"
    value="{{ this.max }}"
    data-model="max"
-    data-action="live#update"
+    data-action="change->live#update"
>
```

The `data-action="change->live#update"` syntax is standard Stimulus
syntax, which says:

> When the "change" event occurs, call the `update` method on the
> `live` controller.

### Deferring a Re-Render Until Later

Other times, you might want to update the internal value of a property,
but wait until later to re-render the component (e.g. until a button
is clicked). To do that, use the `updateDefer` method:

```diff
<input
    type="number"
    value="{{ this.max }}"
    data-model="max"
-    data-action="live#update"
+    data-action="live#updateDefer"
>
```

Now, as you type, the `max` "model" will be updated in JavaScript, but
it won't, yet, make an Ajax call to re-render the component. Whenever
the next re-render _does_ happen, the updated `max` value will be used.

### Using name="" instead of data-model

Instead of communicating the property name of a field via `data-model`,
you can communicate it via the standard `name` property. The following
code works identically to the previous example:

```diff
<div {{ init_live_component(this)>
    <input
        type="number"
        value="{{ this.min }}"
-        data-model="min"
+        name="min"
        data-action="live#update"
    >

    // ...
</div>
```

If an element has _both_ `data-model` and `name` attributes, the
`data-model` attribute takes precedence.

## Loading States

Often, you'll want to show (or hide) an element while a component is
re-rendering or an [action](#actions) is processing. For example:

```twig
<!-- show only when the component is loading -->
<span data-loading>Loading</span>

<!-- equalivalent, longer syntax -->
<span data-loading="show">Loading</span>
```

Or, to _hide_ an element while the component is loading:

```twig
<!-- hide when the component is loading -->
<span
    data-loading="hide"
>Saved!</span>
```

### Adding and Removing Classes or Attributes

Instead of hiding or showing an entire element, you could
add or remove a class:

```twig
<!-- add this class when loading -->
<div data-loading="addClass(opacity-50)">...</div>

<!-- remove this class when loading -->
<div data-loading="removeClass(opacity-50)">...</div>

<!-- add multiple classes when loading -->
<div data-loading="addClass(opacity-50 disabled)">...</div>
```

Sometimes you may want to add or remove an attribute when loading.
That can be accomplished with `addAttribute` or `removeAttribute`:

```twig
<!-- add the "disabled" attribute when loading -->
<div data-loading="addAttribute(disabled)">...</div>
```

You can also combine any number of directives by separating them
with a space:

```twig
<div data-loading="addClass(opacity-50) addAttribute(disabled)">...</div>
```

Finally, you can add the `delay` modifier to not trigger the loading
changes until loading has taken longer than a certain amount of time:

```twig
<!-- Add class after 200ms of loading -->
<div data-loading="delay|addClass(opacity-50)">...</div>

<!-- Show after 200ms of loading -->
<div data-loading="delay|show">Loading</div>

<!-- Show after 500ms of loading -->
<div data-loading="delay(500)|show">Loading</div>
```

## Actions

You can also trigger actions on your component. Let's pretend we
want to add a "Reset Min/Max" button to our "random number"
component that, when clicked, sets the min/max numbers back
to a default value.

First, add a method with a `LiveAction` attribute above it that
does the work:

```php
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
```

To call this, add `data-action="live#action"` and `data-action-name`
to an element (e.g. a button or form):

```twig
<button
    data-action="live#action"
    data-action-name="resetMinMax"
>Reset Min/Max</button>
```

Done! When the user clicks this button, a POST request will be sent
that will trigger the `resetMinMax()` method! After calling that method,
the component will re-render like normal, using the new `$min` and `$max`
properties!

You can also add several "modifiers" to the action:

```twig
<form>
    <button
        data-action="live#action"
        data-action-name="prevent|debounce(300)|save"
    >Save</button>
</form>
```

The `prevent` modifier would prevent the form from submitting
(`event.preventDefault()`). The `debounce(300)` modifier will
add 300ms of "debouncing" before the action is executed. In
other words, if you click really fast 5 times, only one Ajax
request will be made!

#### Actions & Services

One really neat thing about component actions is that they are
_real_ Symfony controllers. Internally, they are processed
identically to a normal controller method that you would create
with a route.

This means that, for example, you can use action autowiring:

```php
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
```

### Actions and CSRF Protection

When you trigger an action, a POST request is sent that contains
a `X-CSRF-TOKEN` header. This header is automatically populated
and validated. In other words... you get CSRF protection without
any work.

Your only job is to make sure that the CSRF component is installed:

```
composer require symfony/security-csrf
```

### Actions, Redirecting and AbstractController

Sometimes, you may want to redirect after an action is executed
(e.g. your action saves a form and then you want to redirect to
another page). You can do that by returning a `RedirectResponse`
from your action:

```php
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
```

You probably noticed one interesting trick: to make redirecting easier,
the component now extends `AbstractController`! That is totally allowed,
and gives you access to all of your normal controller shortcuts. We
even added a flash message!

## Forms

A component can also help render a [Symfony form](https://symfony.com/doc/current/forms.html),
either the entire form (useful for automatic validation as you type) or just
one or some fields (e.g. a markdown preview for a `textarea` or
[dependent form fields](https://symfony.com/doc/current/form/dynamic_form_modification.html#dynamic-generation-for-submitted-forms)).

### Rendering an Entire Form in a Component

Suppose you have a `PostType` form class that's bound to a `Post` entity
and you'd like to render this in a component so that you can get instant
validation as the user types:

```php
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
```

Before you start thinking about the component, make sure that you
have your controller set up so you can handle the form submit. There's
nothing special about this controller: it's written however you normally
write your form controller logic:

```php
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
     * @Route("/admin/post/{id}/edit", name="app_post_edit")
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
```

Great! In the template, instead of rendering the form, let's render
a `post_form` component that we will create next:

```twig
{# templates/post/edit.html.twig #}

{% extends 'base.html.twig' %}

{% block body %}
    <h1>Edit Post</h1>

    {{ component('post_form', {
        post: post,
        form: form
    }) }}
{% endblock %}
```

Ok: time to build that `post_form` component! The Live Components package
comes with a special trait - `ComponentWithFormTrait` - to make it easy to
deal with forms:

```php
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
     * with that data. The value - initialFormData - could be anything.
     */
    #[LiveProp(fieldName: 'initialFormData')]
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
```

The trait forces you to create an `instantiateForm()` method,
which is used when the component is rendered via AJAX. Notice that,
in order to recreate the _same_ form, we pass in the `Post` object
and set it as a `LiveProp`.

The template for this component will render the form, which is
available as `this.form` thanks to the trait:

```twig
{# templates/components/post_form.html.twig #}
<div
    {{ init_live_component(this) }}
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
    {{ form_start(this.form) }}
        {{ form_row(this.form.title) }}
        {{ form_row(this.form.slug) }}
        {{ form_row(this.form.content) }}

        <button>Save</button>
    {{ form_end(this.form) }}
</div>
```

Mostly, this is a pretty boring template! It includes the normal
`init_live_component(this)` and then you render the form however you want.

But the result is incredible! As you finish changing each field, the
component automatically re-renders - including showing any validation
errors for that field! Amazing!

This is possible thanks to a few interesting pieces:

-   `data-action="change->live#update"`: instead of adding `data-action`
    to _every_ field, you can place this on a parent element. Thanks to
    this, as you change or type into fields (i.e. the `input` event),
    the model for that field will update and the component will re-render.

-   The fields in our form do not have a `data-model=""` attribute. But
    that's ok! When that is absent, the `name` attribute is used instead.
    `ComponentWithFormTrait` has a modifiable `LiveProp` that captures
    these and submits the form using them. That's right: each render time
    the component re-renders, the form is _submitted_ using the values.
    However, if a field has not been modified yet by the user, its
    validation errors are cleared so that they aren't rendered.

### Form Rendering Problems

For the most part, rendering a form inside a component works beautifully.
But there are a few situations when your form may not behave how you
want.

**A) Text Boxes Removing Trailing Spaces**

If you're re-rendering a field on the `input` event (that's the default
event on a field, which is fired each time you type in a text box), then
if you type a "space" and pause for a moment, the space will disappear!

This is because Symfony text fields "trim spaces" automatically. When
your component re-renders, the space will disappear... as the user is typing!
To fix this, either re-render on the `change` event (which fires after
the text box loses focus) or set the `trim` option of your field to
`false`:

```php
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        // ...
        ->add('content', TextareaType::class, [
            'trim' => false,
        ])
    ;
}
```

**B) `PasswordType` loses the password on re-render**

If you're using the `PasswordType`, when the component re-renders,
the input will become blank! That's because, by default, the
`PasswordType` does not re-fill the `<input type="password">` after
a submit.

To fix this, set the `always_empty` option to `false` in your form:

```php
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        // ...
        ->add('plainPassword', PasswordType::class, [
            'always_empty' => false,
        ])
    ;
}
```

### Submitting the Form via an action()

Notice that, while we _could_ add a `save()` [component action](#actions)
that handles the form submit through the component, we've chosen not
to do that so far. The reason is simple: by creating a normal route &
controller that handles the submit, our form continues to work without
JavaScript.

However, you _can_ do this if you'd like. In that case, you wouldn't
need any form logic in your controller:

```php
/**
 * @Route("/admin/post/{id}/edit", name="app_post_edit")
 */
public function edit(Post $post): Response
{
    return $this->render('post/edit.html.twig', [
        'post' => $post,
    ]);
}
```

And you wouldn't pass any `form` into the component:

```twig
{# templates/post/edit.html.twig #}

<h1>Edit Post</h1>

{{ component('post_form', {
    post: post
}) }}
```

When you do _not_ pass a `form` into a component that uses `ComponentWithFormTrait`,
the form will be created for you automatically. Let's add the `save()`
action to the component:

```php
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
```

Finally, tell the `form` element to use this action:

```
{# templates/components/post_form.html.twig #}
{# ... #}

{{ form_start(this.form, {
    attr: {
        'data-action': 'live#action',
        'data-action-name': 'prevent|save'
    }
}) }}
```

Now, when the form is submitted, it will execute the `save()` method
via Ajax. If the form fails validation, it will re-render with the
errors. And if it's successful, it will redirect.

## Modifying Embedded Properties with the "exposed" Option

If your component will render a form, you don't need to use
the Symfony form component. Let's build an `EditPostComponent`
without a form. This will need one `LiveProp`: the `Post` object
that is being edited:

```php
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
```

In the template, let's render an HTML form _and_ a "preview" area
where the user can see, as they type, what the post will look like
(including rendered the `content` through a Markdown filter from the
`twig/markdown-extra` library):

```
<div {{ init_live_component(this) }}>
    <input
        type="text"
        value="{{ this.post.title }}"
        data-model="post.title"
        data-action="live#update"
    >

    <textarea
        data-model="post.content"
        data-action="live#update"
    >{{ this.post.content }}</textarea>

    <div class="markdown-preview" data-loading="addClass(low-opacity)">
        <h3>{{ this.post.title }}</h3>
        {{ this.post.content|markdown_to_html }}
    </div>
</div>
```

This is pretty straightforward, except for one thing: the `data-model`
attributes aren't targeting properties on the component class itself,
they're targeting _embedded_ properties within the `$post` property.

Out-of-the-box, modifying embedded properties is _not_ allowed. However,
you can enable it via the `exposed` option:

```diff
// ...

class EditPostComponent
{
-   #[LiveProp]
+   #[LiveProp(exposed: ['title', 'content'])]
    public Post $post;

    // ...
}
```

With this, both the `title` and the `content` properties of the
`$post` property _can_ be modified by the user. However, notice
that the `LiveProp` does _not_ have `modifiable=true`. This
means that while the `title` and `content` properties can be
changed, the `Post` object itself **cannot** be changed. In other
words, if the component was originally created with a Post
object with id=2, a bad user could _not_ make a request that
renders the component with id=3. Your component is protected from
someone changing to see the form for a different `Post` object,
unless you added `writable=true` to this property.

### Validation (without a Form)

**NOTE** If your component [contains a form](#forms), then validation
is built-in automatically. Follow those docs for more details.

If you're building some sort of form _without_ using Symfony's form
component, you _can_ still validate your data.

First use the `ValidatableComponentTrait` and add any constraints you need:

```php
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
```

Be sure to add the `IsValid` attribute/annotation to any property where
you want the object on that property to also be validated.

Thanks to this setup, the component will now be automatically validated
on each render, but in a smart way: a property will only be validated
once its "model" has been updated on the frontend. The system keeps track
of which models have been updated (e.g. `data-action="live#update"`)
and only stores the errors for those fields on re-render.

You can also trigger validation of your _entire_ object manually
in an action:

```php
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
```

If validation fails, an exception is thrown, but the component will be
re-rendered. In your template, render errors using the `getError()`
method:

```twig
{% if this.getError('post.content') %}
    <div class="error">
        {{ this.getError('post.content').message }}
    </div>
{% endif %}
<textarea
    data-model="post.content"
    data-action="live#update"
    class="{{ this.getError('post.content') ? 'has-error' : '' }}"
>{{ this.post.content }}</textarea>
```

Once a component has been validated, the component will "rememeber"
that it has been validated. This means that, if you edit a field and
the component re-renders, it will be validated again.

## Real Time Validation

As soon as you enable validation, each field will automatically
be validated when its model is updated. For example, if you want
a single field to be validated "on change" (when you change the field
and then blur the field), update the model via the `change` event:

```twig
<textarea
    data-model="post.content"
    data-action="change->live#update"
    class="{{ this.getError('post.content') ? 'has-error' : '' }}"
>{{ this.post.content }}</textarea>
```

When the component re-renders, it will signal to the server that this
one field should be validated. Like with normal validation, once an
individual field has been validated, the component "remembers" that,
and re-validates it on each render.

## Polling

You can also use "polling" to continually refresh a component. On
the **top-level** element for your component, add `data-poll`:

```diff
<div
    {{ init_live_component(this) }}
+    data-poll
>
```

This will make a request every 2 seconds to re-render the component. You
can change this by adding a `delay()` modifier. When you do this, you need
to be specific that you want to call the `$render` method. To delay for
500ms:

```twig
<div
    {{ init_live_component(this) }}
    data-poll="delay(500)|$render"
>
```

You can also trigger a specific "action" instead of a normal re-render:

```twig
<div
    {{ init_live_component(this) }}

    data-poll="save"
    {#
    Or add a delay() modifier:
    data-poll="delay(2000)|save"
    #}
>
```

## Embedded Components

Need to embed one live component inside another one? No problem! As a rule
of thumb, **each component exists in its own, isolated universe**. This
means that embedding one component inside another could be really simple
or a bit more complex, depending on how inter-connected you want your components
to be.

Here are a few helpful things to know:

### Each component re-renders independent of one another

If a parent component re-renders, the child component will _not_ (most
of the time) be updated, even though it lives inside the parent. Each
component is its own, isolated universe.

But this is not always what you want. For example, suppose you have a
parent component that renders a form and a child component that renders
one field in that form. When you click a "Save" button on the parent
component, that validates the form and re-renders with errors - including
a new `error` value that it passes into the child:

```twig
{# templates/components/post_form.html.twig #}

{{ component('textarea_field', {
    value: this.content,
    error: this.getError('content')
}) }}
```

In this situation, when the parent component re-renders after clicking
"Save", you _do_ want the updated child component (with the validation
error) to be rendered. And this _will_ happen automatically. Why? because
the live component system detects that the **parent component has
_changed_ how it's rendering the child**.

This may not always be perfect, and if your child component has its own
`LiveProp` that has changed since it was first rendered, that value will
be lost when the parent component causes the child to re-render. If you
have this situation, use `data-model-map` to map that child `LiveProp` to
a `LiveProp` in the parent component, and pass it into the child when
rendering.

### Actions, methods and model updates in a child do not affect the parent

Again, each component is its own, isolated universe! For example, suppose
your child component has:

```html
<button data-action="live#action" data-action-name="save">Save</button>
```

When the user clicks that button, it will attempt to call the `save` action
in the _child_ component only, even if the `save` action actually only
exists in the parent. The same is true for `data-model`, though there is
some special handling for this case (see next point).

### If a child model updates, it will attempt to update the parent model

Suppose a child component has a:

```html
<textarea data-model="markdown_value" data-action="live#update">
```

When the user changes this field, this will _only_ update the `markdown_value`
field in the _child_ component... because (yup, we're saying it again):
each component is its own, isolated universe.

However, sometimes this isn't what you want! Sometimes, in addition
to updating the child component's model, you _also_ want to update a
model on the _parent_ component.

To help with this, whenever a model updates, a `live:update-model` event
is dispatched. All components automatically listen to this event. This
means that, when the `markdown_value` model is updated in the child
component, _if_ the parent component _also_ has a model called `markdown_value`
it will _also_ be updated. This is done as a "deferred" update
(i.e. [updateDefer()](#deferring-a-re-render-until-later)).

If the model name in your child component (e.g. `markdown_value`) is
_different_ than the model name in your parent component (e.g. `post.content`),
you have two options. First, you can make sure both are set by
leveraging both the `data-model` and `name` attributes:

```twig
<textarea
    data-model="markdown_value"
    name="post[content]"
    data-action="live#update"
>
```

In this situation, the `markdown_value` model will be updated on the child
component (because `data-model` takes precedence over `name`). But if
any parent components have a `markdown_value` model _or_ a `post.content`
model (normalized from `post[content`]`), their model will also be updated.

A second option is to wrap your child element in a special `data-model-map`
element:

```twig
{# templates/components/post_form.html.twig #}

<div data-model-map="from(markdown_value)|post.content">
    {{ component('textarea_field', {
        value: this.content,
        error: this.getError('content')
    }) }}
</div>
```

Thanks to the `data-model-map`, whenever the `markdown_value` model
updates in the child component, the `post.content` model will be
updated in the parent component.

**NOTE**: If you _change_ a `LiveProp` of a child component on the server
(e.g. during re-rendering or via an action), that change will _not_ be
reflected on any parent components that share that model.

### Full Embedded Component Example

Let's look at a full, complex example of an embedded component. Suppose
you have an `EditPostComponent`:

```php
<?php

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
```

And a `MarkdownTextareaComponent`:

```php
<?php

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
```

In the `EditPostComponent` template, you render the `MarkdownTextareaComponent`:

```twig
{# templates/components/edit_post.html.twig #}
<div {{ init_live_component(this) }}>
    <input
        type="text"
        name="post[title]"
        data-action="live#update"
        value="{{ this.post.title }}"
    >

    {{ component('markdown_textarea', {
        name: 'post[content]',
        label: 'Content',
        value: this.post.content
    }) }}

    <button
        data-action="live#action"
        data-action-name="save"
    >Save</button>
</div>
```

```twig
<div {{ init_live_component(this) }} class="mb-3">
    <textarea
        name="{{ this.name }}"
        data-model="value"
        data-action="live#update"
    >{{ this.value }}</textarea>

    <div class="markdown-preview">
        {{ this.value|markdown_to_html }}
    </div>
</div>
```

Notice that `MarkdownTextareaComponent` allows a dynamic `name` attribute to
be passed in. This makes that component re-usable in any form. But it
also makes sure that when the `textarea` changes, both the `value` model
in `MarkdownTextareaComponent` _and_ the `post.content` model in
`EditPostcomponent` will be updated.
