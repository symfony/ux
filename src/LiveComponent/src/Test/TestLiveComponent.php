<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\MountedComponent;
use Symfony\UX\TwigComponent\Test\RenderedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestLiveComponent
{
    private bool $performedInitialRequest = false;

    /**
     * @internal
     */
    public function __construct(
        private ComponentMetadata $metadata,
        private array $data,
        private ComponentFactory $factory,
        private KernelBrowser $client,
        private LiveComponentHydrator $hydrator,
        private LiveComponentMetadataFactory $metadataFactory,
        private UrlGeneratorInterface $router,
    ) {
        $this->client->catchExceptions(false);
        $this->data['attributes']['id'] ??= 'in-a-real-scenario-it-would-already-have-one---provide-one-yourself-if-needed';
    }

    public function render(): RenderedComponent
    {
        return new RenderedComponent($this->response()->getContent());
    }

    public function component(): object
    {
        $component = $this->factory->get($this->metadata->getName());
        $componentAttributes = $this->hydrator->hydrate(
            $component,
            $this->props(),
            [],
            $this->metadataFactory->getMetadata($this->metadata->getName()),
        );

        return (new MountedComponent($this->metadata->getName(), $component, $componentAttributes))->getComponent();
    }

    /**
     * @param UserInterface $user
     */
    public function actingAs(object $user, string $firewallContext = 'main'): self
    {
        // we call loginUser() on the raw client in case the entire component requires authentication
        $this->client->loginUser($user, $firewallContext);

        return $this;
    }

    /**
     * @param array<string,mixed>         $arguments
     * @param array<string, UploadedFile> $files
     */
    public function call(string $action, array $arguments = [], array $files = []): self
    {
        return $this->request(['args' => $arguments], $action, $files);
    }

    /**
     * @param array<string,mixed> $arguments
     */
    public function emit(string $event, array $arguments = []): self
    {
        $listeners = AsLiveComponent::liveListeners($this->component());
        $actions = [];

        foreach ($listeners as $listener) {
            if ($listener['event'] === $event) {
                $actions[] = ['name' => $listener['action'], 'args' => $arguments];
            }
        }

        if (!$actions) {
            throw new \InvalidArgumentException(\sprintf('Event "%s" does not exist on component "%s".', $event, $this->metadata->getName()));
        }

        if (1 === \count($listeners)) {
            return $this->call($actions[0]['name'], $arguments);
        }

        return $this->request(['actions' => $actions], '_batch');
    }

    public function set(string $prop, mixed $value): self
    {
        return $this->request(['updated' => [$prop => $value]]);
    }

    public function refresh(): self
    {
        return $this->request();
    }

    public function response(): Response
    {
        return $this->client()->getResponse();
    }

    public function submitForm(array $formValues, ?string $action = null): self
    {
        $flattenValues = $this->flattenFormValues($formValues);

        return $this->request(['updated' => $flattenValues, 'validatedFields' => array_keys($flattenValues)], $action);
    }

    private function request(array $content = [], ?string $action = null, array $files = []): self
    {
        $csrfToken = $this->csrfToken();

        $this->client()->request(
            'POST',
            $this->router->generate(
                $this->metadata->get('route'),
                array_filter([
                    '_live_component' => $this->metadata->getName(),
                    '_live_action' => $action,
                ])
            ),
            parameters: ['data' => json_encode(array_merge($content, ['props' => $this->props()]))],
            files: $files,
            server: $csrfToken ? ['HTTP_X_CSRF_TOKEN' => $csrfToken] : [],
        );

        return $this;
    }

    private function props(): array
    {
        $crawler = $this->client()->getCrawler();

        if (!\count($node = $crawler->filter('[data-live-props-value]'))) {
            throw new \LogicException('A live component action has redirected and you can no longer access the component.');
        }

        return json_decode($node->attr('data-live-props-value'), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function csrfToken(): ?string
    {
        $crawler = $this->client()->getCrawler();

        if (!\count($node = $crawler->filter('[data-live-csrf-value]'))) {
            return null;
        }

        return $node->attr('data-live-csrf-value');
    }

    private function client(): KernelBrowser
    {
        if ($this->performedInitialRequest) {
            return $this->client;
        }

        $mounted = $this->factory->create($this->metadata->getName(), $this->data);
        $props = $this->hydrator->dehydrate(
            $mounted->getComponent(),
            $mounted->getAttributes(),
            $this->metadataFactory->getMetadata($mounted->getName())
        );

        if ('POST' === strtoupper($this->metadata->get('method'))) {
            $this->client->request(
                'POST',
                $this->router->generate($this->metadata->get('route'), [
                    '_live_component' => $this->metadata->getName(),
                ]),
                [
                    'data' => json_encode(['props' => $props->getProps()], flags: \JSON_THROW_ON_ERROR),
                ],
            );
        } else {
            $this->client->request('GET', $this->router->generate(
                $this->metadata->get('route'),
                [
                    '_live_component' => $this->metadata->getName(),
                    'props' => json_encode($props->getProps(), flags: \JSON_THROW_ON_ERROR),
                ]
            ));
        }

        $this->performedInitialRequest = true;

        return $this->client;
    }

    private function flattenFormValues(array $values, string $prefix = ''): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $result += $this->flattenFormValues($value, $prefix.$key.'.');
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }
}
