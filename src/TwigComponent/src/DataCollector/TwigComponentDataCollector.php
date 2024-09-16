<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\VarDumper\Caster\ClassStub;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\EventListener\TwigComponentLoggerListener;
use Twig\Environment;
use Twig\Error\LoaderError;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TwigComponentDataCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    private bool $hasStub;

    public function __construct(
        private readonly TwigComponentLoggerListener $logger,
        private readonly Environment $twig,
    ) {
        $this->hasStub = class_exists(ClassStub::class);
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
    }

    public function lateCollect(): void
    {
        $this->collectDataFromLogger();
        $this->data = $this->cloneVar($this->data);
    }

    public function getData(): array|Data
    {
        return $this->data;
    }

    public function getName(): string
    {
        return 'twig_component';
    }

    public function reset(): void
    {
        $this->logger->reset();
        parent::reset();
    }

    public function getComponents(): array|Data
    {
        return $this->data['components'] ?? [];
    }

    public function getComponentCount(): int
    {
        return $this->data['component_count'] ?? 0;
    }

    public function getPeakMemoryUsage(): int
    {
        return $this->data['peak_memory_usage'] ?? 0;
    }

    public function getRenders(): array|Data
    {
        return $this->data['renders'] ?? [];
    }

    public function getRenderCount(): int
    {
        return $this->data['render_count'] ?? 0;
    }

    public function getRenderTime(): float
    {
        return (float) ($this->data['render_time'] ?? 0);
    }

    private function collectDataFromLogger(): void
    {
        $components = [];
        $renders = [];
        $ongoingRenders = [];

        $classStubs = [];
        $templatePaths = [];

        foreach ($this->logger->getEvents() as [$event, $profile]) {
            if ($event instanceof PreRenderEvent) {
                $mountedComponent = $event->getMountedComponent();

                $metadata = $event->getMetadata();
                $componentName = $metadata->getName();
                $componentClass = $mountedComponent->getComponent()::class;

                $components[$componentName] ??= [
                    'name' => $componentName,
                    'class' => $componentClass,
                    'class_stub' => $classStubs[$componentClass] ??= ($this->hasStub ? new ClassStub($componentClass) : $componentClass),
                    'template' => $template = $metadata->getTemplate(),
                    'template_path' => $templatePaths[$template] ??= $this->resolveTemplatePath($template),
                    'render_count' => 0,
                    'render_time' => 0,
                ];

                $renderId = spl_object_id($mountedComponent);
                $renders[$renderId] = [
                    'name' => $componentName,
                    'class' => $componentClass,
                    'is_embed' => $event->isEmbedded(),
                    'input_props' => $mountedComponent->getInputProps(),
                    'attributes' => $mountedComponent->getAttributes()->all(),
                    'template_index' => $event->getTemplateIndex(),
                    'component' => $mountedComponent->getComponent(),
                    'depth' => \count($ongoingRenders),
                    'children' => [],
                    'render_start' => $profile[0],
                ];

                if ($parentId = end($ongoingRenders)) {
                    $renders[$parentId]['children'][] = $renderId;
                }

                $ongoingRenders[$renderId] = $renderId;
                continue;
            }

            if ($event instanceof PostRenderEvent) {
                $mountedComponent = $event->getMountedComponent();
                $componentName = $mountedComponent->getName();
                $renderId = spl_object_id($mountedComponent);

                $renderTime = ($profile[0] - $renders[$renderId]['render_start']) * 1000;
                $renders[$renderId] += [
                    'render_end' => $profile[0],
                    'render_time' => $renderTime,
                    'render_memory' => (int) $profile[1],
                ];

                ++$components[$componentName]['render_count'];
                $components[$componentName]['render_time'] += $renderTime;

                unset($ongoingRenders[$renderId]);
            }
        }

        // Sort by render count DESC
        uasort($components, fn ($a, $b) => $b['render_count'] <=> $a['render_count']);

        $this->data['components'] = $components;
        $this->data['component_count'] = \count($components);

        $this->data['renders'] = $renders;
        $this->data['render_count'] = \count($renders);
        $rootRenders = array_filter($renders, fn (array $r) => 0 === $r['depth']);
        $this->data['render_time'] = array_sum(array_column($rootRenders, 'render_time'));

        $this->data['peak_memory_usage'] = max([0, ...array_column($renders, 'render_memory')]);
    }

    private function resolveTemplatePath(string $logicalName): ?string
    {
        try {
            $source = $this->twig->getLoader()->getSourceContext($logicalName);
        } catch (LoaderError) {
            return null;
        }

        return $source->getPath();
    }
}
