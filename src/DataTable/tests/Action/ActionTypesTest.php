<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Action;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\UX\DataTable\Action\AbstractActionType;
use Symfony\UX\DataTable\Action\Action;
use Symfony\UX\DataTable\Action\ActionType;
use Symfony\UX\DataTable\Action\ActionTypeInterface;
use Symfony\UX\DataTable\Action\DropdownActionType;
use Symfony\UX\DataTable\Action\LinkActionType;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;
use Symfony\UX\DataTable\TypeRegistry;

final class ActionTypesTest extends TestCase
{
    public function testAction(): void
    {
        $view = self::action('archiveAll', ActionType::class)->createView();

        self::assertSame('archiveAll', $view->vars['id']);
        self::assertSame('archiveAll', $view->vars['name']);
        self::assertSame(['action'], $view->vars['block_prefixes']);
        self::assertNull($view->vars['row']);
        self::assertSame('Archive all', $view->vars['label']);
        self::assertNull($view->vars['icon']);
        self::assertSame([], $view->vars['attr']);
        self::assertTrue($view->vars['visible']);
        self::assertNull($view->vars['confirmation']);
        self::assertNull($view->vars['confirmation_translation_domain']);
    }

    public function testOptionsReceiveTheRow(): void
    {
        $product = new Product('Chair');
        $view = self::action('delete', ActionType::class, [
            'label' => 'Remove',
            'icon' => 'tabler:trash',
            'attr' => static fn (Product $product): array => ['title' => $product->name],
            'visible' => static fn (Product $product): bool => $product->active,
            'confirmation' => static fn (Product $product): string => 'Delete '.$product->name.'?',
        ])->createView($product, ['id' => 'products-0-delete', 'translation_domain' => 'admin']);

        self::assertSame('products-0-delete', $view->vars['id']);
        self::assertSame($product, $view->vars['row']);
        self::assertSame('Remove', $view->vars['label']);
        self::assertSame('tabler:trash', $view->vars['icon']);
        self::assertSame(['title' => 'Chair'], $view->vars['attr']);
        self::assertTrue($view->vars['visible']);
        self::assertSame('Delete Chair?', $view->vars['confirmation']);
        self::assertSame('admin', $view->vars['confirmation_translation_domain']);
    }

    public function testConfirmsWithTheDefaultMessage(): void
    {
        $view = self::action('delete', ActionType::class, ['confirmation' => true])->createView(null, ['translation_domain' => 'admin']);
        self::assertSame('Are you sure?', $view->vars['confirmation']);
        self::assertSame('UXDataTableBundle', $view->vars['confirmation_translation_domain']);

        self::assertNull(self::action('delete', ActionType::class, ['confirmation' => false])->createView()->vars['confirmation']);
    }

    public function testLink(): void
    {
        $view = self::action('show', LinkActionType::class, ['href' => '/products'])->createView();
        self::assertSame(['action', 'link_action'], $view->vars['block_prefixes']);
        self::assertSame('/products', $view->vars['href']);
        self::assertSame('GET', $view->vars['method']);
        self::assertNull($view->vars['csrf_token_id']);

        $view = self::action('delete', LinkActionType::class, [
            'href' => static fn (Product $product): string => '/products/'.$product->name,
            'method' => 'delete',
            'csrf_token_id' => static fn (Product $product): string => 'delete-'.$product->name,
        ])->createView(new Product('Chair'));
        self::assertSame('/products/Chair', $view->vars['href']);
        self::assertSame('DELETE', $view->vars['method']);
        self::assertSame('delete-Chair', $view->vars['csrf_token_id']);
    }

    public function testLinkRejectsOtherMethods(): void
    {
        $this->expectException(InvalidOptionsException::class);

        self::action('show', LinkActionType::class, ['href' => '/', 'method' => 'HEAD']);
    }

    public function testDropdownShowsItsVisibleActions(): void
    {
        $dropdown = self::action('more', DropdownActionType::class, ['actions' => [
            self::action('edit', LinkActionType::class, ['href' => '/edit']),
            self::action('restore', LinkActionType::class, ['href' => '/restore', 'visible' => static fn (Product $product): bool => !$product->active]),
        ]]);

        $view = $dropdown->createView(new Product('Chair'), ['id' => 'products-0-more', 'translation_domain' => 'admin']);
        self::assertTrue($view->vars['visible']);
        self::assertSame(['edit'], array_keys($view->vars['actions']));
        self::assertSame('products-0-more-edit', $view->vars['actions']['edit']->vars['id']);
        self::assertSame('admin', $view->vars['actions']['edit']->vars['translation_domain']);

        self::assertSame(['edit', 'restore'], array_keys($dropdown->createView(new Product('Chair', active: false))->vars['actions']));
    }

    public function testDropdownIsHiddenWithoutVisibleActions(): void
    {
        $dropdown = self::action('more', DropdownActionType::class, ['actions' => [
            self::action('edit', LinkActionType::class, ['href' => '/edit', 'visible' => false]),
        ]]);
        self::assertFalse($dropdown->createView()->vars['visible']);

        $dropdown = self::action('more', DropdownActionType::class, ['visible' => false, 'actions' => [
            self::action('edit', LinkActionType::class, ['href' => '/edit']),
        ]]);
        self::assertFalse($dropdown->createView()->vars['visible']);
    }

    public function testActionTypesInheritFromTheRootActionType(): void
    {
        $type = new class extends AbstractActionType {
        };
        [$types, $options] = new TypeRegistry()->resolve($type::class, ActionTypeInterface::class);

        self::assertSame(ActionType::class, $type->getParent());
        self::assertSame('Do it', new Action('doIt', $types, $options)->createView()->vars['label']);
        self::assertSame('dropdown_action', new DropdownActionType()->getBlockPrefix());
    }

    public function testGetters(): void
    {
        $action = self::action('show', LinkActionType::class, ['href' => '/']);

        self::assertSame('show', $action->getName());
        self::assertSame('/', $action->getOptions()['href']);
    }

    /**
     * @param class-string<ActionTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    private static function action(string $name, string $type, array $options = []): Action
    {
        [$types, $options] = new TypeRegistry()->resolve($type, ActionTypeInterface::class, $options);

        return new Action($name, $types, $options);
    }
}
