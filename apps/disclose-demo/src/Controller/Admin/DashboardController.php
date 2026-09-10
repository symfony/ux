<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\Client;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('UX Disclose demo');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', Client::class);
    }

    /**
     * Registers the "app" AssetMapper entrypoint so the disclose Stimulus
     * controller (loaded through assets/app.js) is available on every admin page.
     *
     * EasyAdmin renders the entrypoint in its native "importmap" block, so no
     * layout template override is needed (overriding @EasyAdmin/layout.html.twig
     * would extend the override itself and recurse infinitely).
     */
    public function configureAssets(): Assets
    {
        return Assets::new()->addAssetMapperEntry('app');
    }
}
