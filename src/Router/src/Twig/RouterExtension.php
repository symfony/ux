<?php

namespace Symfony\UX\Router\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RouterExtension extends AbstractExtension
{
    public function __construct(
        private RequestContext $requestContext,
    )
    {

    }

    public function getFunctions(): iterable
    {
        yield new TwigFunction('render_ux_router_configuration', $this->renderConfiguration(...), [
            'needs_environment' => true,
            'is_safe' => ['html'],
        ]);
    }

    private function renderConfiguration(\Twig\Environment $twig): string
    {
        return sprintf('data-symfony-ux-router-configuration="%s"', twig_escape_filter($twig, json_encode([
            'context' => [
                'base_url' => $this->requestContext->getBaseUrl(),
                'host' => $this->requestContext->getHost(),
                'scheme' => $this->requestContext->getScheme(),
                'http_port' => $this->requestContext->getHttpPort(),
                'https_port' => $this->requestContext->getHttpsPort(),
                'locale' => $this->requestContext->getParameter('_locale'),
            ]
        ], JSON_THROW_ON_ERROR)));
    }
}