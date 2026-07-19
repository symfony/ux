# Logout Link

A link that logs the current user out through a secure POST form.

::: example Demo {"height": "300px", "collapseClass": true}

## Installation

::: installation

## Usage

::: example Usage

> [!WARNING]
> `LogoutLink` logs the user out through a **POST** request protected by a CSRF token (token id `logout`). For it to work, your firewall's logout must **require `POST`** and have **CSRF protection enabled** — otherwise logging out will be rejected.

Restrict the logout route to `POST` so it can't be triggered by a plain link or prefetch:

```php
#[Route('/logout', name: 'app_logout', methods: ['POST'])]
public function logout(): never
{
    throw new \LogicException('This method is intercepted by the logout key on your firewall.');
}
```

Point the firewall's logout at that route (`app_logout`) and enable CSRF protection (this validates the `logout` token the component sends):

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            logout:
                path: app_logout
                enable_csrf: true
```

## Examples

### Specific Firewall

Set the `firewall` prop to log out from a specific firewall instead of the current one.

::: example Specific Firewall {"height": "300px", "collapseClass": true}

## API Reference

::: api-reference
