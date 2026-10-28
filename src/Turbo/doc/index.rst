Symfony UX Turbo
================

.. admonition:: Screencast
    :class: screencast

    Do you prefer video tutorials? Check out the `Symfony UX Turbo screencast series`_.

Symfony UX Turbo integrates the `Hotwire Turbo`_ library in Symfony
applications. It is part of `the Symfony UX initiative`_.

Turbo gives your application the fast and fluid feel of
`Single Page Applications`_ while you keep rendering HTML on the server with
controllers and Twig templates. You don't write JavaScript to fetch data or
to update the page: Turbo does it for you.

On top of the Turbo library, this bundle provides Twig functions and
components to generate Turbo markup, helpers to detect Turbo requests in your
controllers and integrations with `Symfony Mercure`_ and Doctrine to push page
updates to all connected users in real time.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-turbo

How Turbo Works
---------------

HTML Over the Wire
~~~~~~~~~~~~~~~~~~

In a traditional application, every click on a link or every form submission
makes the browser load a new page: it downloads the HTML, discards the current
page, parses the CSS and JavaScript again and renders everything from scratch.
Single Page Applications avoid this by loading the page once and then asking
the server only for data (usually JSON), which JavaScript code turns into HTML
in the browser. This feels faster, but you must build and maintain a second
application in JavaScript.

Turbo follows a third approach called *HTML over the wire*: the server keeps
rendering HTML, as usual in Symfony applications, but Turbo requests it in the
background and inserts it into the current page instead of loading a new page.
Your templates, controllers, forms, security and validation stay the same, and
the user doesn't see full page reloads.

Drive, Frames and Streams
~~~~~~~~~~~~~~~~~~~~~~~~~

Turbo provides three tools. Each one updates a different amount of the page:

**Turbo Drive**
    Handles navigation for the entire page. When the user clicks a link or
    submits a form, Turbo Drive makes the request in the background and
    replaces the ``<body>`` of the page with the one of the response. It's
    enabled by default, so you get it for free when installing this bundle.

**Turbo Frames**
    Divide the page into independent sections. Links and forms inside a
    ``<turbo-frame>`` element only replace the contents of that frame with the
    matching frame of the response. The rest of the page doesn't change.

**Turbo Streams**
    Make precise changes to one or more parts of the page at the same time.
    The server sends *stream messages* (append this item to that list, remove
    that row, update this counter, etc.) either as the response to a form
    submission or pushed in real time (e.g. with Mercure) to all users who are
    viewing the page.

=============  ====================================  ==============================================
Tool           What it updates                       Use it when
=============  ====================================  ==============================================
Turbo Drive    The entire page, without a reload     Always; it's enabled by default
Turbo Frames   One section, which is fully reloaded  Inline editing, pagination, tabs, lazy loading
Turbo Streams  Several parts, with precise actions   Changes in several places, real-time updates
=============  ====================================  ==============================================

When choosing between Frames and Streams, think about how you'd describe the
change. If it's "this section of the page reloads", use a Turbo Frame. If it's
"add this item to the list, update the counter and reset the form", use Turbo
Streams.

When Not to Use Turbo
~~~~~~~~~~~~~~~~~~~~~

Turbo works best for applications whose pages are mostly rendered on the
server: admin backends, CRUD applications, e-commerce sites, dashboards, blogs,
etc. Consider other tools in these cases:

* The interface keeps a lot of state in the browser and changes it on every
  keystroke or mouse movement (e.g. rich text editors, drawing tools,
  interactive maps or spreadsheets). Each change would need a request to the
  server, so it's better to build those parts with JavaScript. Use
  `Stimulus`_ controllers to integrate them in your Turbo pages;
* The application must work offline, or it's already built as a Single Page
  Application that consumes a JSON API;
* You want a component that re-renders itself as the user types (e.g. a form
  with fields that depend on each other or a live search). It's possible with
  Turbo, but `Symfony UX LiveComponent`_ is designed for this use case.

Usage
-----

Turbo Drive
~~~~~~~~~~~

Turbo Drive watches for link clicks and form submissions, performs them in the
background and updates the page without doing a full reload. It's
automatically enabled when you install Symfony UX Turbo.

Getting Started with Turbo Drive
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Most applications work with Turbo Drive without changes, but there are three
things to be aware of:

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
If you're using Reprise, see :doc:`reprise`.

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

A Turbo Frame is a section of the page wrapped in a ``<turbo-frame>`` element
with a unique ``id``. Links and forms inside the frame keep working as usual,
but Turbo only replaces the contents of that frame with the frame that has the
same ``id`` in the response. The rest of the page doesn't change.

Use frames for self-contained parts of the page, such as editing a row of a
list without leaving the list, paginating a block or switching tabs:

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

When the user clicks on "Edit this task", Turbo requests the edit page,
looks for the ``<turbo-frame id="task_42">`` element in it and replaces the
contents of the current frame with it. Submitting the form works the same way,
as long as the response contains a frame with that ``id``, so the user edits
the task without leaving the list. The edit page also works as a regular full
page when opened directly.

Frames can also be **lazy-loaded**: they start empty and Turbo fetches their
contents from the URL defined in their ``src`` attribute. This is useful to
defer slow parts of a page or to cache them independently.

<twig:Turbo:Frame>
^^^^^^^^^^^^^^^^^^

The ``<twig:Turbo:Frame>`` Twig component renders a ``<turbo-frame>``
element. Use it to wrap the contents of a frame or as a lazy-loading container
with the ``src`` attribute:

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

Detecting Turbo Frame Requests
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Inject the ``TurboFrame`` service to detect whether the current request
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

With Turbo Streams, the server sends pieces of HTML together with an
instruction that tells Turbo what to do with them and where in the page.

.. _turbo-stream-anatomy:

Anatomy of a Stream Message
^^^^^^^^^^^^^^^^^^^^^^^^^^^

This is a stream message that adds a new task to a list:

.. code-block:: html

    <turbo-stream action="append" targets="#task_list">
        <template>
            <li id="task_42">Buy some milk</li>
        </template>
    </turbo-stream>

Each message has three parts:

``action``
    What to do: append, replace, remove, etc.
``targets``
    A CSS selector of the element(s) to change. Instead of ``targets``, you
    can also use ``target`` with the ``id`` of a single element, without the
    ``#`` (e.g. ``target="task_list"``). The helpers of Symfony UX Turbo
    generate ``targets``.
``<template>``
    The new HTML contents, which must always be wrapped by a ``<template>``
    element. Actions that don't insert contents (``remove`` and ``refresh``)
    don't include it.

When Turbo receives this message, it looks for the ``#task_list`` element in
the page and adds the ``<li>`` element at the end of it. Nothing else in the
page changes. If no element matches the target, Turbo ignores the message
without displaying any error, so check the ``id`` attributes of your page if
an update doesn't show up.

A single response can contain several messages. Turbo applies them in order.

Common Actions
^^^^^^^^^^^^^^

These are the actions you'll use most of the time:

===========  ==============================================  ======================================
Action       What it does to the target                      Typical use
===========  ==============================================  ======================================
``append``   Adds the contents inside it, at the end         Add a comment or a chat message
``prepend``  Adds the contents inside it, at the beginning   Add an item to a "newest first" list
``replace``  Replaces the entire element                     Update an edited row, show form errors
``update``   Replaces the contents and keeps the element     Update a counter or a badge
``remove``   Removes the element                             Delete a row or close an alert
===========  ==============================================  ======================================

The difference between ``replace`` and ``update`` is a common source of
errors. If your template includes the element itself, with its ``id``
(e.g. ``<li id="task_42">...</li>``), use ``replace``. If it only includes
the inner contents (e.g. ``42``), use ``update``. If you use ``replace`` with
contents that don't include the ``id``, the following messages won't find the
target anymore.

Turbo also provides the ``before``, ``after`` and ``refresh`` actions and a
*morphing* mode for ``replace`` and ``update``. They are all explained in the
:ref:`list of stream actions <turbo-stream-actions>`.

How Stream Messages Reach the Page
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Turbo receives stream messages through two different channels:

**In the response to a form submission**
    The user submits a form and the controller responds with stream messages
    instead of a redirect. Only the user who submitted the form sees the
    changes. This is explained in the next section.

**Broadcast in real time**
    The server publishes stream messages to a hub, such as Mercure, at any
    moment, and every browser displaying the page receives them. This is
    useful for chats, notifications and dashboards. This is explained in the
    :ref:`Broadcasting <turbo-broadcasting>` section.

The messages are the same in both cases. Put the HTML of each item in a Twig
template fragment (e.g. ``task/_task.html.twig``) and include it from the
regular page, from the stream responses and from the broadcast templates, so
you don't duplicate any HTML.

.. _turbo-stream-responses:

Turbo Stream Responses
^^^^^^^^^^^^^^^^^^^^^^

When a form uses the ``POST``, ``PUT``, ``PATCH`` or ``DELETE`` method, Turbo
adds the ``text/vnd.turbo-stream.html`` MIME type to the ``Accept`` header of
the request. Symfony UX Turbo registers this MIME type as the
``turbo_stream`` request format (also available as the
``TurboBundle::STREAM_FORMAT`` constant), so controllers can check it with
``$request->getPreferredFormat()`` and respond with stream messages. Other
requests (e.g. from a browser with JavaScript disabled) keep receiving the
usual redirect, so your application keeps working without Turbo.

.. tip::

    Links and ``GET`` forms don't accept stream responses by default. Add the
    ``data-turbo-stream`` attribute to them to change this.

There are two ways to return stream messages from a controller:

**Option 1: inline with** ``TurboStreamResponse``

Use ``TurboStreamResponse`` to build stream actions directly from the
controller. It sets the ``text/vnd.turbo-stream.html`` content type itself, and
it needs no template for simple actions like removing an element::

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

Each method (``append()``, ``prepend()``, ``replace()``, ``update()``,
``remove()``, ``before()``, ``after()``, ``refresh()`` and ``action()``) adds a
``<turbo-stream>`` element to the response body and returns the same response.
Chain the calls to send several actions at once. For example, after a form
submit, append the new task to the list, update the task counter and replace
the form with an empty one::

    // src/Controller/TaskController.php
    use Symfony\UX\Turbo\TurboBundle;
    use Symfony\UX\Turbo\TurboStreamResponse;

    if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
        $emptyForm = $this->createForm(TaskType::class, new Task());

        return (new TurboStreamResponse())
            ->append('#task_list', $this->renderView('task/_task.html.twig', ['task' => $task]))
            ->update('#task_count', (string) $taskRepository->count([]))
            ->replace('form[name=task]', $this->renderView('task/_form.html.twig', ['form' => $emptyForm]));
    }

``replace()`` and ``update()`` also accept a third ``$morph`` argument. Pass
``true`` to apply the change with morphing (``method="morph"``).

.. caution::

    The ``$html`` argument is inserted into the response as-is: it is **not**
    escaped. Render it with ``renderView()``, so that Twig escapes the
    variables, or escape any user-provided value yourself with
    ``htmlspecialchars()``.

**Option 2: dedicated template with** ``renderBlock()``

Use ``renderBlock()`` to render a specific Twig block from a template as a
Turbo Stream response. This is the better fit when the stream markup belongs
with the page template it updates, for example when it uses the
``<twig:Turbo:Stream:*>`` components. Call
``$request->setRequestFormat(TurboBundle::STREAM_FORMAT)`` so that the response
gets the Turbo Stream content type. Otherwise, the response is sent as
``text/html`` and Turbo doesn't apply the stream messages::

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

.. _turbo-stream-actions:

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

You can also use the ``TurboStreamResponse::action()`` method from a controller.
Its fourth argument adds attributes to the ``<turbo-stream>`` element. Set a
value to ``null`` to add a boolean attribute::

    // src/Controller/TaskController.php
    use Symfony\UX\Turbo\TurboBundle;
    use Symfony\UX\Turbo\TurboStreamResponse;

    if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
        return (new TurboStreamResponse())
            ->action(
                'my_action',
                '#task_'.$task->getId(),
                $this->renderView('task/_task.html.twig', ['task' => $task]),
                ['data-duration' => 300, 'data-highlight' => null],
            );
    }

    // output:
    // <turbo-stream action="my_action" targets="#task_42" data-duration="300" data-highlight>
    //     <template><li id="task_42">My task</li></template>
    // </turbo-stream>

.. seealso::

    `Read the Turbo Streams reference for more details`_.

.. _turbo-broadcasting:

Broadcasting
~~~~~~~~~~~~

Broadcasting sends stream messages to every browser that displays a page, not
only to the user who made the request. Use it to build real-time features such
as chats, notifications or dashboards. The messages are the same ones described
in :ref:`Anatomy of a Stream Message <turbo-stream-anatomy>`; only the channel
that delivers them changes. Symfony UX Turbo uses the `Mercure`_ protocol by
default, but supports any other transport.

Start by installing `the Mercure support`_ on your project:

.. code-block:: terminal

    $ composer require symfony/mercure-bundle

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

The following example builds a real-time task list. When a user creates a task,
it appears in the list of all users who are viewing it. First, publish the
stream messages to a Mercure *topic* after saving the task::

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

                // the first argument is the topic: every page subscribed to
                // 'tasks' receives the stream messages rendered by the template
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

    {# templates/task/stream/new.html.twig #}
    <turbo-stream action="append" targets="#task_list">
        <template>
            {{ include('task/_task.html.twig') }}
        </template>
    </turbo-stream>

Then, subscribe the page to that topic with the ``<twig:Turbo:Stream:From>``
component (explained in the next section). When Turbo receives the messages,
it applies them to the page:

.. code-block:: html+twig

    {# templates/task/index.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        <h1>Tasks</h1>

        <twig:Turbo:Stream:From topics="tasks" />

        <ul id="task_list">
            {% for task in tasks %}
                {{ include('task/_task.html.twig') }}
            {% endfor %}
        </ul>
    {% endblock %}

    {# templates/task/_task.html.twig #}
    <li id="task_{{ task.id }}">{{ task.title }}</li>

The ``task/stream/new.html.twig`` template is the same you'd use to respond to
the form submission with stream messages. Only the delivery changes: instead of
returning it in the HTTP response to one user, Mercure sends it to all pages
subscribed to the ``tasks`` topic.

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

.. caution::

    Double the backslashes when passing a Fully Qualified Class Name. Twig
    reads ``\E`` inside a string literal as an escape sequence: Twig 3 drops
    the backslash, so ``'App\Entity\Book'`` turns into the topic
    ``AppEntityBook``, which no broadcast publishes to. The stream then stays
    silent rather than reporting an error. Twig 4 keeps the backslash and the
    class name reaches Turbo intact.

You can also use the ``turbo_stream_from()`` Twig function directly:

.. code-block:: twig

    {{ turbo_stream_from('chat') }}
    {{ turbo_stream_from('chat', private: true) }}
    {{ turbo_stream_from('App\\Entity\\Book', transport: 'hub2') }}

.. versionadded:: 3.6

    Support for the Mercure protocol 1.0 was introduced in Symfony UX 3.6.

When the hub speaks the Mercure protocol 1.0 (``protocol_version: '1.0'`` in the
MercureBundle configuration, the default since 0.6), ``turbo_stream_from()``
subscribes with the query parameters of this protocol, and listening to every
entity of a class uses a URL Pattern
(``https://symfony.com/ux-turbo/App%5CEntity%5CBook/:id``). The deprecated
``turbo_stream_listen()`` function does not support this protocol.

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

To subscribe to updates of an entity, pass it to the ``turbo_stream_from()``
Twig function:

.. code-block:: html+twig

    {{ turbo_stream_from(task) }}
    <div id="task_{{ task.id }}">{{ task.title }}</div>

Alternatively, you can subscribe to updates made to all entities of a
given class by using its Fully Qualified Class Name:

.. code-block:: html+twig

    {{ turbo_stream_from('App\\Entity\\Task') }}
    <div id="tasks">
        {# ... #}
    </div>

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
    reprise
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
.. _`Symfony UX LiveComponent`: https://symfony.com/bundles/ux-live-component/current/index.html
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
