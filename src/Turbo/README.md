# Symfony UX Turbo

Symfony UX Turbo is a Symfony bundle integrating the [Turbo](https://turbo.hotwire.dev)
library in Symfony applications. It is part of [the Symfony UX initiative](https://symfony.com/ux).

## Installation

Symfony UX Turbo requires PHP 7.2+ and Symfony 4.4+.

Install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-turbo

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

## Usage

Turbo is a library that provides several key features which help building interactive
applications with the least amount of JavaScript possible.

The way Turbo works is by relying as much as possible on HTML and the backend server.
It provides 3 features to achieve this:

### 1. Navigate with Turbo Drive

[Turbo Drive](https://turbo.hotwire.dev/handbook/drive) creates a "single-page app" feel
for traditional server-side applications. It transforms links clicks and forms submissions
in AJAX calls in order to re-render the page without any page load. It's an evolution of
the legacy Turbolinks library.

When using Symfony UX Turbo, Turbo Drive is enabled by default and automatically
watches for links and forms in your application. Read the
[Turbo Drive documentation](https://turbo.hotwire.dev/handbook/drive) to learn how to
configure it.

### 2. Create interactive components with Turbo Frames

[Turbo Frames](https://turbo.hotwire.dev/handbook/frames) allows to decompose pages in parts that
can be updated interactively. Any links and forms inside a frame are captured, and the frame
content is automatically updated after receiving a response. Only that particular section of the page
will be extracted from the response to replace the existing content, regardless of whether the server
provides a full document, or just a fragment containing an updated version of the requested
frame.

This behavior allows to build interactive components that mimic the behaviors of single-page
applications without any added complexity.

For example, using the helpers to build frames:

```twig
{# messages_list.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
<div id="navigation">Links targeting the entire page</div>

<!-- Start a frame: the ID is the way Turbo will update the frame from AJAX responses -->
{{ turbo_frame_start('message_1') }}

    <h1>My message title</h1>
    <p>My message content</p>

    <!--
    When clicking this link, Turbo will catch the link, make an AJAX call to
    /messages/1/edit, find the "message_1" frame in the body of the response and
    replace this frame's content with the new frame's content.

    Here, if /messages/1/edit renders messages_edit.html.twig, the form will be
    displayed inline in the frame.
    -->
    <a href="/messages/1/edit">Edit this message</a>

<!-- End the frame -->
{{ turbo_frame_end() }}

</body>
```

```twig
{# messages_edit.html.twig #}
<body>

<!-- By using the same ID, this response will update the original content to this new one -->
{{ turbo_frame_start('message_1') }}

    <!--
    This form is in a frame: Turbo will catch the form submission, make an AJAX call instead and
    replace the frame's content with the next frame's content.
    -->
    {{ form(message_form) }}

    <!--
    We can also display another link to go back to the list: the original HTML from frame
    "message_1" in the list will be displayed again.
    -->
    <a href="/messages">Back</a>

<!-- End the frame -->
{{ turbo_frame_end() }}
</body>
```

### 3. Create real-time applications with Turbo Streams

[Turbo Streams](https://turbo.hotwire.dev/handbook/streams) are a way to update the content
of HTML pages in real-time (server push) with no JavaScript coding required.

Turbo Streams work by relying on a transport (a way to push messages from the server to
the client in real-time), like websockets or [Symfony Mercure](https://symfony.com/doc/current/mercure.html).

Then, Turbo Streams defines a protocol to **describe changes to apply to a page using HTML**.
For instance, the following snippet of code could be sent over the real-time transport:

```html
<turbo-stream action="append" target="messages">
    <template>
        <div id="message_1">This div will be appended to the element with the DOM ID "messages".</div>
    </template>
</turbo-stream>
```

When this code will be received by the client, Turbo will apply the change to the page. In this example,
a div will be appended to the DOM element with ID "messages".

There are 5 native actions available in Turbo Streams: **append**, **prepend**, **replace**,
**update**, and **remove**. In addition to these 5 actions, you can add custom behavior by using
[Stimulus](https://stimulus.hotwire.dev/), which is native to Symfony UX.

Symfony UX Turbo helps setting up Turbo Streams by providing native adapters for transports (like
Mercure) and by providing helpers to create streams.

**Example: a chat**

For instance, let's imagine we want to build a simple chat system: we want to display messages in real-time
to all clients currently connected when a message is posted. To do so, we can leverage Turbo Streams.

First, we need to configure a transport. In the Symfony ecosystem, we recommend to use
[Mercure](https://symfony.com/doc/current/mercure.html), a protocol based on Server-Sent Events which
provides many very useful features on top of them (authentication, security, topics, ...).

To configure it, [install Mercure](https://symfony.com/doc/current/mercure.html) and configure UX Turbo
to use it:

```yaml
# config/packages/turbo.yaml
turbo:
    streams:
        adapter: turbo.streams.adapter.mercure
        options:
            hub: '%env(MERCURE_PUBLISH_URL)%'
```

Then, let's build the chat view and configure it to listen to the "chat" topic on Mercure:

```php
class ChatController extends AbstractController
{
    /**
     * @Route("/chat", name="chat")
     */
    public function chat(): Response
    {
        return $this->render('chat.html.twig');
    }
}
```

```twig
{# chat.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    {# This line configures Turbo to listen to the "chat" topic on Mercure #}
    {{ turbo_stream_from('chat') }}

    <h1>Chats</h1>

    <div id="messages"></div>
{% endblock %}
```

When a message is posted, we can now send the new DOM to append as a Turbo Stream snippet:

```php
namespace App\Controller;

// ...
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

class ChatController extends AbstractController
{
    // ...

    /**
     * @Route("/publish", name="publish")
     */
    public function publish(PublisherInterface $publisher, Request $request): Response
    {
        // Let's imagine $message is the message received from the request (form, query param, ...)

        $publisher(new Update('chat', '
            <turbo-stream action="append" target="messages">
              <template>
                <p>'.$message.'</p>
              </template>
            </turbo-stream>
        '));

        // Then redirect, display a success message, ...
    }
}
```

Additionnally, to send messages easily in the chat without reloading the page, we could add a Frame around
the message form (not related to Streams but useful still):

```twig
{# chat.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    {# This line configures Turbo to listen to the "chat" topic on Mercure #}
    {{ turbo_stream_from('chat') }}

    <h1>Chats</h1>

    <div id="messages"></div>

    {{ turbo_frame_start('message_form') }}
        <!--
        Because this form is in a frame, when it's submitted, the page won't reload.
        Instead Turbo will send an AJAX request and replace the content of this frame by
        the frame "message_form" in the AJAX response.
        -->
        {{ form(message_form) }}
    {{ turbo_frame_end() }}
{% endblock %}
```

## Backward Compatibility promise

This bundle aims at following the same Backward Compatibility promise as the Symfony framework:
[https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)

However it is currently considered
[**experimental**](https://symfony.com/doc/current/contributing/code/experimental.html),
meaning it is not bound to Symfony's BC policy for the moment.

## Run tests

### PHP tests

```sh
php vendor/bin/phpunit
```

### JavaScript tests

```sh
cd Resources/assets
yarn test
```
