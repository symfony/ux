<?php

namespace App\Twig;

use App\Service\DinoStatsService;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class DinoChart
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public array $currentTypes = ['all', 'large theropod', 'small theropod'];

    #[LiveProp(writable: true)]
    public int $fromYear = -200;
    #[LiveProp(writable: true)]
    public int $toYear = -65;

    public function __construct(
        private DinoStatsService $dinoStatsService,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    #[ExposeInTemplate]
    public function getChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData($this->dinoStatsService->fetchData(
            $this->fromYear,
            $this->toYear,
            $this->currentTypes
        ));

        $chart->setOptions([
            // set title plugin
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => sprintf(
                        'Dinos species count from %dmya to %dmya',
                        abs($this->fromYear),
                        abs($this->toYear)
                    ),
                ],
            ],
            'maintainAspectRatio' => false,
        ]);

        return $chart;
    }

    #[ExposeInTemplate]
    public function allTypes(): array
    {
        return DinoStatsService::getAllTypes();
    }
}
