<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartjsController extends AbstractController
{
    #[Route('/chartjs', name: 'app_chartjs')]
    public function __invoke(UxPackageRepository $packageRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $package = $packageRepository->find('chartjs');

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'Cookies eaten 🍪',
                    'backgroundColor' => 'rgb(255, 99, 132, .4)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [2, 10, 5, 18, 20, 30, 45],
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Km walked 🏃‍♀️',
                    'backgroundColor' => 'rgba(45, 220, 126, .4)',
                    'borderColor' => 'rgba(45, 220, 126)',
                    'data' => [10, 15, 4, 3, 25, 41, 25],
                    'tension' => 0.4,
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
        ]);

        return $this->render('ux_packages/chartjs.html.twig', [
            'package' => $package,
            'chart' => $chart,
        ]);
    }
}
