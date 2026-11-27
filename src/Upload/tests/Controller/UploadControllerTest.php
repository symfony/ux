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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage as RateLimiterStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Token\ResumeTokenHandler;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\Url\SymfonyUploadUrlGenerator;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

#[CoversClass(UploadController::class)]
final class UploadControllerTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];
    private InMemoryStorage $storage;
    private UploadUrlGeneratorInterface $uploadUrlGenerator;
    private UriSigner $uriSigner;
    private Uploader $uploader;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();

        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')
            ->willReturnCallback(static fn (string $route, array $params = []) => 'http://example.com/upload/'.$params['uploadId']);

        $this->uriSigner = new UriSigner('test-secret-key');
        $this->uploadUrlGenerator = new SymfonyUploadUrlGenerator($router, $this->uriSigner);
        $this->uploader = new Uploader($this->storage, $this->uploadUrlGenerator, new EventDispatcher());
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

        $response = $this->controller()->direct($request);
        $payload = $this->decode($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotSame('', $payload['uploadId']);
        self::assertSame([
            'filename' => 'direct.txt',
            'mimeType' => 'text/plain',
            'size' => \strlen($content),
        ], $payload['meta']);
        $completed = new UploadTokenHandler($this->uriSigner, $this->storage)->resolve($payload['token']);
        self::assertNotNull($completed);
        self::assertSame($payload['uploadId'], $completed->getId());
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
        $controller = $this->controller(
            tokenHandler: $tokenHandler,
            uploaders: $uploaders,
            csrfTokenManager: $csrf,
            contextResolver: $contextResolver,
            policySigner: $policySigner,
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
        $payload = $this->decode($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $completed = $tokenHandler->resolve($payload['token'], new UploadContext('owner-1', 'tenant-1', 'profile.avatar'));
        self::assertNotNull($completed);
        self::assertSame('avatar', $completed->getUploaderName());
        self::assertSame('owner-1', $completed->getOwnerId());
        self::assertSame('tenant-1', $completed->getTenantId());
        self::assertSame('profile.avatar', $completed->getFieldName());
    }

    public function testDirectUploadReturns413ForDeclaredOrTransmittedOversize(): void
    {
        $controller = $this->controller(
            uploader: new Uploader($this->storage, $this->uploadUrlGenerator, new EventDispatcher(), chunkSize: 4),
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
        $controller = $this->controller(initRateLimiter: new RateLimiterFactory(
            ['id' => 'ux_upload_direct', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'],
            new RateLimiterStorage(),
        ));

        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->direct(new Request())->getStatusCode());
        self::assertSame(
            Response::HTTP_TOO_MANY_REQUESTS,
            $controller->direct($this->directRequest('data', ['filename' => 'x', 'fileSize' => 4, 'mimeType' => 'text/plain']))->getStatusCode(),
        );
    }

    public function testDirectUploadRejectsMalformedMetadataAndPolicyViolations(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $controller = $this->controller(policySigner: $policySigner);

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

    public function testDirectUploadRejectsAFieldSentAsAnArray(): void
    {
        $controller = $this->controller();
        $request = $this->directRequest('data', ['filename' => ['x'], 'fileSize' => 4, 'mimeType' => 'text/plain']);

        $response = $controller->direct($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Malformed request body', $this->decode($response)['error']);
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
                self::anything(),
                self::callback(static fn (array $context): bool => 'storage unavailable' === $context['message']
                    && $context['exception'] instanceof \RuntimeException),
            );
        $controller = $this->controller(uploader: $uploader, logger: $logger);

        $response = $controller->direct($this->directRequest('data', [
            'filename' => 'file.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
        ]));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Internal server error', $this->decode($response)['error']);
    }

    public function testDirectUploadTokensSubmitTogetherIntoAMultipleField(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $controller = $this->controller(tokenHandler: $tokenHandler, policySigner: $policySigner);
        $policyToken = $policySigner->issue('default', 1024, ['text/plain'], 3, 'attachments');
        $tokens = [];

        for ($i = 0; $i < 3; ++$i) {
            $content = 'file-'.$i;
            $response = $controller->direct($this->directRequest($content, [
                'filename' => $i.'.txt',
                'fileSize' => \strlen($content),
                'mimeType' => 'text/plain',
                'digest' => hash('sha256', $content),
                'policyToken' => $policyToken,
            ]));
            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            $tokens[] = $this->decode($response)['token'];
        }

        foreach ($tokens as $token) {
            self::assertNotNull($tokenHandler->resolve($token, new UploadContext(fieldName: 'attachments')));
        }

        $type = new UploadType($this->createStub(UrlGeneratorInterface::class), $tokenHandler, policySigner: $policySigner);
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$type], []))
            ->getFormFactory()
            ->createNamed('attachments', UploadType::class, options: [
                'multiple' => true,
                'max_files' => 3,
            ]);
        $form->submit(json_encode(array_map(static fn (string $token): array => ['token' => $token], $tokens), \JSON_THROW_ON_ERROR));

        self::assertTrue($form->isSynchronized());
        self::assertCount(3, $form->getData());
    }

    public function testInitActionWithValidCsrfToken(): void
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')
            ->with(self::callback(static fn (CsrfToken $token) => 'ux_upload' === $token->getId() && 'valid-token' === $token->getValue()))
            ->willReturn(true);
        $request = $this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain']);
        $request->headers->set('X-CSRF-Token', 'valid-token');

        self::assertSame(200, $this->controller(csrfTokenManager: $csrfTokenManager)->init($request)->getStatusCode());
    }

    public function testInitActionWithInvalidCsrfToken(): void
    {
        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);
        $request = $this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain']);
        $request->headers->set('X-CSRF-Token', 'invalid-token');

        $response = $this->controller(csrfTokenManager: $csrfTokenManager)->init($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Invalid CSRF token', $this->decode($response)['error']);
    }

    public function testMutationWithoutCsrfTokenIsRejectedWhenCsrfIsAvailable(): void
    {
        $pending = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);
        $controller = $this->controller(csrfTokenManager: $csrfTokenManager);
        $request = $this->chunkRequest($this->uploadUrlGenerator->generateUploadUrl($pending->uploadId), 0, 'data');

        $response = $controller->handle($request, $pending->uploadId);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame([], $this->storage->listChunks($pending->uploadId));
    }

    public function testSignedFormPolicyIsEnforcedAndCannotBeTampered(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $controller = $this->controller(policySigner: $policySigner);
        $policy = $policySigner->issue('default', 10, ['text/plain'], 1);

        $tooLarge = $this->jsonRequest([
            'filename' => 'large.txt',
            'fileSize' => 11,
            'mimeType' => 'text/plain',
            'policyToken' => $policy,
        ]);
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->init($tooLarge)->getStatusCode());

        $wrongMime = $this->jsonRequest([
            'filename' => 'image.png',
            'fileSize' => 4,
            'mimeType' => 'image/png',
            'policyToken' => $policy,
        ]);
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->init($wrongMime)->getStatusCode());

        $tampered = $this->jsonRequest([
            'filename' => 'small.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'policyToken' => $policy.'tampered',
        ]);
        self::assertSame(Response::HTTP_FORBIDDEN, $controller->init($tampered)->getStatusCode());
    }

    public function testResumeTokenCannotCrossSignedFormFields(): void
    {
        $policySigner = new UploadPolicySigner($this->uriSigner);
        $controller = $this->controller(
            policySigner: $policySigner,
            resumeTokenHandler: new ResumeTokenHandler($this->uriSigner),
        );
        $fieldA = $policySigner->issue('default', 1000, ['text/plain'], 1, 'profile.attachment');
        $fieldB = $policySigner->issue('default', 1000, ['text/plain'], 1, 'profile.avatar');
        $initPayload = $this->decode($controller->init($this->jsonRequest([
            'filename' => 'private.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'policyToken' => $fieldA,
        ])));

        self::assertSame('profile.attachment', $this->metadata($initPayload['uploadId'])['field']);
        $wrongField = $this->jsonRequest([
            'resumeToken' => $initPayload['resumeToken'],
            'policyToken' => $fieldB,
        ]);
        self::assertSame(Response::HTTP_FORBIDDEN, $controller->resume($wrongField)->getStatusCode());

        $sameField = $this->jsonRequest([
            'resumeToken' => $initPayload['resumeToken'],
            'policyToken' => $fieldA,
        ]);
        self::assertSame(Response::HTTP_OK, $controller->resume($sameField)->getStatusCode());
    }

    public function testMalformedJsonBodyIsRejectedWithBadRequest(): void
    {
        $controller = $this->controller();

        foreach (['init', 'resume', 'remove'] as $action) {
            $response = $controller->{$action}(new Request(content: '{invalid json'));

            self::assertSame(400, $response->getStatusCode(), \sprintf('Action "%s" must reject a malformed JSON body with 400.', $action));
            self::assertSame('Malformed request body', $this->decode($response)['error']);
        }
    }

    public function testHandleChunkAcceptsIdenticalRetransmission(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);
        $request = $this->chunkRequest($uploadUrl, 0, 'data');
        $controller->handle($request, $uploadId);

        $response = $controller->handle($request, $uploadId);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->decode($response)['success']);
    }

    public function testHandleChunkRejectsOverwriteAndPreservesOriginalChunk(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller, fileSize: 8);

        self::assertSame(200, $controller->handle($this->chunkRequest($uploadUrl, 0, 'original'), $uploadId)->getStatusCode());

        $response = $controller->handle($this->chunkRequest($uploadUrl, 0, 'tampered'), $uploadId);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('already been uploaded', $this->decode($response)['error']);

        $response = $controller->handle(Request::create($uploadUrl, 'POST'), $uploadId);
        self::assertSame(200, $response->getStatusCode());
        $completed = new UploadTokenHandler($this->uriSigner, $this->storage)->resolve($this->decode($response)['token'] ?? null);
        self::assertNotNull($completed);
        $stream = $completed->openStream();
        try {
            self::assertSame('original', stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
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
        $controller = $this->controller(storage: $storage);
        $request = Request::create($this->uploadUrlGenerator->generateUploadUrl('vanished'), 'GET');

        self::assertSame(Response::HTTP_NOT_FOUND, $controller->handle($request, 'vanished')->getStatusCode());
    }

    public function testInvalidNamedUploaderServiceReturnsInternalServerError(): void
    {
        $uploaders = $this->createStub(ContainerInterface::class);
        $uploaders->method('has')->willReturn(true);
        $uploaders->method('get')->willReturn(new \stdClass());
        $request = $this->jsonRequest([
            'filename' => 'test.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'uploader' => 'documents',
        ]);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->controller(uploaders: $uploaders)->init($request)->getStatusCode());
    }

    public function testSignedUrlCannotBeUsedByAnotherUploadContext(): void
    {
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $firstController = $this->controller(
            tokenHandler: $tokenHandler,
            contextResolver: $this->contextResolver('owner-a', 'tenant-a', 'attachment'),
        );
        $secondController = $this->controller(
            tokenHandler: $tokenHandler,
            contextResolver: $this->contextResolver('owner-a', 'tenant-b', 'attachment'),
        );
        $payload = $this->decode($firstController->init($this->jsonRequest([
            'filename' => 'private.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
        ])));
        self::assertSame('owner-a', $this->metadata($payload['uploadId'])['ownerId']);
        self::assertSame('tenant-a', $this->metadata($payload['uploadId'])['tenantId']);
        self::assertSame('attachment', $this->metadata($payload['uploadId'])['field']);

        $request = Request::create($payload['uploadUrl'], 'GET');

        self::assertSame(Response::HTTP_FORBIDDEN, $secondController->handle($request, $payload['uploadId'])->getStatusCode());
    }

    public function testRemoveDeletesACompletedTemporaryUpload(): void
    {
        $contextResolver = $this->contextResolver('owner-a', 'tenant-a', 'attachment');
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage, contextResolver: $contextResolver);
        $controller = $this->controller(tokenHandler: $tokenHandler, contextResolver: $contextResolver);
        $pending = $this->uploader->initializeUpload('private.txt', 4, 'text/plain', context: $contextResolver->resolve());
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');
        $completed = $this->uploader->completeUpload($pending->uploadId);

        $response = $controller->remove($this->jsonRequest(['token' => $tokenHandler->generate($completed)]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($this->storage->exists($completed->getStoragePath()));
        self::assertNull($this->storage->getMetadata($completed->getId()));
    }

    public function testHandleWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $badStorage = $this->createStub(StorageInterface::class);
        $badStorage->method('getMetadata')->willThrowException(new \RuntimeException('Unexpected error'));
        $controller = $this->controller(
            uploader: new Uploader($badStorage, $this->uploadUrlGenerator, new EventDispatcher()),
            storage: $badStorage,
            logger: $logger,
        );
        $request = Request::create($this->uploadUrlGenerator->generateUploadUrl('unknown'), 'GET');

        self::assertSame(500, $controller->handle($request, 'unknown')->getStatusCode());
    }

    public function testInitInternalErrorWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $badStorage = $this->createStub(StorageInterface::class);
        $badStorage->method('initiate')->willThrowException(new \RuntimeException('disk full'));
        $controller = $this->controller(
            uploader: new Uploader($badStorage, $this->uploadUrlGenerator, new EventDispatcher()),
            storage: $badStorage,
            logger: $logger,
        );
        $request = $this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 100, 'mimeType' => 'text/plain']);

        self::assertSame(500, $controller->init($request)->getStatusCode());
    }

    public function testInitAction(): void
    {
        $response = $this->controller()->init($this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain']));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        $data = $this->decode($response);
        self::assertArrayHasKey('uploadId', $data);
        self::assertArrayHasKey('uploadUrl', $data);
    }

    public function testHandleCompleteCanBeRetriedAfterThePendingSessionWasRemoved(): void
    {
        $controller = $this->controller();
        $content = 'test content';
        [$uploadId, $uploadUrl] = $this->initUpload($controller, fileSize: \strlen($content));
        $this->uploader->storeChunk($uploadId, 0, $content);
        $complete = Request::create($uploadUrl, 'POST');

        $first = $controller->handle($complete, $uploadId);
        $second = $controller->handle($complete, $uploadId);

        self::assertSame(Response::HTTP_OK, $first->getStatusCode());
        self::assertSame(Response::HTTP_OK, $second->getStatusCode());
        $data = $this->decode($first);
        self::assertTrue($data['success']);
        self::assertArrayNotHasKey('path', $data['meta']);
        self::assertArrayHasKey('filename', $data['meta']);
        self::assertArrayHasKey('mimeType', $data['meta']);
        self::assertArrayHasKey('size', $data['meta']);
        self::assertSame($data, $this->decode($second));
    }

    public function testHandleStatus(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);

        $response = $controller->handle(Request::create($uploadUrl, 'GET'), $uploadId);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('progress', $this->decode($response));
    }

    public function testHandleCancel(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);

        $response = $controller->handle(Request::create($uploadUrl, 'DELETE'), $uploadId);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->decode($response)['success']);
    }

    public function testHandleTamperedSignature(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);
        $tamperedUrl = preg_replace('/_hash=[^&]+/', '_hash=tampered', $uploadUrl);
        self::assertIsString($tamperedUrl);

        $response = $controller->handle(Request::create($tamperedUrl, 'GET'), $uploadId);

        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('Invalid or expired', $this->decode($response)['error']);
    }

    public function testHandleMissingChunkIndexHeader(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);

        $response = $controller->handle(Request::create($uploadUrl, 'PUT', content: 'chunk data'), $uploadId);

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('X-Chunk-Index', $this->decode($response)['error']);
    }

    /**
     * @return iterable<string, array{array<string, int|string>, string}>
     */
    public static function incompleteInitPayloads(): iterable
    {
        yield 'missing filename' => [['fileSize' => 1000, 'mimeType' => 'text/plain'], 'filename'];
        yield 'missing file size' => [['filename' => 'test.txt', 'mimeType' => 'text/plain'], 'fileSize'];
    }

    /**
     * @param array<string, int|string> $payload
     */
    #[DataProvider('incompleteInitPayloads')]
    public function testInitRejectsAnIncompletePayload(array $payload, string $missingField): void
    {
        $response = $this->controller()->init($this->jsonRequest($payload));

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString($missingField, $this->decode($response)['error']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function malformedInitPayloads(): iterable
    {
        yield 'filename as a list' => [['filename' => ['a.txt'], 'fileSize' => 1000], 'filename'];
        yield 'file size as text' => [['filename' => 'a.txt', 'fileSize' => 'large'], 'fileSize'];
        yield 'MIME type as a number' => [['filename' => 'a.txt', 'fileSize' => 1000, 'mimeType' => 42], 'mimeType'];
        yield 'uploader as a list' => [['filename' => 'a.txt', 'fileSize' => 1000, 'uploader' => ['avatar']], 'uploader'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('malformedInitPayloads')]
    public function testInitRejectsAMalformedPayload(array $payload, string $field): void
    {
        $response = $this->controller()->init($this->jsonRequest($payload));

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString($field, $this->decode($response)['error']);
    }

    public function testMethodNotAllowed(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);

        self::assertSame(405, $controller->handle(Request::create($uploadUrl, 'PATCH'), $uploadId)->getStatusCode());
    }

    public function testInitWithNamedUploader(): void
    {
        $namedUploader = new Uploader(new InMemoryStorage(), $this->uploadUrlGenerator, new EventDispatcher(), name: 'avatar');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => 'avatar' === $id);
        $container->method('get')->willReturnCallback(static fn (string $id) => 'avatar' === $id ? $namedUploader : null);

        $response = $this->controller(uploaders: $container)->init($this->jsonRequest([
            'filename' => 'avatar.png',
            'fileSize' => 500,
            'mimeType' => 'image/png',
            'uploader' => 'avatar',
        ]));

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayHasKey('uploadId', $this->decode($response));
    }

    public function testInitWithUnknownUploaderReturns400(): void
    {
        $response = $this->controller()->init($this->jsonRequest([
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
            'uploader' => 'nonexistent',
        ]));

        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('Unknown uploader', $this->decode($response)['error']);
    }

    public function testInitDefaultMimeType(): void
    {
        $response = $this->controller()->init($this->jsonRequest(['filename' => 'test.bin', 'fileSize' => 100]));

        self::assertSame(200, $response->getStatusCode());
        $uploadId = $this->decode($response)['uploadId'];
        self::assertSame('application/octet-stream', $this->metadata($uploadId)['mimeType']);
    }

    public function testHandleStatusReturns404WhenSessionMissing(): void
    {
        $controller = $this->controller();
        [$uploadId, $uploadUrl] = $this->initUpload($controller);
        $this->uploader->cancelUpload($uploadId);

        self::assertSame(404, $controller->handle(Request::create($uploadUrl, 'GET'), $uploadId)->getStatusCode());
    }

    public function testInitRateLimitRejectsWhenExceeded(): void
    {
        $controller = $this->controller(initRateLimiter: new RateLimiterFactory(
            ['id' => 'ux_upload_init', 'policy' => 'sliding_window', 'limit' => 2, 'interval' => '1 minute'],
            new RateLimiterStorage(),
        ));
        $payload = ['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain'];

        self::assertSame(200, $controller->init($this->jsonRequest($payload))->getStatusCode());
        self::assertSame(200, $controller->init($this->jsonRequest($payload))->getStatusCode());

        $response = $controller->init($this->jsonRequest($payload));
        self::assertSame(429, $response->getStatusCode());
        self::assertStringContainsString('Too many requests', $this->decode($response)['error']);
    }

    public function testResumeRejectsMissingCsrfHandlerAndInvalidPolicy(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $resumeHandler = new ResumeTokenHandler($this->uriSigner);
        $withCsrf = $this->controller(csrfTokenManager: $csrf, resumeTokenHandler: $resumeHandler);
        self::assertSame(Response::HTTP_FORBIDDEN, $withCsrf->resume(new Request(content: '{}'))->getStatusCode());

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $this->controller()->resume(new Request(content: '{"resumeToken":"token"}'))->getStatusCode(),
        );

        $withPolicy = $this->controller(
            policySigner: new UploadPolicySigner($this->uriSigner),
            resumeTokenHandler: $resumeHandler,
        );
        $request = $this->jsonRequest([
            'resumeToken' => 'invalid',
            'policyToken' => 'invalid',
        ]);
        self::assertSame(Response::HTTP_FORBIDDEN, $withPolicy->resume($request)->getStatusCode());
    }

    public function testResumeRejectsMissingSessionAndMismatchedContext(): void
    {
        $handler = new ResumeTokenHandler($this->uriSigner);
        $controller = $this->controller(resumeTokenHandler: $handler);
        $request = $this->jsonRequest([
            'resumeToken' => $handler->generate('missing', new UploadContext()),
        ]);
        self::assertSame(Response::HTTP_NOT_FOUND, $controller->resume($request)->getStatusCode());

        $this->storage->initiate('owned', [
            'ownerId' => 'owner-a',
            'tenantId' => null,
            'field' => null,
            'uploader' => 'default',
        ]);
        $ownerB = $this->contextResolver('owner-b');
        $ownedController = $this->controller(contextResolver: $ownerB, resumeTokenHandler: $handler);
        $ownedRequest = $this->jsonRequest([
            'resumeToken' => $handler->generate('owned', $ownerB->resolve()),
        ]);
        self::assertSame(Response::HTTP_FORBIDDEN, $ownedController->resume($ownedRequest)->getStatusCode());
    }

    public function testResumeMapsDomainAndUnexpectedFailures(): void
    {
        $handler = new ResumeTokenHandler($this->uriSigner);
        $token = $handler->generate('upload-1', new UploadContext());

        $missingStorage = $this->createStub(StorageInterface::class);
        $missingStorage->method('getMetadata')->willThrowException(new UploadSessionNotFoundException('upload-1'));
        $missing = $this->controller(storage: $missingStorage, resumeTokenHandler: $handler);
        self::assertSame(Response::HTTP_NOT_FOUND, $missing->resume($this->jsonRequest(['resumeToken' => $token]))->getStatusCode());

        $this->storage->initiate('unknown-uploader', [
            'uploader' => 'missing',
            'ownerId' => null,
            'tenantId' => null,
            'field' => null,
        ]);
        $invalid = $this->controller(resumeTokenHandler: $handler);
        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $invalid->resume($this->jsonRequest(['resumeToken' => $handler->generate('unknown-uploader', new UploadContext())]))->getStatusCode(),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $brokenStorage = $this->createStub(StorageInterface::class);
        $brokenStorage->method('getMetadata')->willThrowException(new \RuntimeException('backend down'));
        $broken = $this->controller(storage: $brokenStorage, logger: $logger, resumeTokenHandler: $handler);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $broken->resume($this->jsonRequest(['resumeToken' => $token]))->getStatusCode());
    }

    public function testRemoveRejectsCsrfPolicyAndTokenFailures(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $this->controller(csrfTokenManager: $csrf)->remove(new Request(content: '{}'))->getStatusCode(),
        );

        $controller = $this->controller(policySigner: new UploadPolicySigner($this->uriSigner));
        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $controller->remove($this->jsonRequest(['policyToken' => 'invalid']))->getStatusCode(),
        );
        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $controller->remove($this->jsonRequest(['token' => 'invalid']))->getStatusCode(),
        );
    }

    public function testRemoveLogsStorageFailure(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('delete')->willThrowException(new \RuntimeException('delete failed'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $handler = new UploadTokenHandler($this->uriSigner, $storage);
        $upload = new UploadedFileFactory()->create($storage);
        $controller = $this->controller(tokenHandler: $handler, storage: $storage, logger: $logger);

        $response = $controller->remove($this->jsonRequest(['token' => $handler->generate($upload)]));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
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
        $controller = $this->controller(uploader: $uploader);
        $url = $this->uploadUrlGenerator->generateUploadUrl('small-chunks');

        $declared = $this->chunkRequest($url, 0, 'data');
        $declared->headers->set('Content-Length', '9');
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->handle($declared, 'small-chunks')->getStatusCode());

        $actual = $this->chunkRequest($url, 0, '12345');
        $actual->headers->remove('Content-Length');
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->handle($actual, 'small-chunks')->getStatusCode());
    }

    public function testAnonymousInitializationIsRejectedByDefault(): void
    {
        $controller = new UploadController(
            $this->uploader,
            $this->uploadUrlGenerator,
            new UploadTokenHandler($this->uriSigner, $this->storage),
            $this->storage,
        );

        $response = $controller->init($this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain']));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertStringContainsString('Anonymous uploads are disabled', (string) $response->getContent());
    }

    public function testAnonymousInitializationIsAcceptedWhenExplicitlyAllowed(): void
    {
        $controller = $this->controller(allowAnonymous: true);

        self::assertSame(
            Response::HTTP_OK,
            $controller->init($this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain']))->getStatusCode(),
        );
    }

    public function testAnonymousDirectUploadIsRejectedWhenAnonymousUploadsAreDisabled(): void
    {
        $parameters = ['filename' => 'test.txt', 'fileSize' => 4, 'mimeType' => 'text/plain'];

        $response = $this->controller(allowAnonymous: false)->direct($this->directRequest('data', $parameters));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Anonymous uploads are disabled', $this->decode($response)['error']);
        self::assertSame(0, $this->storage->countPendingByContext(new UploadContext()));
        self::assertSame(Response::HTTP_OK, $this->controller(allowAnonymous: true)->direct($this->directRequest('data', $parameters))->getStatusCode());
    }

    public function testAnonymousResumeIsRejectedWhenAnonymousUploadsAreDisabled(): void
    {
        $handler = new ResumeTokenHandler($this->uriSigner);
        $pending = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $request = $this->jsonRequest(['resumeToken' => $handler->generate($pending->uploadId, new UploadContext())]);

        $response = $this->controller(resumeTokenHandler: $handler, allowAnonymous: false)->resume($request);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Anonymous uploads are disabled', $this->decode($response)['error']);
        self::assertSame(Response::HTTP_OK, $this->controller(resumeTokenHandler: $handler, allowAnonymous: true)->resume($request)->getStatusCode());
    }

    public function testAnonymousRemoveIsRejectedWhenAnonymousUploadsAreDisabled(): void
    {
        $tokenHandler = new UploadTokenHandler($this->uriSigner, $this->storage);
        $pending = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');
        $completed = $this->uploader->completeUpload($pending->uploadId);
        $request = $this->jsonRequest(['token' => $tokenHandler->generate($completed)]);

        $response = $this->controller(tokenHandler: $tokenHandler, allowAnonymous: false)->remove($request);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Anonymous uploads are disabled', $this->decode($response)['error']);
        self::assertTrue($this->storage->exists($completed->getStoragePath()));
        self::assertSame(Response::HTTP_OK, $this->controller(tokenHandler: $tokenHandler, allowAnonymous: true)->remove($request)->getStatusCode());
        self::assertFalse($this->storage->exists($completed->getStoragePath()));
    }

    public function testAnIdentifiedOwnerNeedsNoAnonymousOptIn(): void
    {
        $contextResolver = $this->contextResolver('owner-1');
        $controller = $this->controller(
            tokenHandler: new UploadTokenHandler($this->uriSigner, $this->storage, contextResolver: $contextResolver),
            contextResolver: $contextResolver,
            allowAnonymous: false,
        );

        self::assertSame(
            Response::HTTP_OK,
            $controller->init($this->jsonRequest(['filename' => 'test.txt', 'fileSize' => 1000, 'mimeType' => 'text/plain']))->getStatusCode(),
        );
    }

    private function controller(
        ?UploaderInterface $uploader = null,
        ?StorageInterface $storage = null,
        ?UploadTokenHandler $tokenHandler = null,
        ?ContainerInterface $uploaders = null,
        ?LoggerInterface $logger = null,
        ?CsrfTokenManagerInterface $csrfTokenManager = null,
        ?RateLimiterFactoryInterface $initRateLimiter = null,
        UploadContextResolverInterface $contextResolver = new AnonymousUploadContextResolver(),
        ?UploadPolicySigner $policySigner = null,
        ?ResumeTokenHandler $resumeTokenHandler = null,
        bool $allowAnonymous = true,
    ): UploadController {
        $storage ??= $this->storage;

        return new UploadController(
            $uploader ?? $this->uploader,
            $this->uploadUrlGenerator,
            $tokenHandler ?? new UploadTokenHandler($this->uriSigner, $storage),
            $storage,
            $uploaders,
            $logger,
            $csrfTokenManager,
            $initRateLimiter,
            $contextResolver,
            $policySigner,
            $resumeTokenHandler,
            $allowAnonymous,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(string $uploadId): array
    {
        $metadata = $this->storage->getMetadata($uploadId);
        self::assertNotNull($metadata);

        return $metadata;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function jsonRequest(array $body): Request
    {
        return new Request(content: json_encode($body, \JSON_THROW_ON_ERROR));
    }

    private function chunkRequest(string $uploadUrl, int $index, string $content): Request
    {
        return Request::create($uploadUrl, 'PUT', server: ['HTTP_X_CHUNK_INDEX' => (string) $index], content: $content);
    }

    /**
     * @return array{string, string} the upload ID and its signed URL
     */
    private function initUpload(UploadController $controller, int $fileSize = 1000): array
    {
        $response = $controller->init($this->jsonRequest(['filename' => 'test.txt', 'fileSize' => $fileSize, 'mimeType' => 'text/plain']));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = $this->decode($response);

        return [$data['uploadId'], $data['uploadUrl']];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
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
