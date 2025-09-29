<?php

use TwigCsFixer\Rules\Delimiter\BlockNameSpacingRule;
use TwigCsFixer\Rules\File\DirectoryNameRule;
use TwigCsFixer\Rules\File\FileExtensionRule;
use TwigCsFixer\Rules\File\FileNameRule;
use TwigCsFixer\Rules\Function\IncludeFunctionRule;
use TwigCsFixer\Rules\Punctuation\TrailingCommaSingleLineRule;
use TwigCsFixer\Rules\Literal\SingleQuoteRule;
use TwigCsFixer\Rules\Variable\VariableNameRule;
use TwigCsFixer\Rules\Whitespace\EmptyLinesRule;
use TwigCsFixer\Rules\Whitespace\TrailingSpaceRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Rules\Whitespace\BlankEOFRule;
use TwigCsFixer\Standard\Twig;

$uxRuleset = (new Ruleset())
    ->addStandard(new Twig())
    // Symfony standard (customized for Symfony UX)
    ->addRule(new DirectoryNameRule(baseDirectory: 'templates', ignoredSubDirectories: ['bundles', 'components', 'demos/live_memory/components']))
    ->addRule(new FileNameRule(baseDirectory: 'templates', ignoredSubDirectories: ['bundles', 'components', 'demos/live_memory/components'], optionalPrefix: '_'))
    ->addRule(new DirectoryNameRule(case: DirectoryNameRule::PASCAL_CASE, baseDirectory: 'templates/components'))
    ->addRule(new FileNameRule(case: DirectoryNameRule::PASCAL_CASE, baseDirectory: 'templates/components'))
    ->addRule(new DirectoryNameRule(case: DirectoryNameRule::PASCAL_CASE, baseDirectory: 'demos/live_memory/components'))
    ->addRule(new FileNameRule(case: DirectoryNameRule::PASCAL_CASE, baseDirectory: 'demos/live_memory/components'))
    ->addRule(new FileExtensionRule())
    // TwigCsFixer standard (customized for Symfony UX)
    ->addRule(new BlankEOFRule())
    ->addRule(new BlockNameSpacingRule())
    ->addRule(new EmptyLinesRule())
    ->addRule(new IncludeFunctionRule())
    ->addRule(new SingleQuoteRule())
    ->addRule(new TrailingSpaceRule())
    ->addRule(new TrailingCommaSingleLineRule())
    ->overrideRule(new VariableNameRule(VariableNameRule::SNAKE_CASE, '_'))
;

$config = (new TwigCsFixer\Config\Config('Symfony UX'))
    ->setRuleset($uxRuleset)
    ->allowNonFixableRules()
    ->setCacheFile(null)
;

return $config;
