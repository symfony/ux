<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Chartjs\Model;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class Chart
{
    public const TYPE_LINE = 'line';
    public const TYPE_BAR = 'bar';
    public const TYPE_RADAR = 'radar';
    public const TYPE_PIE = 'pie';
    public const TYPE_DOUGHNUT = 'doughnut';
    public const TYPE_POLAR_AREA = 'polarArea';
    public const TYPE_BUBBLE = 'bubble';
    public const TYPE_SCATTER = 'scatter';

    private $type;
    private $data = [];
    private $options = [];
    private $attributes = [];

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets Chart.js options.
     *
     * @see https://www.chartjs.org/docs/2.9.4
     *
     * <code>
     *    $chart->setOptions([
     *        'scales' => [
     *            'yAxes' => [
     *                ['ticks' => ['min' => 0, 'max' => 100]],
     *            ]
     *        ]
     *    ]);
     * </code>
     *
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function createView(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'options' => $this->options,
        ];
    }

    public function getDataController(): ?string
    {
        return $this->attributes['data-controller'] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
