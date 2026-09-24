<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drives the fixture profile form and the upload endpoints the way the browser controller does.
 */
trait UploadClientTrait
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    private function profilePage(KernelBrowser $client): Crawler
    {
        $client->catchExceptions(false);
        $crawler = $client->request('GET', '/profile');
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        return $crawler;
    }

    /**
     * @return array{csrf: string, policy: string}
     */
    private function field(Crawler $page, string $name): array
    {
        $widget = $page->filter(\sprintf('input[name="profile[%s]"]', $name))->closest('[data-controller="symfony--ux-upload--upload"]');
        self::assertNotNull($widget);

        return [
            'csrf' => (string) $widget->attr('data-symfony--ux-upload--upload-csrf-token-value'),
            'policy' => (string) $widget->attr('data-symfony--ux-upload--upload-policy-token-value'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function directUpload(KernelBrowser $client, Crawler $page, string $fieldName, string $filename, string $mimeType, string $content): array
    {
        $field = $this->field($page, $fieldName);
        $client->request('POST', '/_ux/upload', [
            'filename' => $filename,
            'fileSize' => \strlen($content),
            'mimeType' => $mimeType,
            'digest' => hash('sha256', $content),
            'policyToken' => $field['policy'],
        ], ['file' => $this->file($content, $filename, $mimeType)], ['HTTP_X_CSRF_TOKEN' => $field['csrf']]);

        return $this->decode($client);
    }

    /**
     * Submits the profile form the way the Stimulus controller leaves it: the
     * token field is sent, the file input is not.
     *
     * @param array<string, string> $values raw values by profile field name
     */
    private function submitProfileForm(KernelBrowser $client, Crawler $page, array $values): Crawler
    {
        $form = $page->filter('form[name="profile"]')->form();
        $parameters = $form->getPhpValues();
        foreach ($values as $name => $value) {
            $parameters['profile'][$name] = $value;
        }

        return $client->request($form->getMethod(), $form->getUri(), $parameters);
    }

    private function uploadValue(string $token): string
    {
        return json_encode(['token' => $token], \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client, string $method, string $uri, ?string $csrfToken, array $body): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if (null !== $csrfToken) {
            $server['HTTP_X_CSRF_TOKEN'] = $csrfToken;
        }
        $client->request($method, $uri, server: $server, content: json_encode($body, \JSON_THROW_ON_ERROR));

        return $this->decode($client);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function file(string $content, string $filename, string $mimeType): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ux-upload-functional-');
        self::assertIsString($path);
        file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $filename, $mimeType, test: true);
    }
}
