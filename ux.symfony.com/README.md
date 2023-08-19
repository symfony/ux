# [ux.symfony.com](https://ux.symfony.com)

Source code for [ux.symfony.com](https://ux.symfony.com).

## Contributing

### Local Development

1. Install the project:
    ```bash
    git clone git@github.com:symfony/ux
    cd ux/ux.symfony.com
    ```
2. Install the dependencies:
    ```bash
    composer install
    ```
3. (optional) Configure docker:
    ```bash
    docker compose up -d
    ```
4. Populate the database:
    ```bash
    symfony console app:load-data
    ```
5. Start the web server:
    ```bash
    symfony server:start -d
    ```

6. Compile the Sass files:
    ```bash
    php bin/console sass:build --watch
    ```

### Running the Test Suite

```bash
vendor/bin/phpunit
```
