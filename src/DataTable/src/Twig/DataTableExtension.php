<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Twig;

use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\DataTable\DataTableView;
use Symfony\UX\DataTable\Exception\LogicException;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;

/**
 * @internal
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DataTableExtension
{
    public function __construct(
        private readonly ?CsrfTokenManagerInterface $csrfTokenManager = null,
    ) {
    }

    /**
     * @param array<string, mixed> $variables overrides the variables of the view
     */
    #[AsTwigFunction('ux_data_table', needsEnvironment: true, isSafe: ['html'])]
    public function render(Environment $twig, DataTableView $dataTable, array $variables = []): string
    {
        /** @var string $theme the "theme" option */
        $theme = $dataTable->vars['theme'];

        return $twig->render($theme, ['data_table' => $dataTable] + $variables + $dataTable->vars);
    }

    /**
     * Renders an icon with symfony/ux-icons, or nothing when it is not installed.
     *
     * @param array<string, mixed> $attributes
     */
    #[AsTwigFunction('ux_data_table_icon', needsEnvironment: true, isSafe: ['html'])]
    public function renderIcon(Environment $twig, ?string $icon, array $attributes = []): string
    {
        if (null === $icon || null === $function = $twig->getFunction('ux_icon')) {
            return '';
        }

        $callable = $function->getCallable();
        if (\is_array($callable) && \is_string($callable[0])) {
            $callable[0] = $twig->getRuntime($callable[0]);
        }

        $html = \is_callable($callable) ? $callable($icon, $attributes) : '';

        return \is_string($html) ? $html : '';
    }

    #[AsTwigFunction('ux_data_table_csrf_token')]
    public function getCsrfToken(string $id): string
    {
        if (null === $this->csrfTokenManager) {
            throw new LogicException(\sprintf('Cannot generate the CSRF token "%s" of an action: install symfony/security-csrf and enable "framework.csrf_protection".', $id));
        }

        return $this->csrfTokenManager->getToken($id)->getValue();
    }
}
