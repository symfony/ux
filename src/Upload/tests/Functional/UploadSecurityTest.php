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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Token\UploadTokenHandler;

#[CoversNothing]
final class UploadSecurityTest extends WebTestCase
{
    use UploadClientTrait;

    private const INVALID_REFERENCE = 'The uploaded file reference is invalid or has expired.';

    /**
     * @return iterable<string, array{string}>
     */
    public static function forgedUploadValues(): iterable
    {
        yield 'payload changed by one character' => ['tampered payload'];
        yield 'signature changed by one character' => ['tampered signature'];
        yield 'expired token' => ['expired'];
        yield 'token issued for another field of the form' => ['another field'];
        yield 'token issued for another uploader' => ['another uploader'];
        yield 'resume token instead of an upload token' => ['resume token'];
        yield 'form policy instead of an upload token' => ['policy token'];
    }

    #[DataProvider('forgedUploadValues')]
    public function testFormRejectsAForgedUploadedFileToken(string $forgery): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);

        $value = match ($forgery) {
            'tampered payload' => str_replace('f=cv.txt', 'f=cw.txt', $this->upload($client, $page)),
            'tampered signature' => $this->alterSignature($this->upload($client, $page)),
            'expired' => $this->expire($this->upload($client, $page)),
            'another field' => $this->directUpload($client, $page, 'attachment', 'cv.txt', 'text/plain', 'data')['token'],
            'another uploader' => self::service(UploadTokenHandler::class)->generate(
                new UploadedFileFactory(uploader: 'avatar', fieldName: 'profile.document')->create(self::service(StorageInterface::class)),
                new UploadContext(fieldName: 'profile.document'),
            ),
            'resume token' => $this->init($client, $page, 'document', 'cv.txt', 'text/plain', 4)['resumeToken'],
            'policy token' => $this->field($page, 'document')['policy'],
            default => self::fail(\sprintf('Unknown forgery "%s".', $forgery)),
        };
        self::assertIsString($value);

        $response = $this->submitProfileForm($client, $page, ['age' => '30', 'document' => $this->uploadValue($value)]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
        self::assertSame(self::INVALID_REFERENCE, $response->filter('#profile_document_error1')->text());
    }

    public function testUploadedFileOfOneOwnerIsRejectedForAnother(): void
    {
        $client = static::createClient();
        $client->setServerParameter('HTTP_X_TEST_OWNER', 'alice');
        $page = $this->profilePage($client);
        $token = $this->upload($client, $page);

        $client->setServerParameter('HTTP_X_TEST_OWNER', 'bob');
        $response = $this->submitProfileForm($client, $page, ['age' => '30', 'document' => $this->uploadValue($token)]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
        self::assertSame(self::INVALID_REFERENCE, $response->filter('#profile_document_error1')->text());

        $client->setServerParameter('HTTP_X_TEST_OWNER', 'alice');
        $this->submitProfileForm($client, $page, ['age' => '30', 'document' => $this->uploadValue($token)]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forgedChunkUrls(): iterable
    {
        yield 'signature changed by one character' => ['tampered signature'];
        yield 'upload ID changed under the original signature' => ['tampered upload ID'];
        yield 'expired URL' => ['expired'];
        yield 'signed URL of upload A aimed at upload B' => ['another upload'];
    }

    #[DataProvider('forgedChunkUrls')]
    public function testSignedChunkUrlCannotBeForged(string $forgery): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $csrf = $this->field($page, 'document')['csrf'];
        $first = $this->init($client, $page, 'document', 'a.txt', 'text/plain', 4);
        $second = $this->init($client, $page, 'document', 'b.txt', 'text/plain', 4);
        $storage = self::service(StorageInterface::class);
        $sessions = [$first['uploadId'] => $storage->getMetadata($first['uploadId']), $second['uploadId'] => $storage->getMetadata($second['uploadId'])];
        $parts = parse_url($first['uploadUrl']);
        self::assertIsArray($parts);
        $path = $parts['path'] ?? self::fail('The upload URL has no path.');
        $query = $parts['query'] ?? self::fail('The upload URL has no query.');

        $url = match ($forgery) {
            'tampered signature' => $this->alterSignature($first['uploadUrl']),
            'tampered upload ID' => 'http://localhost'.$this->alterLastCharacter($path).'?'.$query,
            'expired' => self::service(UriSigner::class, 'uri_signer')->sign('http://localhost'.$path, time() - 10),
            'another upload' => 'http://localhost'.str_replace($first['uploadId'], $second['uploadId'], $path).'?'.$query,
            default => self::fail(\sprintf('Unknown forgery "%s".', $forgery)),
        };
        $client->request('PUT', $url, server: ['HTTP_X_CSRF_TOKEN' => $csrf, 'HTTP_X_CHUNK_INDEX' => '0'], content: 'evil');

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Invalid or expired signature'], $this->decode($client));
        foreach ($sessions as $uploadId => $metadata) {
            self::assertSame([], $storage->listChunks($uploadId));
            self::assertSame($metadata, $storage->getMetadata($uploadId));
        }
    }

    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function foreignResumeContexts(): iterable
    {
        yield 'another owner' => ['bob', 'document'];
        yield 'an anonymous visitor' => [null, 'document'];
        yield 'the same owner on another field' => ['alice', 'attachment'];
    }

    #[DataProvider('foreignResumeContexts')]
    public function testResumeTokenIsRefusedOutsideItsContextWithoutDisclosingTheSession(?string $owner, string $fieldName): void
    {
        $client = static::createClient();
        $client->setServerParameter('HTTP_X_TEST_OWNER', 'alice');
        $page = $this->profilePage($client);
        $resumeToken = $this->init($client, $page, 'document', 'private.txt', 'text/plain', 4)['resumeToken'];

        $client->setServerParameter('HTTP_X_TEST_OWNER', $owner ?? '');
        $field = $this->field($page, $fieldName);
        $this->json($client, 'POST', '/_ux/upload/resume', $field['csrf'], ['resumeToken' => $resumeToken, 'policyToken' => $field['policy']]);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Invalid or expired resume token'], $this->decode($client));

        $client->setServerParameter('HTTP_X_TEST_OWNER', 'alice');
        $document = $this->field($page, 'document');
        $this->json($client, 'POST', '/_ux/upload/resume', $document['csrf'], ['resumeToken' => $resumeToken, 'policyToken' => $document['policy']]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array{array<string, string>, string, int, string}>
     */
    public static function alteredPolicies(): iterable
    {
        yield 'maximum size raised' => [['s' => '104857600'], 'large.png', 2048, 'image/png'];
        yield 'allowed types widened' => [['t' => ''], 'notes.txt', 512, 'text/plain'];
    }

    /**
     * @param array<string, string> $changes
     */
    #[DataProvider('alteredPolicies')]
    public function testAlteredFormPolicyIsRefused(array $changes, string $filename, int $size, string $mimeType): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $avatar = $this->field($page, 'avatar');
        parse_str($avatar['policy'], $policy);
        $altered = http_build_query(array_replace($policy, $changes));
        self::assertNotSame($avatar['policy'], $altered);

        $this->json($client, 'POST', '/_ux/upload/init', $avatar['csrf'], ['filename' => $filename, 'fileSize' => $size, 'mimeType' => $mimeType, 'policyToken' => $altered]);
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Invalid or expired upload policy'], $this->decode($client));

        $content = str_repeat('x', $size);
        $client->request('POST', '/_ux/upload', ['filename' => $filename, 'fileSize' => $size, 'mimeType' => $mimeType, 'policyToken' => $altered], ['file' => $this->file($content, $filename, $mimeType)], ['HTTP_X_CSRF_TOKEN' => $avatar['csrf']]);
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertSame(['error' => 'Invalid or expired upload policy'], $this->decode($client));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function uploadsMadeWithoutTheFieldPolicy(): iterable
    {
        yield 'file outside the field limits' => ['notes.txt', 'text/plain', str_repeat('x', 2048)];
        yield 'file within the field limits' => ['avatar.png', 'image/png', (string) file_get_contents(__DIR__.'/../Fixtures/files/valid_image.png')];
    }

    #[DataProvider('uploadsMadeWithoutTheFieldPolicy')]
    public function testUploadMadeWithoutTheFieldPolicyIsRejectedByThatField(string $filename, string $mimeType, string $content): void
    {
        $client = static::createClient();
        $page = $this->profilePage($client);
        $avatar = $this->field($page, 'avatar');

        $client->request('POST', '/_ux/upload', ['filename' => $filename, 'fileSize' => \strlen($content), 'mimeType' => $mimeType], ['file' => $this->file($content, $filename, $mimeType)], ['HTTP_X_CSRF_TOKEN' => $avatar['csrf']]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $unboundToken = $this->decode($client)['token'];

        $response = $this->submitProfileForm($client, $page, [
            'age' => '30',
            'document' => $this->uploadValue($this->upload($client, $page)),
            'avatar' => $this->uploadValue($unboundToken),
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());
        self::assertSame(self::INVALID_REFERENCE, $response->filter('#profile_avatar_error1')->text());
        self::assertCount(0, $response->filter('#profile_document_error1'));
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

    private function upload(KernelBrowser $client, Crawler $page): string
    {
        $token = $this->directUpload($client, $page, 'document', 'cv.txt', 'text/plain', 'data')['token'];
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function init(KernelBrowser $client, Crawler $page, string $fieldName, string $filename, string $mimeType, int $size): array
    {
        $field = $this->field($page, $fieldName);
        $payload = $this->json($client, 'POST', '/_ux/upload/init', $field['csrf'], [
            'filename' => $filename,
            'fileSize' => $size,
            'mimeType' => $mimeType,
            'policyToken' => $field['policy'],
        ]);
        self::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        return $payload;
    }

    private function expire(string $token): string
    {
        parse_str($token, $payload);
        unset($payload['_hash'], $payload['_expiration']);
        $payload['x'] = (string) (time() - 10);
        $signer = self::service(UriSigner::class, 'uri_signer');

        return substr($signer->sign('?'.http_build_query($payload), time() - 10), 1);
    }

    private function alterLastCharacter(string $value): string
    {
        return substr($value, 0, -1).('A' === substr($value, -1) ? 'B' : 'A');
    }

    private function alterSignature(string $signed): string
    {
        $altered = preg_replace_callback('/(_hash=)([^&])/', static fn (array $match): string => $match[1].('A' === $match[2] ? 'B' : 'A'), $signed, 1);
        self::assertIsString($altered);
        self::assertNotSame($signed, $altered);

        return $altered;
    }
}
