<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class _ChartController extends AbstractController
{
    #[Route('/chartjs', name: 'app_chartjs')]
    public function chartjs(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                ['label' => 'Cookies eaten 🍪', 'data' => [2, 10, 5, 18, 20, 30, 45]],
                ['label' => 'Km walked 🏃‍♀️', 'data' => [10, 15, 4, 3, 25, 41, 25]],
            ],
        ]);

        return $this->render('chart/chartjs.html.twig', [
            'chart' => $chart,
        ]);
    }
}
