<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Installer\StimulusIdentifierRewriter;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class StimulusIdentifierRewriterTest extends KernelTestCase
{
    use TestHelperTrait;

    private const IDENTIFIERS = [
        'calendar' => ['month-change', 'select'],
        'context-menu' => ['close'],
        'context-menu-sub' => [],
        'dialog' => [],
        'popover' => [],
        'tabs' => [],
        'toggle' => [],
    ];

    public function testPrefixesTheControllersOfTheKitInTemplates(): void
    {
        $rewriter = new StimulusIdentifierRewriter(self::IDENTIFIERS, 'ui--');

        $this->assertSame(<<<'TWIG'
            <div
                data-controller="ui--dialog live"
                data-ui--dialog-open-value="{{ open ? 'true' : 'false' }}"
                data-ui--tabs-active-class="is-active"
            >
                <button
                    data-ui--dialog-target="trigger"
                    data-action="click->ui--dialog#open keydown.esc@window->ui--dialog#close:prevent ui--dialog#focus"
                >Open</button>
                <div
                    data-ui--context-menu-sub-target="content"
                    data-ui--context-menu-sub-open-value="true"
                    data-ui--context-menu-target="item"
                ></div>
                <div
                    data-action="ui--calendar:select->date-picker#onSelect ui--context-menu:close->ui--tabs#reset"
                    data-ui--calendar-date-param="2026-10-24"
                ></div>
                <div data-action="popover:open@window->ui--popover#handleGroupOpen live#update"></div>
                <div data-action="ui--calendar:select@window->ui--dialog#open calendar:open->ui--dialog#open"></div>
            </div>
            {%- set dialog_trigger_attrs = {
                'data-action': 'click->ui--dialog#open'|html_attr_type('sst'),
                'data-ui--dialog-target': 'trigger',
            } -%}
            <div {{ attributes.defaults({'data-controller': 'ui--tabs', 'data-action': 'click->ui--tabs#open'}) }}></div>
            <div
                {{ stimulus_controller('ui--dialog') }}
                {{ stimulus_target('ui--dialog', 'content') }}
                {{ stimulus_action('ui--tabs', 'open') }}
            ></div>
            TWIG, $rewriter->rewrite(<<<'TWIG'
            <div
                data-controller="dialog live"
                data-dialog-open-value="{{ open ? 'true' : 'false' }}"
                data-tabs-active-class="is-active"
            >
                <button
                    data-dialog-target="trigger"
                    data-action="click->dialog#open keydown.esc@window->dialog#close:prevent dialog#focus"
                >Open</button>
                <div
                    data-context-menu-sub-target="content"
                    data-context-menu-sub-open-value="true"
                    data-context-menu-target="item"
                ></div>
                <div
                    data-action="calendar:select->date-picker#onSelect context-menu:close->tabs#reset"
                    data-calendar-date-param="2026-10-24"
                ></div>
                <div data-action="popover:open@window->popover#handleGroupOpen live#update"></div>
                <div data-action="calendar:select@window->dialog#open calendar:open->dialog#open"></div>
            </div>
            {%- set dialog_trigger_attrs = {
                'data-action': 'click->dialog#open'|html_attr_type('sst'),
                'data-dialog-target': 'trigger',
            } -%}
            <div {{ attributes.defaults({'data-controller': 'tabs', 'data-action': 'click->tabs#open'}) }}></div>
            <div
                {{ stimulus_controller('dialog') }}
                {{ stimulus_target('dialog', 'content') }}
                {{ stimulus_action('tabs', 'open') }}
            ></div>
            TWIG));
    }

    public function testPrefixesTheControllersTheKitInstallsThroughThePlaceholderAndTheEventsTheyDispatch(): void
    {
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');
        $kit = $kitFactory->createKitFromAbsolutePath(self::getFixtureKitPath('with-installation-placeholders'));
        $contents = <<<'TWIG'
            <div data-controller="dialog notice" data-notice-target="message"></div>
            <div data-action="dialog:open->app#log dialog:close->app#log dialog:notified->app#log notice:show->app#log"></div>
            TWIG;

        $this->assertSame(<<<'TWIG'
            <div data-controller="ui--dialog notice" data-notice-target="message"></div>
            <div data-action="ui--dialog:open->app#log ui--dialog:close->app#log dialog:notified->app#log notice:show->app#log"></div>
            TWIG, StimulusIdentifierRewriter::forKit($kit, 'ui--')->rewrite($contents));
        $this->assertSame($contents, StimulusIdentifierRewriter::forKit($kit, '')->rewrite($contents));
    }

    public function testPrefixesTheControllersOfTheKitInControllers(): void
    {
        $rewriter = new StimulusIdentifierRewriter(self::IDENTIFIERS, 'ui--');

        $this->assertSame(<<<'JS'
            const toggles = this.element.querySelectorAll('[data-controller~="ui--toggle"]');
            const calendar = this.application.getControllerForElementAndIdentifier(element, 'ui--calendar');
            const trigger = item.querySelector('[data-ui--tabs-target="trigger"]');
            this.element.setAttribute('data-controller', 'ui--dialog');
            trigger.setAttribute('data-action', 'click->ui--dialog#open');
            JS, $rewriter->rewrite(<<<'JS'
            const toggles = this.element.querySelectorAll('[data-controller~="toggle"]');
            const calendar = this.application.getControllerForElementAndIdentifier(element, 'calendar');
            const trigger = item.querySelector('[data-tabs-target="trigger"]');
            this.element.setAttribute('data-controller', 'dialog');
            trigger.setAttribute('data-action', 'click->dialog#open');
            JS));
    }

    public function testPrefixesOverlappingIdentifiersInMultiLineValues(): void
    {
        $rewriter = new StimulusIdentifierRewriter(self::IDENTIFIERS, 'ui--');

        $this->assertSame(<<<'TWIG'
            <div
                data-controller="ui--context-menu ui--context-menu-sub"
                data-action="
                    contextmenu->ui--context-menu#open
                    keydown.enter@document->ui--context-menu-sub#select:prevent:stop
                "
            >
                <button data-action="{{ disabled ? '' : 'click->ui--dialog#open' }}"></button>
            </div>
            const menu = this.element.closest('[data-controller="ui--context-menu"]');
            TWIG, $rewriter->rewrite(<<<'TWIG'
            <div
                data-controller="context-menu context-menu-sub"
                data-action="
                    contextmenu->context-menu#open
                    keydown.enter@document->context-menu-sub#select:prevent:stop
                "
            >
                <button data-action="{{ disabled ? '' : 'click->dialog#open' }}"></button>
            </div>
            const menu = this.element.closest('[data-controller="context-menu"]');
            TWIG));
    }

    public function testLeavesEverythingElseUntouched(): void
    {
        $rewriter = new StimulusIdentifierRewriter(self::IDENTIFIERS, 'ui--');

        $contents = <<<'TWIG'
            <dialog
                data-slot="dialog-content"
                data-state="open"
                data-dialog="modal"
                class="group-data-[state=open]/popover:block group/tabs-list"
            >
                <div data-tabs-list="true" data-calendar-day="1"></div>
                <div data-action="calendar:open->dialog-like#show"></div>
            </dialog>
            window.dispatchEvent(new CustomEvent('popover:open', { detail: { id } }));
            cookieName: { type: String, default: 'sidebar:state' },
            if (event.type === 'toggle') {}
            TWIG;

        $this->assertSame($contents, $rewriter->rewrite($contents));
    }

    public function testDoesNothingWithoutPrefix(): void
    {
        $contents = '<div data-controller="dialog" data-dialog-target="content"></div>';

        $this->assertSame($contents, new StimulusIdentifierRewriter(self::IDENTIFIERS, '')->rewrite($contents));
    }
}
