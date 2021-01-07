# Contributing

Start the test app:

    $ composer install
    $ php tests/app/public/index.php doctrine:schema:create
    $ php -S localhost:8000 -t tests/app/public
    $ cd tests/app
    $ yarn encore dev --watch

## Convenient endpoints:

* `http://localhost:8000`: basic features
* `http://localhost:8000/chat`: chat using Turbo Streams
* `http://localhost:8000/books`: broadcast

## Run tests

### PHP tests

    php vendor/bin/simple-phpunit

### JavaScript tests

    cd Resources/assets
    yarn test
