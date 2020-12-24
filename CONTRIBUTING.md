# Contributing

Start the test app:

    $ composer install
    $ php -S localhost:8001 -t tests/app/public
    $ cd tests/app
    $ php public/index.php doctrine:schema:create
    $ yarn encore dev --watch
