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

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Token\UploadTokenHandler;

#[CoversNothing]
final class UploadOverHttpTest extends WebTestCase
{
    use UploadClientTrait;

    public function testDirectUploadTokenReachesTheApplicationControllerWithTheExactBytes(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $content = "direct\0bytes";

        $token = $this->directUpload($client, $page, 'document', 'notes.txt', 'text/plain', $content)['token'];
        $result = $this->submitProfile($client, $page, ['age' => '30', 'document' => $token]);

        self::assertSame(['age' => 30, 'filename' => 'notes.txt', 'size' => \strlen($content), 'sha256' => hash('sha256', $content)], $result);
    }

    public function testChunkedUploadOverSignedUrlsReachesTheApplicationController(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $field = $this->field($page, 'document');
        $content = random_bytes(100 * 1024);

        $init = $this->json($client, 'POST', '/_ux/upload/init', $field['csrf'], [
            'filename' => 'large.bin',
            'fileSize' => \strlen($content),
            'mimeType' => 'application/octet-stream',
            'policyToken' => $field['policy'],
        ]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        self::assertSame(2, $init['config']['totalChunks']);

        foreach (str_split($content, 64 * 1024) as $index => $chunk) {
            $client->request('PUT', $init['uploadUrl'], server: [
                'HTTP_X_CSRF_TOKEN' => $field['csrf'],
                'HTTP_X_CHUNK_INDEX' => (string) $index,
            ], content: $chunk);
            self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        }
        $client->request('POST', $init['uploadUrl'], server: ['HTTP_X_CSRF_TOKEN' => $field['csrf']]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $token = $this->decode($client)['token'];

        $result = $this->submitProfile($client, $page, ['age' => '41', 'document' => $token]);

        self::assertSame(['age' => 41, 'filename' => 'large.bin', 'size' => \strlen($content), 'sha256' => hash('sha256', $content)], $result);
    }

    public function testUploadedFileSurvivesAnInvalidSubmissionWithoutANewTransfer(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $content = 'kept across validation errors';
        $token = $this->directUpload($client, $page, 'document', 'cv.txt', 'text/plain', $content)['token'];

        $invalid = $this->submitProfileForm($client, $page, ['age' => 'not a number', 'document' => $this->uploadValue($token)]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
        $kept = (string) $invalid->filter('input[type="hidden"][name="profile[document]"]')->attr('value');
        self::assertSame($token, json_decode($kept, true, flags: \JSON_THROW_ON_ERROR)['token']);

        $this->submitProfileForm($client, $invalid, ['age' => '28', 'document' => $kept]);

        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        self::assertSame(['age' => 28, 'filename' => 'cv.txt', 'size' => \strlen($content), 'sha256' => hash('sha256', $content)], $this->decode($client));
    }

    public function testAFormSubmittedWithoutJavaScriptReachesTheApplicationController(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $content = "native\0bytes, longer than one 64 KiB chunk: ".str_repeat('x', 70_000);
        $path = $this->file($content, 'notes.txt', 'text/plain')->getPathname();

        $form = $page->filter('form[name="profile"]')->form();
        self::assertSame('multipart/form-data', $page->filter('form[name="profile"]')->attr('enctype'));
        $form['profile[age]'] = '30';
        self::fileField($form, 'profile[document]')->upload($path);
        $client->submit($form);

        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        self::assertSame(['age' => 30, 'filename' => basename($path), 'size' => \strlen($content), 'sha256' => hash('sha256', $content)], $this->decode($client));
    }

    public function testAFileSubmittedWithoutJavaScriptIsNotAskedAgainAfterAnInvalidSubmission(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $path = $this->file('kept without JavaScript', 'cv.txt', 'text/plain')->getPathname();

        $form = $page->filter('form[name="profile"]')->form();
        $form['profile[age]'] = 'not a number';
        self::fileField($form, 'profile[document]')->upload($path);
        $invalid = $client->submit($form);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
        $kept = (string) $invalid->filter('input[type="hidden"][name="profile[document]"]')->attr('value');
        self::assertStringNotContainsString($path, (string) $client->getResponse()->getContent());
        self::assertArrayHasKey('token', json_decode($kept, true, flags: \JSON_THROW_ON_ERROR));

        $this->submitProfileForm($client, $invalid, ['age' => '28', 'document' => $kept]);

        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        self::assertSame(28, $this->decode($client)['age']);
        self::assertSame(\strlen('kept without JavaScript'), $this->decode($client)['size']);
    }

    public function testAFileSubmittedWithoutJavaScriptIsRefusedWhenAnonymousUploadsAreDisabled(): void
    {
        $client = static::createClient(['environment' => 'anonymous_disabled']);
        $page = $this->profilePage($client);

        $form = $page->filter('form[name="profile"]')->form();
        $form['profile[age]'] = '30';
        self::fileField($form, 'profile[document]')->upload($this->file('data', 'notes.txt', 'text/plain')->getPathname());
        $client->submit($form);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
    }

    public function testUploadMutationsRequireTheCsrfToken(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $field = $this->field($page, 'document');
        $init = ['filename' => 'notes.txt', 'fileSize' => 4, 'mimeType' => 'text/plain', 'policyToken' => $field['policy']];

        $this->json($client, 'POST', '/_ux/upload/init', null, $init);
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Invalid CSRF token'], $this->decode($client));

        $this->json($client, 'POST', '/_ux/upload/init', 'forged-token', $init);
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());

        $client->request('POST', '/_ux/upload', ['filename' => 'notes.txt', 'fileSize' => 4, 'mimeType' => 'text/plain'], ['file' => $this->file('data', 'notes.txt', 'text/plain')]);
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());

        $this->json($client, 'POST', '/_ux/upload/init', $field['csrf'], $init);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testAnonymousUploadsAreRejectedWhenTheApplicationDoesNotAllowThem(): void
    {
        $client = static::createClient(['environment' => 'anonymous_disabled']);
        $page = $this->profilePage($client);
        $field = $this->field($page, 'document');

        $this->json($client, 'POST', '/_ux/upload/init', $field['csrf'], ['filename' => 'notes.txt', 'fileSize' => 4, 'mimeType' => 'text/plain', 'policyToken' => $field['policy']]);
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Anonymous uploads are disabled'], $this->decode($client));

        $this->directUpload($client, $page, 'document', 'notes.txt', 'text/plain', 'data');
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Anonymous uploads are disabled'], $this->decode($client));
    }

    public function testNamedUploaderPolicyIsEnforcedOnTheRealRoutes(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $avatar = $this->field($page, 'avatar');

        $this->json($client, 'POST', '/_ux/upload/init', $avatar['csrf'], ['filename' => 'notes.txt', 'fileSize' => 4, 'mimeType' => 'text/plain', 'policyToken' => $avatar['policy']]);
        self::assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        self::assertStringContainsString('text/plain', $this->decode($client)['error']);

        $this->json($client, 'POST', '/_ux/upload/init', $avatar['csrf'], ['filename' => 'large.png', 'fileSize' => 2048, 'mimeType' => 'image/png', 'policyToken' => $avatar['policy']]);
        self::assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        self::assertStringContainsString('1024 bytes', $this->decode($client)['error']);

        $this->json($client, 'POST', '/_ux/upload/init', $avatar['csrf'], ['filename' => 'small.png', 'fileSize' => 512, 'mimeType' => 'image/png', 'policyToken' => $avatar['policy']]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testRemovalOverHttpDeletesTheTemporaryObject(): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $field = $this->field($page, 'document');
        $token = $this->directUpload($client, $page, 'document', 'draft.txt', 'text/plain', 'draft')['token'];
        $upload = self::service(UploadTokenHandler::class)->resolve($token, new UploadContext(fieldName: 'profile.document'));
        self::assertNotNull($upload);
        $storage = self::service(StorageInterface::class);
        self::assertTrue($storage->exists($upload->getStoragePath()));

        $this->json($client, 'DELETE', '/_ux/upload/remove', $field['csrf'], ['token' => $token, 'policyToken' => $field['policy']]);

        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        self::assertFalse($storage->exists($upload->getStoragePath()));
        self::assertNull($storage->getMetadata($upload->getId()));
    }

    private static function fileField(Form $form, string $name): FileFormField
    {
        $field = $form[$name];
        self::assertInstanceOf(FileFormField::class, $field);

        return $field;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private static function service(string $class, ?string $id = null): object
    {
        $service = static::getContainer()->get($id ?? $class);
        self::assertInstanceOf($class, $service);

        return $service;
    }

    /**
     * @param array{age: string, document: string} $values
     *
     * @return array<string, mixed>
     */
    private function submitProfile(KernelBrowser $client, Crawler $page, array $values): array
    {
        $this->submitProfileForm($client, $page, ['age' => $values['age'], 'document' => $this->uploadValue($values['document'])]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        return $this->decode($client);
    }
}
