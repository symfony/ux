Symfony UX Turbo
================

.. admonition:: Screencast
    :class: screencast

    Do you prefer video tutorials? Check out the `Symfony UX Turbo screencast series`_.

Symfony UX Turbo is a Symfony bundle integrating the `Hotwire Turbo`_
library in Symfony applications. It is part of `the Symfony UX initiative`_.

Symfony UX Turbo allows having the same user experience as with
`Single Page Applications`_ but without having to write a single line of
JavaScript!

Symfony UX Turbo also integrates with `Symfony Mercure`_ or any other
transports to broadcast DOM changes to all currently connected users!

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-turbo

Usage
-----

Turbo Drive
~~~~~~~~~~~

Turbo Drive enhances page-level navigation. It watches for link clicks
and form submissions, performs them in the background, and updates the
page without doing a full reload. This gives you the "single-page-app"
experience without major changes to your code!

Turbo Drive is automatically enabled when you install Symfony UX Turbo.

Getting Started with Turbo Drive
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

There are 3 things to be aware of:

1. Make sure your JavaScript is Turbo-ready
"""""""""""""""""""""""""""""""""""""""""""

Because navigation no longer results in full page refreshes, you may
need to adjust your JavaScript to work properly. The best solution is to
write your JavaScript using
`Stimulus`_ or something similar.

2. Reloading When a JavaScript/CSS File Changes
"""""""""""""""""""""""""""""""""""""""""""""""

Turbo Drive can automatically perform a full refresh if the content of
one of your CSS or JS files *changes*, to ensure that your users always
have the latest version.

**If you're using AssetMapper** (default), this is handled automatically.
Symfony UX Turbo configures the ``data-turbo-track="reload"`` attribute
on your importmap script tags out of the box — no extra configuration needed.

If you're using WebpackEncore, see :doc:`webpack-encore`.

For more info, see: `Turbo Reloading When Assets Change`_.

3. Form Response Code Changes
"""""""""""""""""""""""""""""

Turbo Drive also converts form submissions to AJAX calls. To get it to
work, you *do* need to adjust your code to return a 422 status code on a
validation error (instead of a 200).

The ``render()`` method takes care of this automatically::

    // src/Controller/TaskController.php
    namespace App\Controller;

    use App\Entity\Task;
    use App\Form\TaskType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
        public function new(Request $request): Response
        {
            $task = new Task();
            $form = $this->createForm(TaskType::class, $task);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // ... perform some action, such as saving the task to the database

                return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('task/new.html.twig', [
                'task' => $task,
                'form' => $form,
            ]);
        }
    }

This changes the response status code to 422 on validation error, which
tells Turbo Drive that the form submit failed and it should re-render
with the errors. After a successful submission, Turbo Drive expects a
``303`` redirect response, hence the use of ``Response::HTTP_SEE_OTHER``.

Meta Tags
^^^^^^^^^

Symfony UX Turbo provides a set of Twig functions to configure Turbo Drive
via meta tags.

turbo_exempts_page_from_cache
"""""""""""""""""""""""""""""

.. code-block:: twig

    {{ turbo_exempts_page_from_cache() }}

Generates a ``<meta>`` tag to disable caching of a page.

.. code-block:: html+twig

    {{ turbo_exempts_page_from_cache() }}
    {# output: #}
    <meta name="turbo-cache-control" content="no-cache">

turbo_exempts_page_from_preview
"""""""""""""""""""""""""""""""

.. code-block:: twig

    {{ turbo_exempts_page_from_preview() }}

Generates a ``<meta>`` tag to specify that the cached version of the page
should not be shown as a preview on regular navigation visits.

.. code-block:: html+twig

    {{ turbo_exempts_page_from_preview() }}
    {# output: #}
    <meta name="turbo-cache-control" content="no-preview">

turbo_page_requires_reload
""""""""""""""""""""""""""

.. code-block:: twig

    {{ turbo_page_requires_reload() }}

Generates a ``<meta>`` tag to force a full page reload.

.. code-block:: html+twig

    {{ turbo_page_requires_reload() }}
    {# output: #}
    <meta name="turbo-visit-control" content="reload">

turbo_refreshes_with
""""""""""""""""""""

.. code-block:: twig

    {{ turbo_refreshes_with(method = 'replace', scroll = 'reset') }}

``method`` *(optional)*
    **type**: ``string`` **default**: ``replace`` **possible values**: ``replace`` or ``morph``
``scroll`` *(optional)*
    **type**: ``string`` **default**: ``reset`` **possible values**: ``reset`` or ``preserve``

Generates ``<meta>`` tags to configure both the refresh method and scroll behavior for page refreshes.

.. code-block:: html+twig

    {{ turbo_refreshes_with(method: 'morph', scroll: 'preserve') }}
    {# output: #}
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">

turbo_refresh_method
""""""""""""""""""""

.. code-block:: twig

    {{ turbo_refresh_method(method = 'replace') }}

``method`` *(optional)*
    **type**: ``string`` **default**: ``replace`` **possible values**: ``replace`` or ``morph``

Generates a ``<meta>`` tag to configure the refresh method for page refreshes.

.. code-block:: html+twig

    {{ turbo_refresh_method(method: 'morph') }}
    {# output: #}
    <meta name="turbo-refresh-method" content="morph">

turbo_refresh_scroll
""""""""""""""""""""

.. code-block:: twig

    {{ turbo_refresh_scroll(scroll = 'reset') }}

``scroll`` *(optional)*
    **type**: ``string`` **default**: ``reset`` **possible values**: ``reset`` or ``preserve``

Generates a ``<meta>`` tag to configure the scroll behavior for page refreshes.

.. code-block:: html+twig

    {{ turbo_refresh_scroll(scroll: 'preserve') }}
    {# output: #}
    <meta name="turbo-refresh-scroll" content="preserve">

.. seealso::

    `Read the Turbo meta tags reference`_ for the full list of available meta tags.

.. seealso::

    `Read the Turbo Drive documentation`_ to learn about the advanced features
    offered by Turbo Drive.

Turbo Frames
~~~~~~~~~~~~

Turbo Frames let you treat **any subset of a page as its own component**: links
and form submissions within a frame replace only that part, without any custom
JavaScript. Frames can also be **lazy-loaded**, making it easy to split a page
into independently cached pieces.

<twig:Turbo:Frame>
^^^^^^^^^^^^^^^^^^

Returns a ``<turbo-frame>`` element that can either be used to encapsulate
frame content or as a lazy-loading container that starts empty but fetches
the URL supplied in the ``src`` attribute.

Examples
""""""""

.. code-block:: html+twig

    <twig:Turbo:Frame id="task_{{ task.id }}" src="{{ path('app_task_show', {id: task.id}) }}" />
    {# output: <turbo-frame id="task_1" src="http://example.com/task/1"></turbo-frame> #}

    <twig:Turbo:Frame id="task_{{ task.id }}" src="{{ path('app_task_show', {id: task.id}) }}" target="_top" />
    {# output: <turbo-frame id="task_1" src="http://example.com/task/1" target="_top"></turbo-frame> #}

    <twig:Turbo:Frame id="task" target="other_task" />
    {# output: <turbo-frame id="task" target="other_task"></turbo-frame> #}

    <twig:Turbo:Frame id="task_{{ task.id }}" src="{{ path('app_task_show', {id: task.id}) }}" loading="lazy" />
    {# output: <turbo-frame id="task_1" src="http://example.com/task/1" loading="lazy"></turbo-frame> #}

    <twig:Turbo:Frame id="task_{{ task.id }}">
        <div>My task frame!</div>
    </twig:Turbo:Frame>
    {# output: #}
    <turbo-frame id="task_1">
        <div>My task frame!</div>
    </turbo-frame>

Navigation within a Frame
"""""""""""""""""""""""""

When a link inside a Turbo Frame is clicked, Turbo automatically replaces
the frame content with the matching frame from the linked page.

.. code-block:: html+twig

    {# templates/task/show.html.twig #}
    <twig:Turbo:Frame id="task_{{ task.id }}">
        <p>{{ task.title }}</p>

        <a href="{{ path('app_task_edit', {id: task.id}) }}">Edit this task</a>
    </twig:Turbo:Frame>

    {# templates/task/edit.html.twig #}
    <twig:Turbo:Frame id="task_{{ task.id }}">
        {{ form(form) }}

        <a href="{{ path('app_task_show', {id: task.id}) }}">Cancel</a>
    </twig:Turbo:Frame>

When the user clicks on "Edit this task", the Turbo Frame will be
automatically replaced with the matching frame from ``task/edit.html.twig``.

Detecting Turbo Frame Requests
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

nject the ``TurboFrame`` service to detect whether the current request
was triggered by a Turbo Frame and retrieve the frame's ID::

    // src/Controller/TaskController.php
    namespace App\Controller;

    use App\Entity\Task;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Turbo\TurboFrame;

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/{id}', name: 'app_task_show', methods: ['GET'])]
        public function show(TurboFrame $turboFrame, Task $task): Response
        {
            if ($turboFrame->isFrameRequest()) {
                // The request was triggered by a Turbo Frame.
                // Render a partial response for the frame only.
                $frameId = $turboFrame->getRequestId(); // e.g. "task_details"
            }

            // ...
        }
    }

You can also use the ``turbo_is_frame_request()`` and ``turbo_frame_request_id()``
Twig functions directly in your templates:

.. code-block:: html+twig

    {% if turbo_is_frame_request() %}
        {# Render a partial response for the frame #}
        <p>Frame ID: {{ turbo_frame_request_id() }}</p>
    {% else %}
        {# Render the full page #}
    {% endif %}

.. seealso::

    `Read the Turbo Frames documentation`_ to learn
    everything you can do using Turbo Frames.

Minimal Frame Layout
^^^^^^^^^^^^^^^^^^^^

When a Turbo Frame request is made, the response only needs to contain the
matching ``<turbo-frame>`` element — serving the full application layout is
wasteful. For this reason, frame responses are typically rendered without any
layout at all. However, this optimization has a drawback: it prevents the
response from including ``<head>`` content such as page-specific meta tags.

To solve this, Symfony UX Turbo provides a minimal layout template that keeps the
response lightweight while still allowing you to populate the ``<head>``
block. Use it by extending ``@Turbo/layouts/frame.html.twig`` in templates
that are rendered in response to Turbo Frame requests:

.. code-block:: html+twig

    {# templates/task/edit.html.twig #}
    {% extends '@Turbo/layouts/frame.html.twig' %}

    {% block head %}
        {{ turbo_exempts_page_from_cache() }}
    {% endblock %}

    {% block body %}
        <twig:Turbo:Frame id="task_{{ task.id }}">
            {{ form(form) }}

            <a href="{{ path('app_task_show', {id: task.id}) }}">Cancel</a>
        </twig:Turbo:Frame>
    {% endblock %}

This renders a minimal HTML document:

.. code-block:: html

    <!DOCTYPE html>
    <html>
        <head>
            <meta name="turbo-cache-control" content="no-cache">
        </head>
        <body>
            <turbo-frame id="task_42">
                <!-- form fields -->

                <a href="/task/42">Cancel</a>
            </turbo-frame>
        </body>
    </html>

Turbo Streams
~~~~~~~~~~~~~

Symfony UX Turbo registers ``text/vnd.turbo-stream.html`` as the
``TurboBundle::STREAM_FORMAT`` format, which can be detected in controllers
using ``$request->getPreferredFormat()``.

Turbo Stream Responses
^^^^^^^^^^^^^^^^^^^^^^

When a user submits a form or triggers an action, the controller can detect
the Turbo Stream format and return a partial page update instead of a full
redirect. There are two ways to do this:

**Option 1 — dedicated template with** ``renderBlock``:

Use ``renderBlock()`` to render a specific Twig block from a template as a
Turbo Stream response. This keeps the stream markup close to the page template
it updates::

    // src/Controller/TaskController.php
    namespace App\Controller;

    use App\Entity\Task;
    use App\Form\TaskType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Turbo\TurboBundle;

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
        public function new(Request $request): Response
        {
            $task = new Task();
            $form = $this->createForm(TaskType::class, $task);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // ... perform some action, such as saving the task to the database

                if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                    return $this->renderBlock('task/index.html.twig', 'success_stream', ['task' => $task]);
                }

                return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('task/new.html.twig', [
                'form' => $form,
            ]);
        }
    }

.. code-block:: html+twig

    {# bottom of task/index.html.twig #}
    {% block success_stream %}
        <twig:Turbo:Stream:Append target="#task_list">
            <li id="task_{{ task.id }}">{{ task.title }}</li>
        </twig:Turbo:Stream:Append>
    {% endblock %}

**Option 2 — inline with** ``TurboStreamResponse``:

Use ``TurboStreamResponse`` to build stream actions directly from the
controller, without a dedicated template. This is convenient for simple
actions like removing an element::

    // src/Controller/TaskController.php
    namespace App\Controller;

    use App\Entity\Task;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Turbo\TurboBundle;
    use Symfony\UX\Turbo\TurboStreamResponse;

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/{id}', name: 'app_task_delete', methods: ['POST'])]
        public function delete(Request $request, Task $task): Response
        {
            // ... delete the task

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                return (new TurboStreamResponse())
                    ->remove('#task_'.$task->getId());
            }

            return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
        }
    }

Stream Messages and Actions
^^^^^^^^^^^^^^^^^^^^^^^^^^^

To render ``<turbo-stream>`` elements in templates, Symfony UX Turbo provides
a set of ``<twig:Turbo:Stream:*>`` Twig components. The ``target`` attribute
accepts a CSS selector (e.g. ``#task_42``, ``.tasks``).

Remove
""""""

Removes the element(s) designated by ``targets`` from the DOM.

.. code-block:: html+twig

    <twig:Turbo:Stream:Remove target="#task_{{ task.id }}" />

    {# output: #}
    <turbo-stream action="remove" targets="#task_42"></turbo-stream>

Replace
"""""""

Replaces the element(s) designated by ``targets`` with the provided content.

.. code-block:: html+twig

    <twig:Turbo:Stream:Replace target="#task_{{ task.id }}">
        <li id="task_{{ task.id }}">{{ task.title }}</li>
    </twig:Turbo:Stream:Replace>

    {# output: #}
    <turbo-stream action="replace" targets="#task_42">
        <template>
            <li id="task_42">My task</li>
        </template>
    </turbo-stream>

.. code-block:: html+twig

    {# with morphing #}
    <twig:Turbo:Stream:Replace target="#task_{{ task.id }}" morph>
        <li id="task_{{ task.id }}">{{ task.title }}</li>
    </twig:Turbo:Stream:Replace>

    {# output: #}
    <turbo-stream action="replace" targets="#task_42" method="morph">
        <template>
            <li id="task_42">My task</li>
        </template>
    </turbo-stream>

Update
""""""

Replaces the inner content of the element(s) designated by ``targets``, keeping
the element itself.

.. code-block:: html+twig

    <twig:Turbo:Stream:Update target="#task_{{ task.id }}">
        {{ task.title }}
    </twig:Turbo:Stream:Update>

    {# output: #}
    <turbo-stream action="update" targets="#task_42">
        <template>
            My task
        </template>
    </turbo-stream>

.. code-block:: html+twig

    {# with morphing #}
    <twig:Turbo:Stream:Update target="#task_{{ task.id }}" morph>
        {{ task.title }}
    </twig:Turbo:Stream:Update>

    {# output: #}
    <turbo-stream action="update" targets="#task_42" method="morph">
        <template>
            My task
        </template>
    </turbo-stream>

Before
""""""

Inserts content immediately before the element(s) designated by ``targets``.

.. code-block:: html+twig

    <twig:Turbo:Stream:Before target="#task_{{ task.id }}">
        <li id="task_{{ newTask.id }}">{{ newTask.title }}</li>
    </twig:Turbo:Stream:Before>

    {# output: #}
    <turbo-stream action="before" targets="#task_42">
        <template>
            <li id="task_41">Previous task</li>
        </template>
    </turbo-stream>

After
"""""

Inserts content immediately after the element(s) designated by ``targets``.

.. code-block:: html+twig

    <twig:Turbo:Stream:After target="#task_{{ task.id }}">
        <li id="task_{{ newTask.id }}">{{ newTask.title }}</li>
    </twig:Turbo:Stream:After>

    {# output: #}
    <turbo-stream action="after" targets="#task_42">
        <template>
            <li id="task_43">Next task</li>
        </template>
    </turbo-stream>

Append
""""""

Appends content as the last child of the element(s) designated by ``targets``.

.. code-block:: html+twig

    <twig:Turbo:Stream:Append target="#task_list">
        <li id="task_{{ task.id }}">{{ task.title }}</li>
    </twig:Turbo:Stream:Append>

    {# output: #}
    <turbo-stream action="append" targets="#task_list">
        <template>
            <li id="task_42">My task</li>
        </template>
    </turbo-stream>

Prepend
"""""""

Prepends content as the first child of the element(s) designated by ``targets``.

.. code-block:: html+twig

    <twig:Turbo:Stream:Prepend target="#task_list">
        <li id="task_{{ task.id }}">{{ task.title }}</li>
    </twig:Turbo:Stream:Prepend>

    {# output: #}
    <turbo-stream action="prepend" targets="#task_list">
        <template>
            <li id="task_42">My task</li>
        </template>
    </turbo-stream>

Refresh
"""""""

Triggers a page refresh. Pass a ``requestId`` to debounce multiple refreshes.

.. code-block:: html+twig

    {# without [request-id] #}
    <twig:Turbo:Stream:Refresh />

    {# output: #}
    <turbo-stream action="refresh"></turbo-stream>

.. code-block:: html+twig

    {# debounced with [request-id] #}
    <twig:Turbo:Stream:Refresh requestId="abcd-1234" />

    {# output: #}
    <turbo-stream action="refresh" request-id="abcd-1234"></turbo-stream>


Custom Action
"""""""""""""

For custom stream actions, use the generic ``<twig:Turbo:Stream>`` component
or the ``TurboStreamResponse::action()`` method.

.. code-block:: html+twig

    <twig:Turbo:Stream action="my_action" />

    {# output: #}
    <turbo-stream action="my_action"></turbo-stream>

You can also use the ``TurboStreamResponse::action()`` method from a controller::

    // src/Controller/TaskController.php
    use Symfony\UX\Turbo\TurboStreamResponse;

    if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
        return (new TurboStreamResponse())
            ->action('my_action', '#task_'.$task->getId(), '<li>'.$task->getTitle().'</li>');
    }

.. seealso::

    `Read the Turbo Streams reference for more details`_.

Broadcasting
~~~~~~~~~~~~

Symfony UX Turbo also supports broadcasting HTML updates to all
currently connected clients, using the
`Mercure`_ protocol or any other.

Start by installing `the Mercure support`_ on your project:

.. code-block:: terminal

    $ composer require symfony/mercure-bundle

Then, enable the "mercure stream" controller in ``assets/controllers.json``:

.. code-block:: diff

    "@symfony/ux-turbo": {
        "mercure-turbo-stream": {
    -         "enabled": false,
    +         "enabled": true,
            "fetch": "eager"
        }
    },

.. deprecated:: 3.1

    Manually enabling the ``mercure-turbo-stream`` controller is deprecated since
    Symfony UX 3.1. Use the ``<twig:Turbo:Stream:From>`` Twig component or the
    ``turbo_stream_from()`` Twig function instead.

The easiest way to have a working development (and production-ready)
environment is to use `Symfony Docker`_, which comes with
a Mercure hub integrated in the web server.

If you use Symfony Flex, the configuration has been generated for you,
be sure to update the ``MERCURE_URL`` in the ``.env`` file to point to a
Mercure Hub (it's not necessary if you are using Symfony Docker).

Otherwise, configure Mercure Hub(s) as explained in the documentation:

.. code-block:: yaml

    # config/packages/mercure.yaml
    mercure:
        hubs:
            default:
                url: '%env(MERCURE_URL)%'
                public_url: '%env(MERCURE_PUBLIC_URL)%'
                jwt:
                    secret: '%env(MERCURE_JWT_SECRET)%'
                    publish: '*'

To illustrate this, here is how to build a real-time task list with **0 lines
of JavaScript**. When a task is created, all connected clients will
automatically see it appear in the list::

    // src/Controller/TaskController.php
    namespace App\Controller;

    use App\Entity\Task;
    use App\Form\TaskType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Mercure\HubInterface;
    use Symfony\Component\Mercure\Update;
    use Symfony\Component\Routing\Attribute\Route;

    #[Route('/task')]
    class TaskController extends AbstractController
    {
        #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
        public function new(Request $request, HubInterface $hub): Response
        {
            $form = $this->createForm(TaskType::class, new Task());
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $task = $form->getData();
                // ... save the task

                // Push the update to all connected clients via Mercure
                $hub->publish(new Update(
                    'tasks',
                    $this->renderView('task/stream/new.html.twig', ['task' => $task])
                ));

                return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('task/new.html.twig', [
                'form' => $form,
            ]);
        }
    }

.. code-block:: html+twig

    {# task/index.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <h1>Tasks</h1>

        <div id="tasks" {{ turbo_stream_listen('tasks') }}>
            {# New tasks will automatically appear here for all connected users #}
            {% for task in tasks %}
                <div id="task_{{ task.id }}">{{ task.title }}</div>
            {% endfor %}
        </div>
    {% endblock %}

.. code-block:: html+twig

    {# task/stream/new.html.twig #}
    <turbo-stream action="append" targets="#tasks">
        <template>
            <div id="task_{{ task.id }}">{{ task.title }}</div>
        </template>
    </turbo-stream>

.. seealso::

    Symfony Mercure provides additional features such as `private updates`_
    (to ensure that only authorized users will receive the updates) and
    `async dispatching with Symfony Messenger`_.

<twig:Turbo:Stream:From> Twig Component
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. versionadded:: 3.1

    The ``<twig:Turbo:Stream:From>`` Twig component was introduced in Symfony UX 3.1.

The ``<twig:Turbo:Stream:From>`` Twig component renders a ``<turbo-mercure-stream-source>``
custom HTML element that subscribes to a Mercure topic and delivers incoming
Turbo Stream messages to the page.

.. code-block:: html+twig

    {# subscribe to a string topic #}
    <twig:Turbo:Stream:From topics="chat" />

    {# subscribe to an entity class (resolves its topic URL automatically) #}
    <twig:Turbo:Stream:From topics="App\\Entity\\Book" />

    {# subscribe to a private topic (sets withCredentials on the EventSource) #}
    <twig:Turbo:Stream:From topics="chat" private />

    {# subscribe using a specific transport #}
    <twig:Turbo:Stream:From topics="App\\Entity\\Book" transport="hub2" />

    {# subscribe to multiple topics #}
    <twig:Turbo:Stream:From topics="{{ ['topic_a', 'topic_b'] }}" />

You can also use the ``turbo_stream_from()`` Twig function directly:

.. code-block:: twig

    {{ turbo_stream_from('chat') }}
    {{ turbo_stream_from('chat', private: true) }}
    {{ turbo_stream_from('App\\Entity\\Book', transport: 'hub2') }}

Broadcast Doctrine Entities Update
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Symfony UX Turbo also comes with a convenient integration with Doctrine
ORM.

With a single attribute, your clients can subscribe to creations,
updates and deletions of entities:

.. code-block:: diff

    // src/Entity/Task.php
    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    +use Symfony\UX\Turbo\Attribute\Broadcast;

    #[ORM\Entity]
    +#[Broadcast] // Broadcast entity changes to all connected clients
    class Task
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column(length: 255)]
        private ?string $title = null;
    }

To subscribe to updates of an entity, pass it as parameter of the
``turbo_stream_listen()`` Twig helper:

.. code-block:: html+twig

    <div id="task_{{ task.id }}" {{ turbo_stream_listen(task) }}></div>

Alternatively, you can subscribe to updates made to all entities of a
given class by using its Fully Qualified Class Name:

.. code-block:: html+twig

    <div id="tasks" {{ turbo_stream_listen('App\\Entity\\Task') }}></div>

Finally, create the template that will be rendered when an entity is
created, modified or deleted:

.. code-block:: html+twig

    {# templates/broadcast/Task.stream.html.twig #}
    {% block create %}
        <turbo-stream action="append" targets="#tasks">
            <template>
                <div id="{{ 'task_' ~ id }}">{{ entity.title }} (#{{ id }})</div>
            </template>
        </turbo-stream>
    {% endblock %}

    {% block update %}
        <turbo-stream action="update" targets="#task_{{ id }}">
            <template>
                {{ entity.title }} (#{{ id }}, updated)
            </template>
        </turbo-stream>
    {% endblock %}

    {% block remove %}
        <turbo-stream action="remove" targets="#task_{{ id }}"></turbo-stream>
    {% endblock %}

By convention, Symfony UX Turbo will look for a template named
``templates/broadcast/{ClassName}.stream.html.twig``. This template
**must** contain at least 3 blocks: ``create``, ``update`` and
``remove`` (they can be empty, but they must exist).

Every time an entity marked with the ``Broadcast`` attribute changes,
Symfony UX Turbo will render the associated template and will broadcast
the changes to all connected clients.

Each block must contain a list of Turbo Stream actions. These actions
will be automatically applied by Turbo to the DOM tree of every
connected client. Each template can contain as many actions as needed.

For instance, if the same entity is displayed on different pages, you
can include all actions updating these different places in the template.
Actions applying to non-existing DOM elements will simply be ignored.

The current entity, the string representation of its identifier(s), the
action (``create``, ``update`` or ``remove``) and options set on the
``Broadcast`` attribute are passed to the template as variables:
``entity``, ``id``, ``action`` and ``options``.

Broadcast Conventions and Configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Because Symfony UX Turbo needs access to their identifier, entities have
to either be managed by Doctrine ORM, have a public property named
``id``, or have a public method named ``getId()``.

Symfony UX Turbo will look for a template named after mapping their
Fully Qualified Class Names. For example and by default, if a class
marked with the ``Broadcast`` attribute is named ``App\Entity\Foo``, the
corresponding template will be found in
``templates/broadcast/Foo.stream.html.twig``.

It's possible to configure how namespaces are mapped to templates by
using the ``turbo.broadcast.entity_template_prefixes`` configuration
option. The default is defined as such:

.. code-block:: yaml

    # config/packages/turbo.yaml
    turbo:
        broadcast:
            entity_template_prefixes:
                App\Entity\: broadcast/

Finally, it's also possible to explicitly set the template to use with
the ``template`` parameter of the ``Broadcast`` attribute:

.. code-block:: diff

    // src/Entity/Task.php
    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\UX\Turbo\Attribute\Broadcast;

    #[ORM\Entity]
    -#[Broadcast]
    +#[Broadcast(template: 'my-template.stream.html.twig')]
    class Task
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column(length: 255)]
        private ?string $title = null;
    }

Broadcast Options
^^^^^^^^^^^^^^^^^

The ``Broadcast`` attribute comes with a set of handy options:

-  ``transports`` (``string[]``): a list of transports to broadcast to
-  ``topics`` (``string[]``): a list of topics to use, the default topic
   is derived from the FQCN of the entity and from its id
-  ``template`` (``string``): Twig template to render (see above)

The ``Broadcast`` attribute can be repeated (e.g. you can have multiple
``#[Broadcast]``. This is convenient to render several templates associated with
their own topics for the same change (e.g. the same data is rendered in different
way in the list and in the detail pages).

Options are transport-specific. When using Mercure, some extra options
are supported:

-  ``private`` (``bool``): marks Mercure updates as private
-  ``sse_id`` (``string``): ``id`` field of the SSE
-  ``sse_type`` (``string``): ``type`` field of the SSE
-  ``sse_retry`` (``int``): ``retry`` field of the SSE

The Mercure broadcaster also supports `Expression Language`_ in topics
by starting with ``@=``.

Example:

.. code-block:: diff

    // src/Entity/Task.php
    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\UX\Turbo\Attribute\Broadcast;

    #[ORM\Entity]
    -#[Broadcast]
    +#[Broadcast(
    +    topics: ['@="task_detail_" ~ entity.getId()', 'tasks'],
    +    template: 'task_detail.stream.html.twig',
    +    private: true,
    +)]
    +#[Broadcast(
    +    topics: ['@="task_list_" ~ entity.getId()', 'tasks'],
    +    template: 'task_list.stream.html.twig',
    +    private: true,
    +)]
    class Task
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column(length: 255)]
        private ?string $title = null;
    }

Learn More
----------

.. toctree::
    :maxdepth: 1

    resetting-form
    multiple-submit-buttons
    multiple-transports
    custom-transport
    webpack-encore
    testing

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

Credits
-------

Symfony UX Turbo has been created by `Kévin Dunglas`_. It has been inspired by
`hotwired/turbo-rails`_ and `sroze/live-twig`_.

.. _`Hotwire Turbo`: https://turbo.hotwired.dev
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Single Page Applications`: https://en.wikipedia.org/wiki/Single-page_application
.. _`Symfony Mercure`: https://symfony.com/doc/current/mercure.html
.. _`Symfony UX Turbo screencast series`: https://symfonycasts.com/screencast/turbo
.. _`Stimulus`: https://stimulus.hotwired.dev
.. _`Turbo Reloading When Assets Change`: https://turbo.hotwired.dev/handbook/drive#reloading-when-assets-change
.. _`Read the Turbo Drive documentation`: https://turbo.hotwired.dev/handbook/drive
.. _`Read the Turbo meta tags reference`: https://turbo.hotwired.dev/reference/attributes#meta-tags
.. _`Read the Turbo Frames documentation`: https://turbo.hotwired.dev/handbook/introduction#turbo-frames%3A-decompose-complex-pages
.. _`Mercure`: https://mercure.rocks
.. _`Read the Turbo Streams reference for more details`: https://turbo.hotwired.dev/reference/streams
.. _`the Mercure support`: https://symfony.com/doc/current/mercure.html
.. _`Symfony Docker`: https://github.com/dunglas/symfony-docker
.. _`private updates`: https://symfony.com/doc/current/mercure.html#authorization
.. _`async dispatching with Symfony Messenger`: https://symfony.com/doc/current/mercure.html#async-dispatching
.. _`Kévin Dunglas`: https://dunglas.fr
.. _`hotwired/turbo-rails`: https://github.com/hotwired/turbo-rails
.. _`sroze/live-twig`: https://github.com/sroze/live-twig
.. _`Expression Language`: https://symfony.com/doc/current/components/expression_language.html
