# Logout Link

A link that logs the current user out through a secure POST form.

```twig {"preview":true,"collapseClass":true}
<twig:LogoutLink class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
    Logout
</twig:LogoutLink>
```

## Installation

::: installation

## Usage

```twig
<twig:LogoutLink>
    Logout
</twig:LogoutLink>
```

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

```twig {"preview":true,"collapseClass":true}
<twig:LogoutLink firewall="main" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
    Logout from the main firewall
</twig:LogoutLink>
```

## API Reference

::: api-reference
