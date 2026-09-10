# Symfony UX Disclose demo (EasyAdmin)

A runnable Symfony demo showing `symfony/ux-disclose` inside EasyAdmin and in a
plain page, with 1000 clients (name, email, phone). Emails and phones are masked
and disclosed on demand through the rate-limited, audited disclose endpoint.

## Requirements

- PHP >= 8.4
- Composer

## Run

```shell
composer install
php bin/console doctrine:schema:create
php bin/console app:seed-clients
php -S 127.0.0.1:8010 -t public public/index.php
```

Then browse:

- `/` plain page with the client table (emails and phones masked)
- `/admin` EasyAdmin dashboard (HTTP Basic: `admin` / `admin`)
- `/admin/client` EasyAdmin CRUD, email and phone columns disclosed on click

Every click triggers a request to `/disclose/ux/disclose`. The endpoint
authenticates, authorizes via `App\Disclose\ClientDiscloser`, consumes a token
from a rate limiter stored in the database (`disclose.rate_limiter` cache pool),
and writes an audit record for each attempt.

## Notes

- The `DemoSubjectFactory` keys the rate limiter on a per-visitor reference from
  the request. It is intentionally demo-only: a visitor can reset their own
  budget by changing that reference.
- The monorepo testing app `apps/e2e` exercises the disclose endpoint in
  Playwright browser tests against a similar client table.
