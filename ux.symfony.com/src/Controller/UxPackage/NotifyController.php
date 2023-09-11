<?php

namespace App\Controller\UxPackage;

use App\Form\SendNotificationForm;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Bridge\Mercure\MercureOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Annotation\Route;

class NotifyController extends AbstractController
{
    #[Route('/notify', name: 'app_notify')]
    public function __invoke(UxPackageRepository $packageRepository, Request $request, ChatterInterface $chatter): Response
    {
        $package = $packageRepository->find('notify');

        $form = $this->createForm(SendNotificationForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message = SendNotificationForm::getTextChoices()[$form->getData()['message']];

            // custom_mercure_chatter_transport is configured in config/packages/notifier.yaml
            $message = (new ChatMessage(
                $message,
                new MercureOptions(['/demo/notifier'])
            ))->transport('custom_mercure_chatter_transport');
            $chatter->send($message);

            return $this->redirectToRoute('app_notify');
        }

        return $this->render('ux_packages/notify.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }
}
