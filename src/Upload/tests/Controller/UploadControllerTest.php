<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Form\FileUploadType;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Tests\Mock\MockStorage;
use Symfony\UX\Upload\Token\ResumeTokenHandler;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\Url\SymfonyUploadUrlGenerator;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

final class UploadControllerTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];
    private MockStorage $storage;
    private UploadUrlGeneratorInterface $uploadUrlGenerator;
    private UriSigner $uriSigner;
    private Uploader $uploader;

    protected function setUp(): void
    {
        $this->storage = new MockStorage();

        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')
            ->willReturnCallback(static fn (string $route, array $params = []) => 'http://example.com/upload/'.$params['uploadId']);

        $this->uriSigner = new UriSigner('test-secret-key');

        $this->uploadUrlGenerator = new SymfonyUploadUrlGenerator(
            $router,
            $this->uriSigner,
        );

        $dispatcher = new EventDispatcher();
        $this->uploader = new Uploader(
            $this->storage,
            $this->uploadUrlGenerator,
            $dispatcher,
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }
    }

    public function testDirectUploadReturnsResolvableTokenAndExactBytes(): void
    {
        $content = "one request\0payload";
        $request = $this->directRequest($content, [
            'filename' => 'direct.txt',
            'fileSize' => \strlen($content),
            'mimeType' => 'text/plain',
            'hash' => hash('sha256', $content),
            'hashAlgorithm' => 'sha256',
            'digest' => hash('sha256', $content),
        ]);

        $response = $this->createController()->direct($request);
        $payload = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotSame('', $payload['uploadId']);
        self::assertSame([
            'filename' => 'direct.txt',
            'mimeType' => 'text/plain',
            'size' => \strlen($content),
        ], $payload['meta']);
        $completed = new UploadTokenHandler($this->uriSigner, $this->storage)->resolve($payload['token']);
        self::assertNotNull($completed);
        self::assertSame($payload['uploadId'], $completed->id);
        $stream = $completed->openStream();
        try {
            self::assertSame($content, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testDirectUploadEnforcesCsrfPolicyNamedUploaderAndContext(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturnCallback(
            static fn (CsrfToken $token): bool => 'ux_upload' === $token->getId() && 'valid-token' === $token->getValue(),
        );
        $contextResolver = $this->contextResolver('owner-1', 'tenant-1');
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $policyToken = $policySigner->issue('avatar', 16, ['text/plain'], 1, 'profile.avatar');
        $namedUploader = new Uploader($this->storage, $this->uploadUrlGenerator, new EventDispatcher(), name: 'avatar');
        $uploaders = $this->createStub(ContainerInterface::class);
        $uploaders->method('has')->willReturnCallback(static fn (string $id): bool => 'avatar' === $id);
        $uploaders->method('get')->willReturn($namedUploader);
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage, contextResolver: $contextResolver);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler,
            $this->storage,
            $uploaders,
            csrfTokenManager: $csrf,
            contextResolver: $contextResolver,
            policySigner: $policySigner,
            allowAnonymous: true,
        );
        $parameters = [
            'filename' => 'avatar.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'uploader' => 'ignored-by-policy',
            'policyToken' => $policyToken,
            'digest' => hash('sha256', 'data'),
        ];

        self::assertSame(Response::HTTP_FORBIDDEN, $controller->direct($this->directRequest('data', $parameters))->getStatusCode());

        $request = $this->directRequest('data', $parameters);
        $request->headers->set('X-CSRF-Token', 'valid-token');
        $response = $controller->direct($request);
        $payload = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $completed = $tokenHandler->resolve($payload['token'], new UploadContext('owner-1', 'tenant-1', 'profile.avatar'));
        self::assertNotNull($completed);
        self::assertSame('avatar', $completed->uploader);
        self::assertSame('owner-1', $completed->getOwnerId());
        self::assertSame('tenant-1', $completed->getTenantId());
        self::assertSame('profile.avatar', $completed->getFieldName());
    }

    public function testDirectUploadReturns413ForDeclaredOrTransmittedOversize(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->uploadUrlGenerator,
            new EventDispatcher(),
            chunkSize: 4,
        );
        $controller = new UploadController(
            $uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            allowAnonymous: true,
        );

        self::assertSame(
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
            $controller->direct($this->directRequest('data', ['filename' => 'x', 'fileSize' => 5, 'mimeType' => 'text/plain']))->getStatusCode(),
        );
        self::assertSame(
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
            $controller->direct($this->directRequest(str_repeat('x', 70_000), ['filename' => 'x', 'fileSize' => 4, 'mimeType' => 'text/plain']))->getStatusCode(),
        );
    }

    public function testDirectUploadIsRateLimitedAndRejectsInvalidInput(): void
    {
        $limiter = new RateLimiterFactory(
            ['id' => 'ux_upload_direct', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            initRateLimiter: $limiter,
            allowAnonymous: true,
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->direct(new Request())->getStatusCode());
        self::assertSame(
            Response::HTTP_TOO_MANY_REQUESTS,
            $controller->direct($this->directRequest('data', ['filename' => 'x', 'fileSize' => 4, 'mimeType' => 'text/plain']))->getStatusCode(),
        );
    }

    public function testDirectUploadRejectsMalformedMetadataAndPolicyViolations(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            policySigner: $policySigner,
            allowAnonymous: true,
        );

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $controller->direct($this->directRequest('data', ['filename' => 'x', 'fileSize' => 'invalid', 'mimeType' => 'text/plain']))->getStatusCode(),
        );
        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $controller->direct($this->directRequest('data', [
                'filename' => 'x',
                'fileSize' => 4,
                'mimeType' => 'text/plain',
                'policyToken' => 'invalid',
            ]))->getStatusCode(),
        );

        $policyToken = $policySigner->issue('default', 4, ['text/plain'], 1);
        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $controller->direct($this->directRequest('12345', [
                'filename' => 'large.txt',
                'fileSize' => 5,
                'mimeType' => 'text/plain',
                'policyToken' => $policyToken,
            ]))->getStatusCode(),
        );
        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $controller->direct($this->directRequest('data', [
                'filename' => 'image.png',
                'fileSize' => 4,
                'mimeType' => 'image/png',
                'policyToken' => $policyToken,
            ]))->getStatusCode(),
        );
    }

    public function testDirectUploadLogsUnexpectedFailure(): void
    {
        $uploader = $this->createStub(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 1024,
            'allowed_types' => [],
            'chunk_size' => 1024,
            'integrity_algorithm' => 'sha256',
            'compression' => false,
        ]);
        $uploader->method('uploadDirect')->willThrowException(new \RuntimeException('storage unavailable'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Direct upload failed: {message}',
                self::callback(static fn (array $context): bool => 'storage unavailable' === $context['message']
                    && $context['exception'] instanceof \RuntimeException),
            );
        $controller = new UploadController(
            $uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            logger: $logger,
            allowAnonymous: true,
        );

        $response = $controller->direct($this->directRequest('data', [
            'filename' => 'file.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
        ]));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Internal server error', json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR)['error']);
    }

    public function testTwentyDirectFilesProduceTwentyValidTokens(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler,
            $this->storage,
            policySigner: $policySigner,
            allowAnonymous: true,
        );
        $policyToken = $policySigner->issue('default', 1024, ['text/plain'], 20, 'attachments');
        $tokens = [];

        for ($i = 0; $i < 20; ++$i) {
            $content = 'file-'.$i;
            $response = $controller->direct($this->directRequest($content, [
                'filename' => $i.'.txt',
                'fileSize' => \strlen($content),
                'mimeType' => 'text/plain',
                'digest' => hash('sha256', $content),
                'policyToken' => $policyToken,
            ]));
            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            $tokens[] = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR)['token'];
        }

        self::assertCount(20, $tokens);
        foreach ($tokens as $token) {
            self::assertNotNull($tokenHandler->resolve($token, new UploadContext(fieldName: 'attachments')));
        }

        $router = $this->createStub(UrlGeneratorInterface::class);
        $type = new FileUploadType($router, $tokenHandler, policySigner: $policySigner);
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory()
            ->createNamed('attachments', FileUploadType::class, options: [
                'multiple' => true,
                'max_files' => 20,
            ]);
        $form->submit(json_encode(array_map(static fn (string $token): array => ['token' => $token], $tokens), \JSON_THROW_ON_ERROR));

        self::assertTrue($form->isSynchronized());
        self::assertCount(20, $form->getData());
    }

    public function testInitActionWithValidCsrfToken(): void
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')
            ->with(self::callback(static fn (CsrfToken $token) => 'ux_upload' === $token->getId() && 'valid-token' === $token->getValue()))
            ->willReturn(true);

        $controller = $this->createController($csrfTokenManager);

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $request->headers->set('X-CSRF-Token', 'valid-token');

        $response = $controller->init($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInitActionWithInvalidCsrfToken(): void
    {
        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')
            ->willReturn(false);

        $controller = $this->createController($csrfTokenManager);

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $request->headers->set('X-CSRF-Token', 'invalid-token');

        $response = $controller->init($request);

        $this->assertSame(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Invalid CSRF token', $data['error']);
    }

    public function testMutationWithoutCsrfTokenIsRejectedWhenCsrfIsAvailable(): void
    {
        $pending = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);
        $controller = $this->createController($csrfTokenManager);
        $request = Request::create(
            $this->uploadUrlGenerator->generateUploadUrl($pending->uploadId),
            'PUT',
            server: ['HTTP_X_CHUNK_INDEX' => '0'],
            content: 'data',
        );

        $response = $controller->handle($request, $pending->uploadId);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame([], $this->storage->listChunks($pending->uploadId));
    }

    public function testSignedFormPolicyIsEnforcedAndCannotBeTampered(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            policySigner: $policySigner,
            allowAnonymous: true,
        );
        $policy = $policySigner->issue('default', 10, ['text/plain'], 1);
        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'large.txt',
            'fileSize' => 11,
            'mimeType' => 'text/plain',
            'policyToken' => $policy,
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->init($request)->getStatusCode());

        $wrongMime = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'image.png',
            'fileSize' => 4,
            'mimeType' => 'image/png',
            'policyToken' => $policy,
        ]));
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->init($wrongMime)->getStatusCode());

        $tampered = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'small.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'policyToken' => $policy.'tampered',
        ]));
        self::assertSame(Response::HTTP_FORBIDDEN, $controller->init($tampered)->getStatusCode());
    }

    public function testResumeTokenCannotCrossSignedFormFields(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $resumeTokenHandler = new ResumeTokenHandler($this->uriSigner);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            policySigner: $policySigner,
            resumeTokenHandler: $resumeTokenHandler,
            allowAnonymous: true,
        );
        $fieldA = $policySigner->issue('default', 1000, ['text/plain'], 1, 'profile.attachment');
        $fieldB = $policySigner->issue('default', 1000, ['text/plain'], 1, 'profile.avatar');
        $init = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'private.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'policyToken' => $fieldA,
        ], \JSON_THROW_ON_ERROR));
        $initPayload = json_decode($controller->init($init)->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('profile.attachment', $this->storage->getMetadata($initPayload['uploadId'])['field']);
        $wrongField = new Request([], [], [], [], [], [], json_encode([
            'resumeToken' => $initPayload['resumeToken'],
            'policyToken' => $fieldB,
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_FORBIDDEN, $controller->resume($wrongField)->getStatusCode());

        $sameField = new Request([], [], [], [], [], [], json_encode([
            'resumeToken' => $initPayload['resumeToken'],
            'policyToken' => $fieldA,
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_OK, $controller->resume($sameField)->getStatusCode());
    }

    public function testInitActionWithoutCsrfTokenManagerStillWorks(): void
    {
        $controller = $this->createController(null);

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));

        $response = $controller->init($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMalformedJsonBodyIsRejectedWithBadRequest(): void
    {
        $controller = $this->createController();

        foreach (['init', 'resume', 'remove'] as $action) {
            $request = new Request([], [], [], [], [], [], '{invalid json');
            $response = $controller->{$action}($request);

            $this->assertSame(400, $response->getStatusCode(), \sprintf('Action "%s" must reject a malformed JSON body with 400.', $action));
            $data = json_decode((string) $response->getContent(), true);
            $this->assertSame('Malformed request body', $data['error']);
        }
    }

    public function testHandleChunkAcceptsIdenticalRetransmission(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Upload first time
        $request = Request::create($uploadUrl, 'PUT', [], [], [], [], 'data');
        $request->headers->set('X-Chunk-Index', '0');
        $controller->handle($request, $uploadId);

        // Retransmitting the same bytes is an idempotent retry, not an error
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleChunkRejectsOverwriteAndPreservesOriginalChunk(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 8,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Store the original chunk.
        $first = Request::create($uploadUrl, 'PUT', [], [], [], [], 'original');
        $first->headers->set('X-Chunk-Index', '0');
        $this->assertSame(200, $controller->handle($first, $uploadId)->getStatusCode());

        // A re-send of the same index with tampered content must be rejected.
        $tampered = Request::create($uploadUrl, 'PUT', [], [], [], [], 'tampered');
        $tampered->headers->set('X-Chunk-Index', '0');
        $response = $controller->handle($tampered, $uploadId);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('already been uploaded', json_decode($response->getContent(), true)['error']);

        // Completing the upload must yield the original bytes, not the tampered ones.
        $complete = Request::create($uploadUrl, 'POST');
        $response = $controller->handle($complete, $uploadId);
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $completed = new UploadTokenHandler($this->uriSigner, $this->storage)->resolve($payload['token'] ?? null);
        self::assertNotNull($completed);
        $stream = $completed->openStream();
        try {
            self::assertSame('original', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testHandleWithMissingSessionReturns404(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('getMetadata')->willReturn(null);

        $controller = new UploadController($this->uploader, $this->uploadUrlGenerator, new UploadTokenHandler($this->uriSigner, $storage), $storage, allowAnonymous: true);

        $uploadId = 'unknown';
        $uploadUrl = $this->uploadUrlGenerator->generateUploadUrl($uploadId);
        $request = Request::create($uploadUrl, 'GET');
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testHandleReturns404WhenSessionDisappearsDuringUploaderResolution(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::exactly(2))
            ->method('getMetadata')
            ->with('vanished')
            ->willReturnOnConsecutiveCalls([
                'uploader' => 'default',
                'ownerId' => null,
                'tenantId' => null,
                'field' => null,
            ], null);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $storage),
            $storage,
            allowAnonymous: true,
        );
        $request = Request::create($this->uploadUrlGenerator->generateUploadUrl('vanished'), 'GET');

        self::assertSame(Response::HTTP_NOT_FOUND, $controller->handle($request, 'vanished')->getStatusCode());
    }

    public function testInvalidNamedUploaderServiceReturnsInternalServerError(): void
    {
        $uploaders = $this->createStub(ContainerInterface::class);
        $uploaders->method('has')->willReturn(true);
        $uploaders->method('get')->willReturn(new \stdClass());
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            $uploaders,
            allowAnonymous: true,
        );
        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'uploader' => 'documents',
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $controller->init($request)->getStatusCode());
    }

    public function testSignedUrlCannotBeUsedByAnotherUploadContext(): void
    {
        $ownerA = $this->contextResolver('owner-a', 'tenant-a', 'attachment');
        $ownerB = $this->contextResolver('owner-a', 'tenant-b', 'attachment');
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $firstController = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler,
            $this->storage,
            contextResolver: $ownerA,
            allowAnonymous: true,
        );
        $secondController = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler,
            $this->storage,
            contextResolver: $ownerB,
            allowAnonymous: true,
        );
        $response = $firstController->init(new Request([], [], [], [], [], [], json_encode([
            'filename' => 'private.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
        ], \JSON_THROW_ON_ERROR)));
        $payload = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('owner-a', $this->storage->getMetadata($payload['uploadId'])['ownerId']);
        self::assertSame('tenant-a', $this->storage->getMetadata($payload['uploadId'])['tenantId']);
        self::assertSame('attachment', $this->storage->getMetadata($payload['uploadId'])['field']);

        $request = Request::create($payload['uploadUrl'], 'GET');

        self::assertSame(Response::HTTP_FORBIDDEN, $secondController->handle($request, $payload['uploadId'])->getStatusCode());
    }

    public function testRemoveDeletesACompletedTemporaryUpload(): void
    {
        $contextResolver = $this->contextResolver('owner-a', 'tenant-a', 'attachment');
        $tokenHandler = new UploadTokenHandler(
            $this->uriSigner,
            $this->storage,
            contextResolver: $contextResolver,
        );
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler,
            $this->storage,
            contextResolver: $contextResolver,
            allowAnonymous: true,
        );
        $pending = $this->uploader->initializeUpload(
            'private.txt',
            4,
            'text/plain',
            context: $contextResolver->resolve(),
        );
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');
        $completed = $this->uploader->completeUpload($pending->uploadId);
        $token = $tokenHandler->generate($completed);

        $response = $controller->remove(new Request([], [], [], [], [], [], json_encode(['token' => $token], \JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($this->storage->exists($completed->getTemporaryPath()));
        self::assertNull($this->storage->getMetadata($completed->id));
    }

    public function testHandleWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $badStorage = $this->createStub(StorageInterface::class);
        $badStorage->method('getMetadata')->willThrowException(new \RuntimeException('Unexpected error'));

        $badUploader = new Uploader($badStorage, $this->uploadUrlGenerator, new EventDispatcher());
        $controller = new UploadController($badUploader, $this->uploadUrlGenerator, new UploadTokenHandler($this->uriSigner, $badStorage), $badStorage, null, $logger, allowAnonymous: true);

        $uploadId = 'unknown';
        $uploadUrl = $this->uploadUrlGenerator->generateUploadUrl($uploadId);
        $request = Request::create($uploadUrl, 'GET');
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testInitInternalErrorWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $badStorage = $this->createStub(StorageInterface::class);
        $badStorage->method('initiate')->willThrowException(new \RuntimeException('disk full'));

        $badUploader = new Uploader($badStorage, $this->uploadUrlGenerator, new EventDispatcher());
        $controller = new UploadController($badUploader, $this->uploadUrlGenerator, new UploadTokenHandler($this->uriSigner, $badStorage), $badStorage, null, $logger, allowAnonymous: true);

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 100,
            'mimeType' => 'text/plain',
        ]));

        $response = $controller->init($request);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testInitAction(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));

        $response = $controller->init($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('uploadId', $data);
        $this->assertArrayHasKey('uploadUrl', $data);
    }

    public function testHandleChunk(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        $request = Request::create($uploadUrl, 'PUT', [], [], [], [], 'chunk data');
        $request->headers->set('X-Chunk-Index', '0');

        $response = $controller->handle($request, $uploadId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleComplete(): void
    {
        $controller = $this->createController();
        $content = 'test content';

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => \strlen($content),
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Store chunk via uploader (3-arg signature)
        $this->uploader->storeChunk($uploadId, 0, $content);

        $request = Request::create($uploadUrl, 'POST');
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayNotHasKey('path', $data['meta']);
        $this->assertArrayHasKey('filename', $data['meta']);
        $this->assertArrayHasKey('mimeType', $data['meta']);
        $this->assertArrayHasKey('size', $data['meta']);
    }

    public function testHandleCompleteCanBeRetriedAfterThePendingSessionWasRemoved(): void
    {
        $controller = $this->createController();
        $content = 'test content';
        $init = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => \strlen($content),
            'mimeType' => 'text/plain',
        ]));
        $data = json_decode($controller->init($init)->getContent(), true);
        $this->uploader->storeChunk($data['uploadId'], 0, $content);
        $complete = Request::create($data['uploadUrl'], 'POST');

        $first = $controller->handle($complete, $data['uploadId']);
        $second = $controller->handle($complete, $data['uploadId']);

        self::assertSame(Response::HTTP_OK, $first->getStatusCode());
        self::assertSame(Response::HTTP_OK, $second->getStatusCode());
        self::assertSame(
            json_decode($first->getContent(), true),
            json_decode($second->getContent(), true),
        );
    }

    public function testHandleStatus(): void
    {
        $controller = $this->createController();
        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        $request = Request::create($uploadUrl, 'GET');
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('progress', $data);
    }

    public function testHandleCancel(): void
    {
        $controller = $this->createController();
        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        $request = Request::create($uploadUrl, 'DELETE');
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleInvalidSignature(): void
    {
        $controller = $this->createController();
        $uploadId = 'some-id';

        // Unsigned request
        $request = Request::create('/upload/'.$uploadId, 'GET');

        $response = $controller->handle($request, $uploadId);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testHandleTamperedSignature(): void
    {
        $controller = $this->createController();

        // Create valid upload first
        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Tamper with the signature
        $tamperedUrl = preg_replace('/_hash=[^&]+/', '_hash=tampered', $uploadUrl);
        $request = Request::create($tamperedUrl, 'GET');

        $response = $controller->handle($request, $uploadId);

        $this->assertSame(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid or expired', $responseData['error']);
    }

    public function testHandleExpiredSignature(): void
    {
        // Create a separate SymfonyUploadUrlGenerator with very short expiry
        $storage = new MockStorage();
        $uriSigner = new UriSigner('test-secret-key');

        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')
            ->willReturnCallback(static fn (string $route, array $params = []) => 'http://example.com/upload/'.$params['uploadId']);

        $urlGenerator = new SymfonyUploadUrlGenerator(
            $router,
            $uriSigner,
            signatureExpiry: 1,
        );

        $dispatcher = new EventDispatcher();

        $uploader = new Uploader(
            $storage,
            $urlGenerator,
            $dispatcher,
        );

        $tokenHandler = new UploadTokenHandler($uriSigner, $storage);
        $controller = new UploadController($uploader, $urlGenerator, $tokenHandler, $storage, allowAnonymous: true);

        // Create upload
        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Wait for signature to expire
        sleep(2);

        $request = Request::create($uploadUrl, 'GET');
        $response = $controller->handle($request, $uploadId);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testHandleMissingChunkIndexHeader(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // PUT without X-Chunk-Index header
        $request = Request::create($uploadUrl, 'PUT', [], [], [], [], 'chunk data');

        $response = $controller->handle($request, $uploadId);

        $this->assertSame(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('X-Chunk-Index', $responseData['error']);
    }

    public function testHandleInvalidChunkIndex(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // PUT with invalid chunk index (beyond total)
        $request = Request::create($uploadUrl, 'PUT', [], [], [], [], 'chunk data');
        $request->headers->set('X-Chunk-Index', '99');

        $response = $controller->handle($request, $uploadId);

        $this->assertSame(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('out of range', $responseData['error']);
    }

    public function testInitMissingFilename(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));

        $response = $controller->init($request);

        $this->assertSame(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('filename', $responseData['error']);
    }

    public function testInitMissingFileSize(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'mimeType' => 'text/plain',
        ]));

        $response = $controller->init($request);

        $this->assertSame(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('fileSize', $responseData['error']);
    }

    public function testInitZeroFileSize(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 0,
            'mimeType' => 'text/plain',
        ]));

        $response = $controller->init($request);

        $this->assertSame(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('greater than zero', $responseData['error']);
    }

    public function testMethodNotAllowed(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Use PATCH which is not supported
        $request = Request::create($uploadUrl, 'PATCH');

        $response = $controller->handle($request, $uploadId);

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testInitWithNamedUploader(): void
    {
        $namedStorage = new MockStorage();
        $dispatcher = new EventDispatcher();
        $namedUploader = new Uploader(
            $namedStorage,
            $this->uploadUrlGenerator,
            $dispatcher,
            name: 'avatar',
        );

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => 'avatar' === $id);
        $container->method('get')->willReturnCallback(static fn (string $id) => 'avatar' === $id ? $namedUploader : null);
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $controller = new UploadController($this->uploader, $this->uploadUrlGenerator, $tokenHandler, $this->storage, $container, allowAnonymous: true);

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'avatar.png',
            'fileSize' => 500,
            'mimeType' => 'image/png',
            'uploader' => 'avatar',
        ]));

        $response = $controller->init($request);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('uploadId', $data);
    }

    public function testInitWithUnknownUploaderReturns400(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
            'uploader' => 'nonexistent',
        ]));

        $response = $controller->init($request);

        self::assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Unknown uploader', $data['error']);
    }

    public function testInitDefaultMimeType(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.bin',
            'fileSize' => 100,
        ]));

        $response = $controller->init($request);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testHandleStatusReturns404WhenSessionMissing(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
        $response = $controller->init($request);
        $data = json_decode($response->getContent(), true);
        $uploadId = $data['uploadId'];
        $uploadUrl = $data['uploadUrl'];

        // Cancel to delete session
        $this->uploader->cancelUpload($uploadId);

        $request = Request::create($uploadUrl, 'GET');
        $response = $controller->handle($request, $uploadId);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testInitRateLimitRejectsWhenExceeded(): void
    {
        $limiterFactory = new RateLimiterFactory(
            ['id' => 'ux_upload_init', 'policy' => 'sliding_window', 'limit' => 2, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );

        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $controller = new UploadController($this->uploader, $this->uploadUrlGenerator, $tokenHandler, $this->storage, null, null, null, $limiterFactory, allowAnonymous: true);

        $makeRequest = static fn (): Request => new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));

        self::assertSame(200, $controller->init($makeRequest())->getStatusCode());
        self::assertSame(200, $controller->init($makeRequest())->getStatusCode());

        $response = $controller->init($makeRequest());
        self::assertSame(429, $response->getStatusCode());
        self::assertStringContainsString('Too many requests', json_decode($response->getContent(), true)['error']);
    }

    public function testInitWithoutRateLimiterIsNotThrottled(): void
    {
        $controller = $this->createController();

        for ($i = 0; $i < 5; ++$i) {
            $request = new Request([], [], [], [], [], [], json_encode([
                'filename' => 'test.txt',
                'fileSize' => 1000,
                'mimeType' => 'text/plain',
            ]));

            self::assertSame(200, $controller->init($request)->getStatusCode());
        }
    }

    public function testResumeRejectsMissingCsrfHandlerAndInvalidPolicy(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $resumeHandler = new ResumeTokenHandler($this->uriSigner);
        $withCsrf = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            csrfTokenManager: $csrf,
            resumeTokenHandler: $resumeHandler,
            allowAnonymous: true,
        );
        self::assertSame(Response::HTTP_FORBIDDEN, $withCsrf->resume(new Request([], [], [], [], [], [], '{}'))->getStatusCode());

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $this->createController()->resume(new Request([], [], [], [], [], [], '{"resumeToken":"token"}'))->getStatusCode(),
        );

        $policySigner = new UploadPolicySigner($this->uriSigner);
        $withPolicy = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            policySigner: $policySigner,
            resumeTokenHandler: $resumeHandler,
            allowAnonymous: true,
        );
        $request = new Request([], [], [], [], [], [], json_encode([
            'resumeToken' => 'invalid',
            'policyToken' => 'invalid',
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_FORBIDDEN, $withPolicy->resume($request)->getStatusCode());
    }

    public function testResumeRejectsMissingSessionAndMismatchedContext(): void
    {
        $handler = new ResumeTokenHandler($this->uriSigner);
        $anonymous = new UploadContext();
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            resumeTokenHandler: $handler,
            allowAnonymous: true,
        );
        $request = new Request([], [], [], [], [], [], json_encode([
            'resumeToken' => $handler->generate('missing', $anonymous),
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_NOT_FOUND, $controller->resume($request)->getStatusCode());

        $this->storage->initiate('owned', [
            'ownerId' => 'owner-a',
            'tenantId' => null,
            'field' => null,
            'uploader' => 'default',
        ]);
        $ownerB = $this->contextResolver('owner-b');
        $ownedController = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            contextResolver: $ownerB,
            resumeTokenHandler: $handler,
            allowAnonymous: true,
        );
        $ownedRequest = new Request([], [], [], [], [], [], json_encode([
            'resumeToken' => $handler->generate('owned', $ownerB->resolve()),
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_FORBIDDEN, $ownedController->resume($ownedRequest)->getStatusCode());
    }

    public function testResumeMapsDomainAndUnexpectedFailures(): void
    {
        $handler = new ResumeTokenHandler($this->uriSigner);
        $token = $handler->generate('upload-1', new UploadContext());

        $missingStorage = $this->createStub(StorageInterface::class);
        $missingStorage->method('getMetadata')->willThrowException(new UploadSessionNotFoundException('upload-1'));
        $missing = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $missingStorage),
            $missingStorage,
            resumeTokenHandler: $handler,
            allowAnonymous: true,
        );
        self::assertSame(Response::HTTP_NOT_FOUND, $missing->resume($this->resumeRequest($token))->getStatusCode());

        $this->storage->initiate('unknown-uploader', [
            'uploader' => 'missing',
            'ownerId' => null,
            'tenantId' => null,
            'field' => null,
        ]);
        $invalid = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            resumeTokenHandler: $handler,
            allowAnonymous: true,
        );
        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $invalid->resume($this->resumeRequest($handler->generate('unknown-uploader', new UploadContext())))->getStatusCode(),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $brokenStorage = $this->createStub(StorageInterface::class);
        $brokenStorage->method('getMetadata')->willThrowException(new \RuntimeException('backend down'));
        $broken = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $brokenStorage),
            $brokenStorage,
            logger: $logger,
            resumeTokenHandler: $handler,
            allowAnonymous: true,
        );
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $broken->resume($this->resumeRequest($token))->getStatusCode());
    }

    public function testRemoveRejectsCsrfPolicyAndTokenFailures(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $this->createController($csrf)->remove(new Request([], [], [], [], [], [], '{}'))->getStatusCode(),
        );

        $policySigner = new UploadPolicySigner($this->uriSigner);
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            policySigner: $policySigner,
            allowAnonymous: true,
        );
        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $controller->remove(new Request([], [], [], [], [], [], '{"policyToken":"invalid"}'))->getStatusCode(),
        );
        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $controller->remove(new Request([], [], [], [], [], [], '{"token":"invalid"}'))->getStatusCode(),
        );
    }

    public function testRemoveLogsStorageFailure(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('delete')->willThrowException(new \RuntimeException('delete failed'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $handler = new UploadTokenHandler($this->uriSigner, $storage);
        $now = new \DateTimeImmutable();
        $upload = new CompletedUpload(
            'upload-1',
            'default',
            '.tmp/completed/'.($now->getTimestamp() + 3600).'-'.str_repeat('a', 32).'.txt',
            'file.txt',
            'text/plain',
            4,
            $now,
            $now->modify('+1 hour'),
            access: new CompletedUploadAccess($storage),
        );
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $handler,
            $storage,
            logger: $logger,
            allowAnonymous: true,
        );
        $request = new Request([], [], [], [], [], [], json_encode(['token' => $handler->generate($upload)], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $controller->remove($request)->getStatusCode());
    }

    public function testChunkRejectsDeclaredAndActualOversizeBeforeStorage(): void
    {
        $uploader = $this->createMock(UploaderInterface::class);
        $uploader->method('getConfig')->willReturn([
            'max_size' => 100,
            'allowed_types' => [],
            'chunk_size' => 4,
            'integrity_algorithm' => 'sha256',
        ]);
        $uploader->expects(self::never())->method('storeChunk');
        $this->storage->initiate('small-chunks', [
            'uploader' => 'default',
            'ownerId' => null,
            'tenantId' => null,
            'field' => null,
        ]);
        $controller = new UploadController(
            $uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            allowAnonymous: true,
        );
        $url = $this->uploadUrlGenerator->generateUploadUrl('small-chunks');

        $declared = Request::create($url, 'PUT', server: ['HTTP_X_CHUNK_INDEX' => '0'], content: 'data');
        $declared->headers->set('Content-Length', '9');
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->handle($declared, 'small-chunks')->getStatusCode());

        $actual = Request::create($url, 'PUT', server: ['HTTP_X_CHUNK_INDEX' => '0'], content: '12345');
        $actual->headers->remove('Content-Length');
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->handle($actual, 'small-chunks')->getStatusCode());
    }

    private function resumeRequest(string $token): Request
    {
        return new Request([], [], [], [], [], [], json_encode(['resumeToken' => $token], \JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, int|string> $parameters
     */
    private function directRequest(string $content, array $parameters = []): Request
    {
        $path = tempnam(sys_get_temp_dir(), 'ux-upload-direct-');
        self::assertIsString($path);
        file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;
        $filename = isset($parameters['filename']) && \is_string($parameters['filename']) ? $parameters['filename'] : 'upload.bin';
        $mimeType = isset($parameters['mimeType']) && \is_string($parameters['mimeType']) ? $parameters['mimeType'] : 'application/octet-stream';

        return new Request(
            request: $parameters,
            files: ['file' => new UploadedFile($path, $filename, $mimeType, test: true)],
        );
    }

    public function testAnonymousInitializationIsRejectedByDefault(): void
    {
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
        );

        $response = $controller->init($this->anonymousInitRequest());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertStringContainsString('Anonymous uploads are disabled', (string) $response->getContent());
    }

    public function testAnonymousInitializationIsAcceptedWhenExplicitlyAllowed(): void
    {
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
            allowAnonymous: true,
        );

        self::assertSame(Response::HTTP_OK, $controller->init($this->anonymousInitRequest())->getStatusCode());
    }

    public function testAnIdentifiedOwnerNeedsNoAnonymousOptIn(): void
    {
        $contextResolver = $this->contextResolver('owner-1');
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage, contextResolver: $contextResolver),
            $this->storage,
            contextResolver: $contextResolver,
        );

        self::assertSame(Response::HTTP_OK, $controller->init($this->anonymousInitRequest())->getStatusCode());
    }

    private function anonymousInitRequest(): Request
    {
        return new Request([], [], [], [], [], [], json_encode([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
        ]));
    }

    private function createController(?CsrfTokenManagerInterface $csrfTokenManager = null): UploadController
    {
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);

        return new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler,
            $this->storage,
            csrfTokenManager: $csrfTokenManager,
            allowAnonymous: true,
        );
    }

    private function contextResolver(?string $ownerId, ?string $tenantId = null, ?string $fieldName = null): UploadContextResolverInterface
    {
        return new readonly class($ownerId, $tenantId, $fieldName) implements UploadContextResolverInterface {
            public function __construct(
                private ?string $ownerId,
                private ?string $tenantId,
                private ?string $fieldName,
            ) {
            }

            public function resolve(): UploadContext
            {
                return new UploadContext($this->ownerId, $this->tenantId, $this->fieldName);
            }
        };
    }
}
