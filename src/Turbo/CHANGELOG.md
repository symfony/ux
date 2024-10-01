# CHANGELOG

## 2.21.0

-   Add `Helper/TurboStream::append()` et al. methods
-   Add `TurboStreamResponse`
-   Add `<twig:Turbo:Stream:*>` components

## 2.19.0

-   Fix Doctrine proxies are not Broadcasted #3139

## 2.15.0

-   Add Turbo 8 support #1476
-   Fix missing `use` statement used during broadcast #1475

## 2.14.2

-   Fix using old `ClassUtils` class that's not used in newer versions of Doctrine

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

## 2.9.0

-   Minimum PHP version is now 8.1

-   Add support for symfony/asset-mapper

-   Replace `symfony/webpack-encore-bundle` by `symfony/stimulus-bundle` in dependencies

## 2.7.0

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.1

-   The `symfony/ux-turbo-mercure` package was abandoned and moved into this package.
    If you were previously using `symfony/ux-turbo-mercure`, you can remove it
    and only install mecure-bundle:

    ```
    composer require symfony/mercure-bundle
    composer remove symfony/ux-turbo-mercure
    ```

    After upgrading this package to 2.6.1, you should have a new entry in
    `assets/controllers.json` called `mercure-turbo-stream`. Change
    `enabled: false` to `enabled: true`.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.

-   The directory structure of the bundle was updated to match modern best-practices.

## 2.3

-   The `Broadcast` attribute can now be repeated, this is convenient to render several Turbo Streams Twig templates for the same change

## 2.2

-   The topics defined in the `Broadcast` attribute now support expression language when prefixed with `@=`.

## 2.1

-   `TurboStreamResponse` and `AddTurboStreamFormatSubscriber` have been removed, use native content negotiation instead:

    ```php
    use Symfony\UX\Turbo\TurboBundle;

    class TaskController extends AbstractController
    {
        public function new(Request $request): Response
        {
            // ...
            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                $response = $this->render('task/success.stream.html.twig', ['task' => $task]);
            } else {
                $response = $this->render('task/success.html.twig', ['task' => $task]);
            }

            return $response->setVary('Accept');
        }
    }
    ```

## 2.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.
-   Support added for Symfony 6
-   `@hotwired/turbo` version bumped to stable 7.0.

## 1.3

-   Package introduced! The new `symfony/ux-turbo` and `symfony/ux-turbo-mercure`
    were introduced.
