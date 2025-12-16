<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/ux-chartjs', name: 'app_ux_chartjs_')]
final class ChartjsController extends AbstractController
{
    #[Route('/without-options', name: 'without_options')]
    public function withoutOptions(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'My First dataset',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
                ],
            ],
        ]);

        return $this->render('ux_chartjs/index.html.twig', [
            'chart' => $chart,
        ]);
    }

    #[Route('/with-options', name: 'with_options')]
    public function withOptions(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'My First dataset',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
                ],
            ],
        ]);

        $chart->setOptions([
            'showLines' => false,
        ]);

        return $this->render('ux_chartjs/index.html.twig', [
            'chart' => $chart,
        ]);
    }

    #[Route('/pie', name: 'pie')]
    public function pie(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setData([
            'labels' => ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
            'datasets' => [
                [
                    'label' => 'My First Dataset',
                    'data' => [12, 19, 3, 5, 2, 3],
                    'backgroundColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)',
                        'rgb(255, 159, 64)',
                    ],
                ],
            ],
        ]);

        return $this->render('ux_chartjs/index.html.twig', [
            'chart' => $chart,
        ]);
    }

    #[Route('/pie-with-options', name: 'pie_with_options')]
    public function pieWithOptions(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setData([
            'labels' => ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
            'datasets' => [
                [
                    'label' => 'My First Dataset',
                    'data' => [12, 19, 3, 5, 2, 3],
                    'backgroundColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)',
                        'rgb(255, 159, 64)',
                    ],
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ]);

        return $this->render('ux_chartjs/index.html.twig', [
            'chart' => $chart,
        ]);
    }
}
