# [ux.symfony.com](https://ux.symfony.com)

Source code for [ux.symfony.com](https://ux.symfony.com).

## Contributing

### Local Development

1. Install the project:
    ```bash
    git clone git@github.com:symfony/ux
    cd ux/ux.symfony.com/
    ```

2. Install the dependencies:
    ```bash
    symfony composer install
    ```

3. (optional) Configure Docker:
    ```bash
    docker compose up -d
    ```

5. Run database migrations:
    ```bash
    symfony console doctrine:migration:migrate
    ```

6. Populate the database:
    ```bash
    symfony console app:load-data
    ```

7. Compile the Sass files:
    ```bash
    symfony console sass:build --watch
    ```

8. Start the web server:
    ```bash
    symfony server:start -d
    ```


### Running the Test Suite

```bash
symfony php bin/phpunit
```
