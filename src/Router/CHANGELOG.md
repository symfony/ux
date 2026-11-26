# CHANGELOG

## 3.9.0

- Throw an `InvalidParameterError` when a parameter adds a `.` or `..` segment to the path, like the Symfony `UrlGenerator`
- Move `symfony/dependency-injection` to `require-dev`: it is only needed by `UXRouterBundle`, and is installed by `symfony/framework-bundle` in a Symfony app

## 3.6.0

- Add component
