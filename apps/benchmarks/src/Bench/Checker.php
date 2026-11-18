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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Checker
{
    /**
     * @var array<string, string>
     */
    private array $pages = [];

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LiveRequestBuilder $builder,
        #[Autowire('%kernel.project_dir%/var/bench')]
        private readonly string $directory,
    ) {
    }

    /**
     * @param list<Target> $targets
     *
     * @return list<TargetStats>
     */
    public function check(string $baseUrl, array $targets, bool $compareWithSaved): array
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0o777, true);
        }

        $stats = [];
        $this->pages = [];

        foreach ($targets as $target) {
            if ($target->isLive()) {
                $this->checkLive($baseUrl, $target);

                continue;
            }

            $html = $this->checkPage($baseUrl, $target, $compareWithSaved);
            $this->pages[$target->path] = $html;
            $stats[] = new TargetStats($target, $this->pageStats($target, $html));
        }

        return $stats;
    }

    public function page(string $path): string
    {
        return $this->pages[$path] ?? throw new \LogicException(\sprintf('The page "%s" was not checked.', $path));
    }

    public function pageFile(Target $target): string
    {
        return $this->file($target, 'html');
    }

    public function bodyFile(Target $target): string
    {
        return $this->file($target, 'body');
    }

    private function checkPage(string $baseUrl, Target $target, bool $compareWithSaved): string
    {
        $url = $baseUrl.$target->path;
        $first = $this->fetch($target->name, $target->expectedStatus, 'GET', $url);
        $second = $this->fetch($target->name, $target->expectedStatus, 'GET', $url);
        $this->assertIdentical($target, $first, $second);
        $this->save($target, $first, $compareWithSaved);

        return $first;
    }

    private function checkLive(string $baseUrl, Target $target): void
    {
        $page = $target->scenario->page;
        $this->pages[$page] ??= $this->fetch($page, 200, 'GET', $baseUrl.$page);

        $request = $this->builder->build($this->pages[$page], $target->scenario);
        $options = [
            'headers' => [...$request->headers(), 'Content-Type' => $request->contentType()],
            'body' => $request->body,
        ];

        $url = $baseUrl.$request->url;
        $first = $this->fetch($target->name, $target->expectedStatus, 'POST', $url, $options);
        $second = $this->fetch($target->name, $target->expectedStatus, 'POST', $url, $options);
        $this->assertIdentical($target, $first, $second);
        file_put_contents($this->bodyFile($target), $request->body);
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

    private function save(Target $target, string $html, bool $compareWithSaved): void
    {
        $file = $this->pageFile($target);

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

    private function file(Target $target, string $extension): string
    {
        return \sprintf('%s/%s.%s', $this->directory, str_replace('/', '-', $target->name), $extension);
    }
}
