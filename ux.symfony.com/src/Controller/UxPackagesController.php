<?php

namespace App\Controller;

use App\Entity\Food;
use App\Form\DropzoneForm;
use App\Form\TimeForAMealForm;
use App\Service\PackageRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UxPackagesController extends AbstractController
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private Packages $assetPackages
    ) {
    }

    #[Route('/twig-component', name: 'app_twig_component')]
    public function twigComponent(): Response
    {
        return $this->render('ux_packages/twig-component.html.twig');
    }

    #[Route('/lazy-image', name: 'app_lazy_image')]
    public function lazyImage(): Response
    {
        $legosFilePath = $this->getParameter('kernel.project_dir').'/assets/images/legos.jpg';

        return $this->render('ux_packages/lazy-image.html.twig', [
            'legosFilePath' => $legosFilePath,
        ]);
    }

    #[Route('/react', name: 'app_react')]
    public function react(PackageRepository $packageRepository): Response
    {
        $packagesData = $this->getNormalizedPackages($packageRepository);

        return $this->render('ux_packages/react.html.twig', [
            'packagesData' => $packagesData,
        ]);
    }

    #[Route('/vue', name: 'app_vue')]
    public function vue(PackageRepository $packageRepository): Response
    {
        $packagesData = $this->getNormalizedPackages($packageRepository);

        return $this->render('ux_packages/vue.html.twig', [
            'packagesData' => $packagesData,
        ]);
    }

    #[Route('/svelte', name: 'app_svelte')]
    public function svelte(PackageRepository $packageRepository): Response
    {
        $packagesData = $this->getNormalizedPackages($packageRepository);

        return $this->render('ux_packages/svelte.html.twig', [
            'packagesData' => $packagesData,
        ]);
    }

    #[Route('/typed', name: 'app_typed')]
    public function typed(): Response
    {
        return $this->render('ux_packages/typed.html.twig');
    }

    #[Route('/autocomplete', name: 'app_autocomplete')]
    public function autocomplete(Request $request): Response
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

        return $this->render('ux_packages/autocomplete.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/dropzone', name: 'app_dropzone')]
    public function dropzone(Request $request): Response
    {
        $form = $this->createForm(DropzoneForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('dropzone_success', 'File uploaded! Then immediately discarded... since this is a demo server.');

            return $this->redirectToRoute('app_dropzone');
        }

        return $this->render('ux_packages/dropzone.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/translator', name: 'app_translator')]
    public function translator(): Response
    {
        return $this->render('ux_packages/translator.html.twig');
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

    private function getNormalizedPackages(PackageRepository $packageRepository): array
    {
        $packagesData = $this->normalizer->normalize($packageRepository->findAll());
        $assetPackages = $this->assetPackages;

        return array_map(function (array $data) use ($assetPackages) {
            $data['url'] = $this->generateUrl($data['route']);
            unset($data['route']);
            $data['imageUrl'] = $assetPackages->getUrl('images/'.$data['imageFilename']);

            return $data;
        }, $packagesData);
    }
}
