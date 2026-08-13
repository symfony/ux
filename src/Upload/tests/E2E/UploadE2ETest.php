<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\E2E;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Testing\PlaywrightTestCaseTrait;

/**
 * Browser end-to-end tests for the real Stimulus controller and generated form theme.
 *
 * Server lifecycle: PlaywrightPHP drives a real Chromium instance that navigates
 * to real URLs, so the form has to be served over HTTP. setUp() boots the
 * {@see TestKernel} behind PHP's built-in web server (`php -S`) via proc_open on
 * a free localhost port, pointing it at tests/E2E/public/index.php and isolating
 * its cache/logs/storage under a per-test temp directory (passed through the
 * UX_UPLOAD_E2E_DIR env var). PHP_CLI_SERVER_WORKERS lets the single-process
 * server handle the browser's concurrent connections during an upload. tearDown()
 * stops the browser first, then the server, then removes the temp directory.
 *
 * These tests require a running browser and are excluded from the default suite
 * via the "integration" group (see phpunit.xml.dist).
 */
#[Group('integration')]
#[CoversNothing]
final class UploadE2ETest extends TestCase
{
    use PlaywrightTestCaseTrait;

    /** @var resource|null */
    private $serverProcess;

    private string $workingDir = '';
    private string $baseUrl = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingDir = sys_get_temp_dir().'/ux_upload_e2e_'.bin2hex(random_bytes(6));
        mkdir($this->workingDir, 0o777, true);

        $port = self::findFreePort();
        $this->baseUrl = 'http://127.0.0.1:'.$port;

        $router = __DIR__.'/public/index.php';
        $env = [
            'UX_UPLOAD_E2E_DIR' => $this->workingDir,
            'PHP_CLI_SERVER_WORKERS' => '6',
            'PATH' => getenv('PATH') ?: '',
        ];

        $process = proc_open(
            [\PHP_BINARY, '-S', '127.0.0.1:'.$port, $router],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', $this->workingDir.'/server.log', 'a'],
                2 => ['file', $this->workingDir.'/server.log', 'a'],
            ],
            $pipes,
            \dirname($router),
            $env,
        );

        if (!\is_resource($process)) {
            self::fail('Failed to start the PHP built-in server for E2E tests.');
        }
        $this->serverProcess = $process;

        // PHPUnit does not call tearDown() when setUp() throws, so anything that
        // can fail after the server is up (readiness wait, browser launch) must
        // stop the server itself before re-throwing, or it leaks for the life of
        // the machine.
        try {
            $this->waitForServer($this->baseUrl.'/upload/test');
            $browserChannel = $_SERVER['PLAYWRIGHT_BROWSER_CHANNEL'] ?? getenv('PLAYWRIGHT_BROWSER_CHANNEL');
            $this->setUpPlaywright(customConfig: \is_string($browserChannel) && '' !== $browserChannel
                ? new PlaywrightConfig(channel: $browserChannel)
                : null);
        } catch (\Throwable $e) {
            $this->stopServer();
            self::rrmdir($this->workingDir);

            throw $e;
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->tearDownPlaywright();
        } finally {
            // Stop the server even if Playwright teardown throws (it captures a
            // screenshot on a failing test, which can raise when the page is
            // unhealthy). Skipping this is what orphaned the `php -S` masters
            // and their worker children.
            $this->stopServer();
            self::rrmdir($this->workingDir);

            parent::tearDown();
        }
    }

    /**
     * Stops the built-in web server started in {@see setUp()} and guarantees no
     * `php -S` process survives the test.
     *
     * With PHP_CLI_SERVER_WORKERS set, the built-in server forks that many worker
     * children under the master process. proc_terminate() SIGTERMs only the
     * master; the master normally reaps its workers on shutdown, but a worker
     * stuck mid-request can miss that shutdown and be reparented to launchd/init,
     * where the master PID can no longer reach it. To close that race this method:
     *
     *   1. snapshots the worker PIDs *before* terminating, while the master still
     *      owns them and `pgrep -P <master>` can still enumerate them,
     *   2. SIGTERMs the master and blocks in proc_close() until it exits,
     *   3. SIGKILLs any master/worker PID still alive afterwards.
     *
     * Called from both the setUp() failure path and the tearDown() finally, so it
     * is idempotent and safe to call when the server was never started.
     */
    private function stopServer(): void
    {
        if (!\is_resource($this->serverProcess)) {
            return;
        }

        $status = proc_get_status($this->serverProcess);
        $masterPid = $status['running'] ? (int) $status['pid'] : null;
        $workerPids = null !== $masterPid ? self::childPids($masterPid) : [];

        proc_terminate($this->serverProcess, \SIGTERM);
        proc_close($this->serverProcess);
        $this->serverProcess = null;

        foreach ([...$workerPids, $masterPid] as $pid) {
            if (null !== $pid && @posix_kill($pid, 0)) {
                @posix_kill($pid, \SIGKILL);
            }
        }
    }

    /**
     * @return list<int> PIDs of the given process's direct children (its workers)
     */
    private static function childPids(int $pid): array
    {
        $result = @shell_exec('pgrep -P '.escapeshellarg((string) $pid).' 2>/dev/null');
        if (!\is_string($result)) {
            return [];
        }

        $pids = [];
        foreach (preg_split('/\s+/', trim($result)) ?: [] as $line) {
            if (is_numeric($line)) {
                $pids[] = (int) $line;
            }
        }

        return $pids;
    }

    public function testDropzoneAndFileInputRender(): void
    {
        $this->openPage('/upload/test?layout=dropzone&preview=true');

        $this->expect($this->page->locator('.ux-upload'))->toBeVisible();
        $this->expect($this->page->locator('.ux-upload__dropzone'))->toBeVisible();
        $this->expect($this->page->locator('.ux-upload__input'))->toHaveCount(1);
        $this->expect($this->page->locator('.ux-upload__list > .ux-upload__dropzone'))->toHaveCount(1);
        $this->expect($this->page->locator('.ux-upload'))->toHaveAttribute('data-ux-upload-layout', 'dropzone');
        self::assertStringContainsString('ux-upload--previews', (string) $this->page->locator('.ux-upload')->getAttribute('class'));
        $this->expect($this->page->locator('[data-test-upload-global-theme]'))->toHaveCount(1);
    }

    public function testDropzoneRendersASeparatePickerInstruction(): void
    {
        $this->openPage('/upload/test?layout=dropzone');

        $this->expect($this->page->locator('.ux-upload__instruction'))->toHaveText('Drop files here or click to browse');
        self::assertSame('SPAN', $this->page->locator('.ux-upload__instruction')->evaluate('(element) => element.tagName'));
        $this->expect($this->page->locator('.ux-upload__input'))->toHaveAttribute('aria-label', 'File upload area. Drop files or press to browse.');
    }

    public function testApplicationFormThemeCanOverrideOneBlockAndRenderItsParent(): void
    {
        $this->openPage('/upload/test?layout=dropzone&theme=application');

        $override = $this->page->locator('[data-test-upload-dropzone-block-override]');
        $this->expect($override)->toHaveCount(1);
        $this->expect($override->locator('.ux-upload__dropzone'))->toBeVisible();
        $this->expect($override->locator('.ux-upload__input'))->toHaveCount(1);
    }

    public function testRootAttributesUseSymfonyFormEscapingAndBooleanSemantics(): void
    {
        $this->openPage('/upload/test?attributes=true');

        $upload = $this->page->locator('.ux-upload');
        self::assertStringContainsString('document-upload', (string) $upload->getAttribute('class'));
        self::assertStringContainsString('symfony--ux-upload--upload', (string) $upload->getAttribute('data-controller'));
        self::assertStringContainsString('test-widget', (string) $upload->getAttribute('data-controller'));
        $this->expect($upload)->toHaveAttribute('data-test-widget-connected', 'true');
        $this->expect($upload)->toHaveAttribute('data-label', '"><script data-test-injected-script>alert(1)</script>');
        $this->expect($upload)->toHaveAttribute('data-ready', 'data-ready');
        self::assertNull($upload->getAttribute('data-omitted'));
        $input = $this->page->locator('.ux-upload__input');
        self::assertStringContainsString('document-file-input', (string) $input->getAttribute('class'));
        $this->expect($input)->toHaveAttribute('capture', 'environment');
        $this->expect($input)->toHaveAttribute('data-input-label', 'native-file-input');
        self::assertNotSame('application-controlled-id', $input->getAttribute('id'));
        $this->expect($input)->toHaveAttribute('type', 'file');
        self::assertNull($input->getAttribute('name'));
        self::assertNull($input->getAttribute('value'));
        self::assertNull($input->getAttribute('multiple'));
        self::assertNull($input->getAttribute('required'));
        self::assertNull($input->getAttribute('disabled'));
        $this->expect($this->page->locator('script[data-test-injected-script]'))->toHaveCount(0);
    }

    public function testFormRowRendersLabelHelpAndTransformationErrorsOnTheNativeInput(): void
    {
        $this->openPage('/upload/test?layout=dropzone&row=true&invalid=true&attributes=true');

        $row = $this->page->locator('[data-test-row]');
        $input = $row->locator('.ux-upload__input');
        $inputId = $input->getAttribute('id');
        self::assertNotNull($inputId);

        $this->expect($row->locator('label[data-test-label]'))->toHaveText('Attachments');
        $this->expect($row->locator('label[data-test-label]'))->toHaveAttribute('for', $inputId);
        $this->expect($row->locator('[data-test-help]'))->toHaveText('Upload one or more documents.');
        $this->expect($row->locator('ul li'))->toHaveText('The uploaded file reference is invalid or has expired.');
        $this->expect($input)->toHaveAttribute('aria-invalid', 'true');

        $describedBy = (string) $input->getAttribute('aria-describedby');
        self::assertStringContainsString($inputId.'_help', $describedBy);
        self::assertStringContainsString($inputId.'_error1', $describedBy);
        $this->expect($row->locator('#'.$inputId.'_help'))->toHaveCount(1);
        $this->expect($row->locator('#'.$inputId.'_error1'))->toHaveCount(1);

        $this->expect($row->locator('input[name]'))->toHaveCount(1);
        $this->expect($row->locator('input[type="hidden"]'))->toHaveAttribute('name', 'document[attachments]');
        self::assertNull($input->getAttribute('name'));
    }

    public function testFormRowDelegatesToTheActiveApplicationFormTheme(): void
    {
        $this->openPage('/upload/test?row=true&invalid=true&theme=bootstrap');
        $this->expect($this->page->locator('.mb-3 > label.form-label'))->toHaveText('Attachments');
        $this->expect($this->page->locator('.mb-3 .invalid-feedback'))->toHaveText('The uploaded file reference is invalid or has expired.');

        $this->openPage('/upload/test?row=true&invalid=true&theme=tailwind');
        $this->expect($this->page->locator('.mb-6 > label.block'))->toHaveText('Attachments');
        $this->expect($this->page->locator('.mb-6 .text-red-700'))->toHaveText('The uploaded file reference is invalid or has expired.');
    }

    public function testUploadWorksWhenOptionalPresentationBlocksAreEmpty(): void
    {
        $this->openPage('/upload/test?theme=minimal');

        $file = $this->workingDir.'/minimal.txt';
        file_put_contents($file, 'minimal template');
        $this->page->locator('.ux-upload__input')->setInputFiles($file);

        $this->expect($this->page->locator('.ux-upload__item[data-status="completed"]'))->toBeVisible();
        $this->expect($this->page->locator('.ux-upload__visual'))->toHaveCount(0);
        $this->expect($this->page->locator('.ux-upload__progress'))->toHaveCount(0);
        $this->expect($this->page->locator('.ux-upload__actions'))->toHaveCount(0);
        $this->expect($this->page->locator('.ux-upload__summary'))->toHaveCount(0);
        $this->expect($this->page->locator('.ux-upload__errors'))->toHaveCount(0);
        self::assertStringContainsString('"token"', $this->page->locator('.ux-upload input[type="hidden"]')->inputValue());
    }

    public function testUploadSmallFileCompletes(): void
    {
        $this->openPage('/upload/test');

        $file = $this->workingDir.'/hello.txt';
        file_put_contents($file, 'hello world');

        $this->page->locator('.ux-upload__input')->setInputFiles($file);

        // The item reaches the "completed" status once every chunk has been stored
        // and the server has confirmed the upload.
        $this->expect($this->page->locator('.ux-upload__item[data-status="completed"]'))
            ->toBeVisible();

        // The full round-trip succeeded only if the controller wrote the signed
        // completion token back into the form's hidden result input.
        $resultValue = $this->page->locator('.ux-upload input[type="hidden"]')->inputValue();
        self::assertStringContainsString('"token"', $resultValue);

        self::assertSame(['POST /_upload'], $this->uploadRequests());
    }

    public function testUploadMultipleFilesCompletes(): void
    {
        $this->openPage('/upload/test?multiple=true');

        $first = $this->workingDir.'/a.txt';
        $second = $this->workingDir.'/b.txt';
        file_put_contents($first, 'first file');
        file_put_contents($second, 'second file');

        $this->page->locator('.ux-upload__input')->setInputFiles([$first, $second]);

        $this->expect($this->page->locator('.ux-upload__item'))->toHaveCount(2);
        $this->expect($this->page->locator('.ux-upload__item[data-status="completed"]'))
            ->toHaveCount(2);
    }

    public function testDropzoneHasAccessibleAttributes(): void
    {
        $this->openPage('/upload/test?layout=dropzone');

        $dropzone = $this->page->locator('.ux-upload__dropzone');
        self::assertNull($dropzone->getAttribute('role'));
        $this->expect($this->page->locator('.ux-upload__input'))->toHaveAttribute('aria-label', 'File upload area. Drop files or press to browse.');
    }

    public function testPickerMergesAllDropzoneActions(): void
    {
        $expectedActions = [
            'dragover->symfony--ux-upload--upload#dragover',
            'dragleave->symfony--ux-upload--upload#dragleave',
            'drop->symfony--ux-upload--upload#drop',
            'paste->symfony--ux-upload--upload#paste',
        ];

        $this->openPage('/upload/test');
        $actions = $this->page->locator('[data-symfony--ux-upload--upload-target="dropzone"]')
            ->getAttribute('data-action');

        self::assertNotNull($actions);
        foreach ($expectedActions as $expectedAction) {
            self::assertStringContainsString($expectedAction, $actions);
        }
        self::assertSame(4, substr_count($actions, '->'));
    }

    public function testManualUploadButtonIsRenderedInsideControllerScope(): void
    {
        $this->openPage('/upload/test?manual=true');

        $button = $this->page->locator('.ux-upload > .ux-upload__start');
        $this->expect($button)->toBeHidden();

        $file = $this->workingDir.'/manual.txt';
        file_put_contents($file, 'manual upload');
        $this->page->locator('.ux-upload__input')->setInputFiles($file);

        $this->expect($button)->toBeVisible();
        $this->expect($button)->toHaveAttribute(
            'data-action',
            'symfony--ux-upload--upload#startAll',
        );
    }

    public function testUnstyledActionsUseNativeHiddenState(): void
    {
        $this->openPage('/upload/test');

        $file = $this->workingDir.'/actions.txt';
        file_put_contents($file, 'action states');
        $this->page->locator('.ux-upload__input')->setInputFiles($file);

        $item = $this->page->locator('.ux-upload__item[data-status="completed"]');
        $this->expect($item)->toBeVisible();
        $this->expect($item->locator('[data-ux-upload-action="remove"]'))->toBeVisible();
        $this->expect($item->locator('[data-ux-upload-action="pause"]'))->toBeHidden();
        $this->expect($item->locator('[data-ux-upload-action="resume"]'))->toBeHidden();
        $this->expect($item->locator('[data-ux-upload-action="cancel"]'))->toBeHidden();
        $this->expect($item->locator('[data-ux-upload-action="retry"]'))->toBeHidden();
    }

    public function testOptionalStylesFollowTheApplicationColorScheme(): void
    {
        $this->openPage('/upload/test?styled=true&scheme=light');
        $light = $this->page->locator('.ux-upload__dropzone')->evaluate(
            '(element) => getComputedStyle(element).backgroundColor',
        );

        $this->openPage('/upload/test?styled=true&scheme=dark');
        $dark = $this->page->locator('.ux-upload__dropzone')->evaluate(
            '(element) => getComputedStyle(element).backgroundColor',
        );

        self::assertSame('rgb(255, 255, 255)', $light);
        self::assertSame('rgb(24, 24, 27)', $dark);
    }

    public function testSecondImmediateFormSubmissionIsPrevented(): void
    {
        $this->openPage('/upload/test');

        $state = $this->page->evaluate(<<<'JS'
            () => {
                const form = document.querySelector('form');
                const submitter = form.querySelector('button[type="submit"]');
                const first = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter });
                const second = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter });

                return {
                    firstAccepted: form.dispatchEvent(first),
                    secondAccepted: form.dispatchEvent(second),
                    secondPrevented: second.defaultPrevented,
                    busy: form.getAttribute('aria-busy'),
                };
            }
            JS);

        self::assertSame([
            'firstAccepted' => true,
            'secondAccepted' => false,
            'secondPrevented' => true,
            'busy' => 'true',
        ], $state);
    }

    private function openPage(string $path): void
    {
        $this->page->goto($this->baseUrl.$path);
        $this->expect($this->page->locator('html'))->toHaveAttribute('data-ux-upload-ready', 'true');
    }

    /** @return list<string> */
    private function uploadRequests(): array
    {
        $contents = @file_get_contents($this->workingDir.'/requests.log');

        return false === $contents ? [] : array_values(array_filter(explode("\n", trim($contents))));
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (false === $socket) {
            self::fail(\sprintf('Cannot allocate a free port: %s (%d)', $errstr, $errno));
        }
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private function waitForServer(string $url): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $context = stream_context_create(['http' => ['timeout' => 1, 'ignore_errors' => true]]);
            $body = @file_get_contents($url, false, $context);
            if (false !== $body && str_contains($body, 'ux-upload__dropzone')) {
                return;
            }
            usleep(100_000);
        }

        $log = @file_get_contents($this->workingDir.'/server.log') ?: '(no server log)';
        self::fail("E2E server did not become ready at {$url}.\nServer log:\n".$log);
    }

    private static function rrmdir(string $dir): void
    {
        if ('' === $dir || !is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
