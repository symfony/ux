Symfony UX Notify
===================

Symfony UX Notify is a Symfony bundle integrating server-sent `native notifications`_
in Symfony applications using `Mercure`_. It is part of `the Symfony UX initiative`_.

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Then, install this bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-notify

    # Don't forget to install the JavaScript dependencies as well and compile
    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

Also make sure you have at least version 3.0 of
`@symfony/stimulus-bridge`_ in your ``package.json`` file.

Usage
-----

To use Symfony UX Notify you must have a `running Mercure server`_ and a
properly configured notifier transport:

.. code-block:: yaml

    // config/packages/notifier.yaml

    framework:
        notifier:
            chatter_transports:
               myMercureChatter: '%env(MERCURE_DSN)%'

.. note::

   It is possible to specify the topics to send the notification in the ``MERCURE_DSN``
   environment variable by specifying the ``topics`` query parameter.
   Otherwise, notifications will be sent to ``https://symfony.com/notifier`` topic.

Then, you can inject the ``NotifierInterface`` service and send messages on the ``chat/myMercureChatter`` channel::

    // ...
    use Symfony\Component\Notifier\Notification\Notification;
    use Symfony\Component\Notifier\NotifierInterface;

    #[AsCommand(name: 'app:flash-sales:announce')]
    class AnnounceFlashSalesCommand extends Command
    {
        public function __construct(private NotifierInterface $notifier)
        {
            parent::__construct();
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $this->notifier->send(new Notification('Flash sales has been started!', ['chat/myMercureChatter']));

            return 0;
        }
    }

Finally, to "listen" and trigger the notifications in the user's browser,
call the ``stream_notifications()`` Twig function anywhere on the page:

.. code-block:: twig

    {{ stream_notifications() }}
    {{ stream_notifications(['/my/topic/1', '/my/topic/2']) }}

.. note::

   Calling ``stream_notifications()`` without parameter will fallback to the
   following unique topic: ``https://symfony.com/notifier``.

Enjoy your server-sent native notifications!

.. figure:: https://github.com/symfony/ux-notify/blob/2.x/doc/native-notification-example.png?raw=true
   :alt: Example of a native notification

Extend the Stimulus Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX Notify allows you to extend its default behavior using a
custom Stimulus controller:

.. code-block:: javascript

    // assets/controllers/mynotify_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        initialize() {
            // guarantees "this" refers to this object in _onConnect
            this._onConnect = this._onConnect.bind(this);
        }

        connect() {
            this.element.addEventListener('notify:connect', this._onConnect);
        }

        disconnect() {
            // You should always remove listeners when the controller is disconnected to avoid side effects
            this.element.removeEventListener('notify:connect', this._onConnect);
        }

        _onConnect(event) {
            // Event sources have just been created
            console.log(event.detail.eventSources);

            event.detail.eventSources.forEach((eventSource) => {
                eventSource.addEventListener('message', (event) => {
                    console.log(event); // You can add custom behavior on each event source
                });
            });
        }
    }

Then in your render call, add your controller as an HTML attribute:

.. code-block:: twig

    {{ stream_notifications(options = {'data-controller': 'mynotify'}) }}

Using another Mercure hub
~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX Notify can be configured to specify the Mercure hub to use:

.. code-block:: yaml

    # config/packages/notify.yaml

    notify:
        # Specifies the Mercure hub to use. Defaults to "mercure.hub.default"
        mercure_hub: mercure.hub.custom

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

However it is currently considered `experimental`_, meaning it is not
bound to Symfony’s BC policy for the moment.

.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`experimental`: https://symfony.com/doc/current/contributing/code/experimental.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
.. _`Mercure`: https://mercure.rocks
.. _`running Mercure server`: https://symfony.com/doc/current/mercure.html#running-a-mercure-hub
.. _`native notifications`: https://developer.mozilla.org/en-US/docs/Web/API/Notifications_API/Using_the_Notifications_API
