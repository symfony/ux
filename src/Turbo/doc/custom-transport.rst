How to Register a Custom Transport
==================================

If you prefer using another protocol than Mercure, you can create custom
transports::

    // src/Turbo/Broadcaster.php
    namespace App\Turbo;

    use Symfony\UX\Turbo\Attribute\Broadcast;
    use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

    class Broadcaster implements BroadcasterInterface
    {
        public function broadcast(object $entity, string $action): void
        {
            // Called every time an object marked with #[Broadcast] changes
            $attribute = (new \ReflectionClass($entity))
                ->getAttributes(Broadcast::class)[0] ?? null;
            // ...
        }
    }

Then a stream source renderer::

    // src/Turbo/MyStreamSourceRenderer.php
    namespace App\Turbo;

    use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
    use Symfony\UX\Turbo\StreamSourceRendererInterface;

    #[AsTaggedItem(index: 'my-transport')]
    class MyStreamSourceRenderer implements StreamSourceRendererInterface
    {
        public function render(string|object|array $topics, array $options = []): string
        {
            // $topics is a string, an object (e.g. a Doctrine entity) or an array
            // of them; convert them into the topic names used by your broadcaster
            $url = 'https://my-transport.example.com/subscribe?'.http_build_query([
                'topic' => $this->resolveTopics($topics),
            ]);
            $private = $options['private'] ?? false;

            return \sprintf(
                '<my-custom-stream-source src="%s"%s></my-custom-stream-source>',
                htmlspecialchars($url, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
                $private ? ' private' : '',
            );
        }
    }

The broadcaster must be registered as a service tagged with
``turbo.broadcaster`` and the stream source renderer must be tagged with
``turbo.stream_source_renderer``. If you enabled `autoconfigure option`_
(it's the case by default), these tags will be added automatically because
these classes implement the ``BroadcasterInterface`` and
``StreamSourceRendererInterface`` interfaces.

The index of the stream source renderer (``my-transport`` in the example) is
the name that templates pass to select the transport:

.. code-block:: twig

    {{ turbo_stream_from('tasks', transport: 'my-transport') }}

The renderer only outputs the HTML element that connects the page to your
transport. You also need to write the JavaScript of that element (e.g. a
custom element) that receives the messages and passes them to Turbo with its
``connectStreamSource()`` function.

.. _`autoconfigure option`: https://symfony.com/doc/current/service_container.html#the-autoconfigure-option
