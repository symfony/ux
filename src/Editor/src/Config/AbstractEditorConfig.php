<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Config;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\UX\Editor\Exception\IncompatibleConfigException;

abstract class AbstractEditorConfig implements EditorConfigInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;
    private bool $strict = false;

    public function __construct(
        protected CommonOptions $common = new CommonOptions(),
        protected array $nativeOverrides = [],
    ) {
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public function getCommon(): CommonOptions
    {
        return $this->common;
    }

    public function getNativeOverrides(): array
    {
        return $this->nativeOverrides;
    }

    public function toNative(): array
    {
        $this->assertCapabilities();
        $base = $this->translateCommon($this->common);
        $own = $this->translateOwn();

        return array_replace_recursive($base, $own, $this->nativeOverrides);
    }

    abstract public function getBridgeId(): string;

    abstract public function getCapabilities(): BridgeCapabilities;

    abstract protected function translateCommon(CommonOptions $c): array;

    protected function translateOwn(): array
    {
        return [];
    }

    protected function assertCapabilities(): void
    {
        $cap = $this->getCapabilities();
        $issues = [];

        if (null !== $this->common->toolbar && !$cap->supportsToolbar) {
            $issues[] = 'toolbar';
        }
        if ([] !== $this->common->plugins && !$cap->supportsPlugins) {
            $issues[] = 'plugins';
        }
        if (null !== $this->common->theme && !$cap->supportsTheme) {
            $issues[] = 'theme';
        }
        if (null !== $this->common->language && !$cap->supportsLanguage) {
            $issues[] = 'language';
        }

        if ([] === $issues) {
            return;
        }

        $message = sprintf('Bridge "%s" does not support common option(s): %s', $this->getBridgeId(), implode(', ', $issues));

        if ($this->strict) {
            throw new IncompatibleConfigException($message);
        }

        $this->logger->warning($message);
    }
}
