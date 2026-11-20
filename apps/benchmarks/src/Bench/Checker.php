<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Checker
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LiveRequestBuilder $builder,
    ) {
    }

    /**
     * @param list<Target> $targets
     */
    public function check(string $baseUrl, array $targets, bool $compareWithSaved, string $directory, bool $lenient): CheckResult
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        $stats = [];
        $pages = [];
        $unavailable = [];
        $unavailablePaths = [];

        foreach ($targets as $target) {
            try {
                if ($target->isLive()) {
                    $this->checkLive($baseUrl, $target, $directory, $lenient, $pages, $unavailablePaths);

                    continue;
                }

                $html = $this->checkPage($baseUrl, $target, $compareWithSaved, $directory);
                $pages[$target->path] = $html;
                $stats[] = new TargetStats($target, $this->pageStats($target, $html));
            } catch (\RuntimeException|\JsonException $exception) {
                if (!$lenient) {
                    throw $exception;
                }

                $unavailable[$target->name] = $exception->getMessage();
                if (!$target->isLive()) {
                    $unavailablePaths[$target->path] = true;
                }
            }
        }

        return new CheckResult($directory, $stats, $unavailable, $pages);
    }

    private function checkPage(string $baseUrl, Target $target, bool $compareWithSaved, string $directory): string
    {
        $url = $baseUrl.$target->path;
        $first = $this->fetch($target->name, $target->expectedStatus, 'GET', $url);
        $second = $this->fetch($target->name, $target->expectedStatus, 'GET', $url);
        $this->assertIdentical($target, $first, $second);
        $this->save($target, $first, $compareWithSaved, $directory);

        return $first;
    }

    /**
     * @param array<string, string> $pages
     * @param array<string, true>   $unavailablePaths
     */
    private function checkLive(string $baseUrl, Target $target, string $directory, bool $lenient, array &$pages, array &$unavailablePaths): void
    {
        $page = $target->scenario->page;

        if (isset($unavailablePaths[$page])) {
            throw $this->pageUnavailable($page);
        }

        if (!isset($pages[$page])) {
            try {
                $pages[$page] = $this->fetch($page, 200, 'GET', $baseUrl.$page);
            } catch (\RuntimeException $exception) {
                if (!$lenient) {
                    throw $exception;
                }

                $unavailablePaths[$page] = true;

                throw $this->pageUnavailable($page);
            }
        }

        $request = $this->builder->build($pages[$page], $target->scenario);
        $options = [
            'headers' => [...$request->headers(), 'Content-Type' => $request->contentType()],
            'body' => $request->body,
        ];

        $url = $baseUrl.$request->url;
        $first = $this->fetch($target->name, $target->expectedStatus, 'POST', $url, $options);
        $second = $this->fetch($target->name, $target->expectedStatus, 'POST', $url, $options);
        $this->assertIdentical($target, $first, $second);
        file_put_contents(CheckResult::file($directory, $target, 'body'), $request->body);
    }

    private function pageUnavailable(string $page): \RuntimeException
    {
        return new \RuntimeException(\sprintf('Its page "%s" is not available.', $page));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function fetch(string $name, int $expectedStatus, string $method, string $url, array $options = []): string
    {
        $response = $this->client->request($method, $url, ['max_redirects' => 0, ...$options]);
        $status = $response->getStatusCode();

        if ($expectedStatus !== $status) {
            throw new \RuntimeException(\sprintf('"%s" answered %d, expected %d.', $name, $status, $expectedStatus));
        }

        return $response->getContent(false);
    }

    private function assertIdentical(Target $target, string $first, string $second): void
    {
        if ($first !== $second) {
            throw new \RuntimeException(\sprintf('"%s" renders a different HTML on two requests.', $target->name));
        }
    }

    private function save(Target $target, string $html, bool $compareWithSaved, string $directory): void
    {
        $file = CheckResult::file($directory, $target, 'html');

        if ($compareWithSaved && is_file($file)) {
            if (file_get_contents($file) !== $html) {
                throw new \RuntimeException(\sprintf('"%s" renders a different HTML than the previous mode.', $target->name));
            }

            return;
        }

        file_put_contents($file, $html);
    }

    private function pageStats(Target $target, string $html): PageStats
    {
        $stats = PageStats::fromHtml($html);
        $counts = $stats->counts();

        foreach ($target->minimumCounts as $key => $minimum) {
            if ($counts[$key] < $minimum) {
                throw new \RuntimeException(\sprintf('"%s" holds %d "%s", at least %d expected.', $target->name, $counts[$key], $key, $minimum));
            }
        }

        return $stats;
    }
}
