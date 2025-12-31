<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());

$finder = new TwigCsFixer\File\Finder();

// Some directories may not exist when running Fabbot action, if no files under them were changed.
$finder->in(array_filter([__DIR__.'/src', __DIR__.'/apps'], is_dir(...)));
$finder->notPath('#/Fixtures/#');
$finder->notPath('#/assets/#');
$finder->notPath('#/var/#');
// apps/
$finder->notPath(['#config/#', '#public/#', 'importmap.php'])

$config = new TwigCsFixer\Config\Config();
$config->setCacheFile('.twig-cs-fixer.cache');
$config->setRuleset($ruleset);
$config->setFinder($finder);

return $config;
