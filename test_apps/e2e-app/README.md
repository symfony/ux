# E2E App

This is a Symfony application designed for end-to-end testing. 

It serves for testing UX packages in a real-world scenario, 
to ensure they work as expected for multiple Symfony versions and various browsers.

## Requirements

- Symfony CLI
- PHP 8.2 or higher
- Docker and Docker Compose
- Composer

## Installation

```shell
docker compose up -d
symfony php ../.github/build-packages.php

SYMFONY_REQUIRE=6.4.* symfony composer update
# or...
SYMFONY_REQUIRE=7.3.* symfony composer update
```

## Usage

```shell
symfony serve
```

The application will be available at `http://localhost:9876`.
