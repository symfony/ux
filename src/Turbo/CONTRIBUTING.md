# Contributing

Start a Mercure Hub:

    $ docker run \
        -e SERVER_NAME=:3000 \
        -e MERCURE_PUBLISHER_JWT_KEY='!ChangeMe!' \
        -e MERCURE_SUBSCRIBER_JWT_KEY='!ChangeMe!' \
        -p 3000:3000 \
        dunglas/mercure caddy run -config /etc/caddy/Caddyfile.dev

Install the test app:

    $ composer install
    $ cd tests/app
    $ yarn install
    $ yarn build
    $ php public/index.php doctrine:schema:create

Start the test app:

    $ php -S localhost:8000 -t public

## Convenient endpoints:

-   `http://localhost:8000`: basic features
-   `http://localhost:8000/chat`: chat using Turbo Streams
-   `http://localhost:8000/books`: broadcast
-   `http://localhost:8000/authors`: broadcast
-   `http://localhost:8000/artists`: broadcast
-   `http://localhost:8000/songs`: broadcast

## Run tests

    $ php vendor/bin/simple-phpunit
