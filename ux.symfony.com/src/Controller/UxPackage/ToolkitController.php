<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\UxPackage;

use App\Enum\ToolkitKit;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ToolkitController extends AbstractController
{
    #[Route('/toolkit', name: 'app_toolkit')]
    public function index(UxPackageRepository $packageRepository, UriSigner $uriSigner): Response
    {
        $package = $packageRepository->find('toolkit');
        $demoPreviewHeight = '400px';
        $demoPreviewUrl = $uriSigner->sign($this->generateUrl('app_toolkit_component_preview', [
            'toolkitKit' => ToolkitKit::Shadcn->value,
            'code' => <<<'TWIG'
                <twig:Card>
                    <twig:Card:Header>
                        <twig:Card:Title>Symfony is cool</twig:Card:Title>
                        <twig:Card:Description>
                            Symfony is a set of reusable PHP components...
                        </twig:Card:Description>
                    </twig:Card:Header>
                    <twig:Card:Content>
                        ... and a PHP framework for web projects
                    </twig:Card:Content>
                    <twig:Card:Footer>
                        <twig:Button as="a" href="https://symfony.com">
                            Visit symfony.com
                        </twig:Button>
                    </twig:Card:Footer>
                </twig:Card>
                TWIG,
            'height' => $demoPreviewHeight,
        ], UrlGeneratorInterface::ABSOLUTE_URL));

        return $this->render('ux_packages/toolkit.html.twig', [
            'package' => $package,
            'kits' => ToolkitKit::cases(),
            'demoPreviewUrl' => $demoPreviewUrl,
            'demoPreviewHeight' => $demoPreviewHeight,
        ]);
    }
}
