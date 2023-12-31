# [ux.symfony.com](https://ux.symfony.com)

Source code for [ux.symfony.com](https://ux.symfony.com).

## Installation

### Source code

Install the project:
```bash
git clone git@github.com:symfony/ux
cd ux/ux.symfony.com/
```

Install the dependencies:
```bash
symfony composer install
```

### Services

(optional) Configure Docker to launch Mercure
```bash
docker compose up -d
```

### Database

Run database migrations:
```bash
symfony console doctrine:migration:migrate
```

Populate the database:
```bash
symfony console app:load-data
```

### Assets

Download the importmap packages locally:
```bash
symfony console importmap:install
```

Compile the Sass files:
```bash
symfony console sass:build

# (optional) Add the --watch flag to automatically recompile the Sass files on change.
symfony console sass:build --watch
```

### Local server

Start the local web server (in background):
```bash
symfony server:start -d
```

## Testing

```bash
symfony php bin/phpunit
```
