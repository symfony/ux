# Symfony UX Notify

Symfony UX Notify is a Symfony bundle integrating realtime notifications in Symfony applications
using [Mercure](https://mercure.rocks/).
It is part of [the Symfony UX initiative](https://symfony.com/ux).

## Installation

Symfony UX Notify requires PHP 7.2+ and Symfony 5.3+.

Install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-notify

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

## Usage

To use Symfony UX Notify you must have a [running Mercure server](https://symfony.com/doc/current/mercure.html#running-a-mercure-hub).

Then, inject the `NotifierInterface` service and send messages on the `chat/mercure` channel.

```php
// ...
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

class AnnounceFlashSalesCommand extends Command
{
    protected static $defaultName = 'app:flash-sales:announce';
    private $notifier;

    public function __construct(NotifierInterface $notifier)
    {
        parent::__construct();

        $this->notifier = $notifier;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->notifier->send(new Notification('Flash sales has been started!', ['chat/mercure']));

        return 0;
    }
}
```

Finally, HTML5 notifications could be displayed using the `notify` Twig function:

```twig
{{ stream_notifications(['/my/topic/1', '/my/topic/2'], 'https://my-mercure-server:9090/mercure') }}
{{ stream_notifications() }}

{#
    Calling notify without parameters will fallback to these values:
    - 'https://symfony.com/notifier' as a single topic
    - and mercure.default_hub configuration parameter as hub url
#}
```

### Extend the default behavior

Symfony UX Notify allows you to extend its default behavior using a custom Stimulus controller:

```js
// notify_controller.js

import { Controller } from 'stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('notify:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('notify:connect', this._onConnect);
    }

    _onConnect(event) {
        // Event sources have just been created
        console.log(event.eventSources);

        event.eventSources.forEach((eventSource) => {
            eventSource.addEventListener('message', (event) => {
                console.log(event); // You can add custom behavior on each event source
            });
        });
    }
}
```

Then in your render call, add your controller as an HTML attribute:

```twig
{{ stream_notifications() }}
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
