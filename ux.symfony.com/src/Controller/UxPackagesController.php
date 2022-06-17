<?php

namespace App\Controller;

use App\Entity\Food;
use App\Form\DropzoneForm;
use App\Form\SendNotificationForm;
use App\Form\TimeForAMealForm;
use App\Service\PackageRepository;
use Doctrine\Common\Collections\Collection;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

class UxPackagesController extends AbstractController
{
    #[Route('/chartjs', name: 'app_chartjs')]
    public function chartjs(ChartBuilderInterface $chartBuilder, PackageRepository $packageRepository): Response
    {
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
            'chart' => $chart,
            'package' => $packageRepository->find('chartjs'),
        ]);
    }

    #[Route('/twig-component', name: 'app_twig_component')]
    public function twigComponent(PackageRepository $packageRepository): Response
    {
        return $this->render('ux_packages/twig-component.html.twig', [
            'package' => $packageRepository->find('twig-component'),
        ]);
    }

    #[Route('/lazy-image', name: 'app_lazy_image')]
    public function lazyImage(PackageRepository $packageRepository): Response
    {
        return $this->render('ux_packages/lazy-image.html.twig', [
            'package' => $packageRepository->find('lazy-image'),
            'publicDir' => $this->getParameter('kernel.project_dir').'/public',
        ]);
    }

    #[Route('/notify', name: 'app_notify')]
    public function notify(PackageRepository $packageRepository, Request $request, NotifierInterface $notifier): Response
    {
        $form = $this->createForm(SendNotificationForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = SendNotificationForm::getTextChoices()[$form->getData()['message']];

            // custom_mercure_chatter_transport is configured in notifier.yaml
            $notification = new Notification($message, ['chat/custom_mercure_chatter_transport']);
            $notifier->send($notification);

            return $this->redirectToRoute('app_notify');
        }

        return $this->renderForm('ux_packages/notify.html.twig', [
            'package' => $packageRepository->find('notify'),
            'form' => $form,
        ]);
    }

    #[Route('/react', name: 'app_react')]
    public function react(PackageRepository $packageRepository, NormalizerInterface $normalizer, Packages $assetPackages): Response
    {
        // turn the array of Package into an array, with some extra keys
        $packagesData = $normalizer->normalize($packageRepository->findAll());
        $packagesData = array_map(function (array $data) use ($assetPackages) {
            $data['url'] = $this->generateUrl($data['route']);
            unset($data['route']);
            $data['imageUrl'] = $assetPackages->getUrl('build/images/'.$data['imageFilename']);

            return $data;
        }, $packagesData);

        return $this->render('ux_packages/react.html.twig', [
            'package' => $packageRepository->find('react'),
            'packagesData' => $packagesData,
        ]);
    }

    #[Route('/typed', name: 'app_typed')]
    public function typed(PackageRepository $packageRepository): Response
    {
        return $this->render('ux_packages/typed.html.twig', [
            'package' => $packageRepository->find('typed'),
        ]);
    }

    #[Route('/autocomplete', name: 'app_autocomplete')]
    public function autocomplete(PackageRepository $packageRepository, Request $request): Response
    {
        $form = $this->createForm(TimeForAMealForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->addFlash(
                'autocomplete_success',
                $this->generateEatingMessage(
                    $data['foods'],
                    $data['name']
                )
            );

            return $this->redirectToRoute('app_autocomplete');
        }

        return $this->renderForm('ux_packages/autocomplete.html.twig', [
            'package' => $packageRepository->find('autocomplete'),
            'form' => $form,
        ]);
    }

    #[Route('/cropperjs', name: 'app_cropper')]
    public function cropper(Packages $package, CropperInterface $cropper, Request $request, string $projectDir, PackageRepository $packageRepository): Response
    {
        $crop = $cropper->createCrop($projectDir.'/assets/images/large.jpg');
        $crop->setCroppedMaxSize(1000, 750);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => $package->getUrl('/build/images/large.jpg'),
                'cropper_options' => [
                    'aspectRatio' => 4 / 3,
                    'preview' => '#cropper-preview',
                    'scalable' => false,
                    'zoomable' => false,
                ],
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        $croppedImage = null;
        $croppedThumbnail = null;
        if ($form->isSubmitted()) {
            // faking an error to let the page re-render with the cropped images
            $form->addError(new FormError('🤩'));
            $croppedImage = sprintf('data:image/jpeg;base64,%s', base64_encode($crop->getCroppedImage()));
            $croppedThumbnail = sprintf('data:image/jpeg;base64,%s', base64_encode($crop->getCroppedThumbnail(200, 150)));
        }

        return $this->renderForm('ux_packages/cropperjs.html.twig', [
            'form' => $form,
            'croppedImage' => $croppedImage,
            'croppedThumbnail' => $croppedThumbnail,
            'package' => $packageRepository->find('cropperjs'),
        ]);
    }

    #[Route('/dropzone', name: 'app_dropzone')]
    public function dropzone(Request $request, PackageRepository $packageRepository): Response
    {
        $form = $this->createForm(DropzoneForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('dropzone_success', 'File uploaded! Then immediately discarded... since this is a demo server.');

            return $this->redirectToRoute('app_dropzone');
        }

        return $this->renderForm('ux_packages/dropzone.html.twig', [
            'form' => $form,
            'package' => $packageRepository->find('dropzone'),
        ]);
    }

    #[Route('/swup/{page<\d+>}', name: 'app_swup')]
    public function swup(PackageRepository $packageRepository, int $page = 1): Response
    {
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new ArrayAdapter($packageRepository->findAll()),
            $page,
            4
        );

        return $this->render('ux_packages/swup.html.twig', [
            'package' => $packageRepository->find('swup'),
            'pager' => $pagerfanta,
        ]);
    }

    private function getDeliciousWord(): string
    {
        $words = ['delicious', 'scrumptious', 'mouth-watering', 'life-changing', 'world-beating', 'freshly-squeezed'];

        return $words[array_rand($words)];
    }

    private function generateEatingMessage(Collection $foods, string $name): string
    {
        $i = 0;
        $foodStrings = $foods->map(function (Food $food) use (&$i, $foods) {
            ++$i;
            $str = $food->getName();

            if ($i === \count($foods) && $i > 1) {
                $str = 'and '.$str;
            }

            return $str;
        });

        return sprintf('Time for %s! Enjoy %s %s %s!',
            $name,
            \count($foodStrings) > 1 ? 'some' : 'a',
            $this->getDeliciousWord(),
            implode(\count($foodStrings) > 2 ? ', ' : ' ', $foodStrings->toArray())
        );
    }
}
