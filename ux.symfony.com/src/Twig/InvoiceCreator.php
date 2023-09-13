<?php

namespace App\Twig;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class InvoiceCreator extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: ['customerName', 'customerEmail', 'taxRate'])]
    #[Valid]
    public Invoice $invoice;

    #[LiveProp]
    public array $lineItems = [];

    /**
     * A temporary flag that we just saved.
     *
     * This doesn't need to be a LiveProp because it's set in a LiveAction,
     * rendered immediately, then we want it to be forgotten.
     */
    public bool $savedSuccessfully = false;
    public bool $saveFailed = false;

    public function __construct(private ProductRepository $productRepository)
    {
    }

    // add mount method
    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        $this->lineItems = $this->populateLineItems($invoice);
    }

    #[LiveAction]
    public function addLineItem(): void
    {
        $this->lineItems[] = [
            'productId' => null,
            'quantity' => 1,
            'isEditing' => true,
        ];
    }

    #[LiveListener('removeLineItem')]
    public function removeLineItem(#[LiveArg] int $key): void
    {
        unset($this->lineItems[$key]);
    }

    #[LiveListener('line_item:change_edit_mode')]
    public function onLineItemEditModeChange(#[LiveArg] int $key, #[LiveArg] $isEditing): void
    {
        $this->lineItems[$key]['isEditing'] = $isEditing;
    }

    #[LiveListener('line_item:save')]
    public function saveLineItem(#[LiveArg] int $key, #[LiveArg] Product $product, #[LiveArg] int $quantity): void
    {
        if (!isset($this->lineItems[$key])) {
            // shouldn't happen
            return;
        }

        $this->lineItems[$key]['productId'] = $product->getId();
        $this->lineItems[$key]['quantity'] = $quantity;
    }

    #[LiveAction]
    public function saveInvoice(EntityManagerInterface $entityManager)
    {
        $this->saveFailed = true;
        $this->validate();
        $this->saveFailed = false;

        // TODO: do we check for `isSaved` here... and throw an error?

        // remove any items that no longer exist
        foreach ($this->invoice->getInvoiceItems() as $key => $item) {
            if (!isset($this->lineItems[$key])) {
                // orphanRemoval will cause these to be deleted
                $this->invoice->removeInvoiceItem($item);
            }
        }

        foreach ($this->lineItems as $key => $lineItem) {
            $invoiceItem = $this->invoice->getInvoiceItems()->get($key);
            if (null === $invoiceItem) {
                // this is a new item! Welcome!
                $invoiceItem = new InvoiceItem();
                $entityManager->persist($invoiceItem);
                $this->invoice->addInvoiceItem($invoiceItem);
            }

            $product = $this->findProduct($lineItem['productId']);
            $invoiceItem->setProduct($product);
            $invoiceItem->setQuantity($lineItem['quantity']);
        }

        $isNew = null === $this->invoice->getId();
        $entityManager->persist($this->invoice);
        $entityManager->flush();

        if ($isNew) {
            // it's new! Let's redirect to the edit page
            $this->addFlash('live_demo_success', 'Invoice saved!');

            return $this->redirectToRoute('app_live_components_invoice', [
                'id' => $this->invoice->getId(),
            ]);
        }

        // it's not new! We should already be on the edit page, so let's
        // just let the component stay rendered.
        $this->savedSuccessfully = true;

        // Keep the lineItems in sync with the invoice: new InvoiceItems may
        //      not have been given the same key as the original lineItems
        $this->lineItems = $this->populateLineItems($this->invoice);
    }

    public function getSubtotal(): float
    {
        $subTotal = 0;

        foreach ($this->lineItems as $lineItem) {
            if (!$lineItem['productId']) {
                continue;
            }

            $product = $this->findProduct($lineItem['productId']);

            $subTotal += ($product->getPrice() * $lineItem['quantity']);
        }

        return $subTotal / 100;
    }

    public function getTotal(): float
    {
        $taxMultiplier = 1 + ($this->invoice->getTaxRate() / 100);

        return $this->getSubtotal() * $taxMultiplier;
    }

    #[ExposeInTemplate]
    public function areAnyLineItemsEditing(): bool
    {
        foreach ($this->lineItems as $lineItem) {
            if ($lineItem['isEditing']) {
                return true;
            }
        }

        return false;
    }

    private function populateLineItems(Invoice $invoice): array
    {
        $lineItems = [];
        foreach ($invoice->getInvoiceItems() as $item) {
            $lineItems[] = [
                'productId' => $item->getProduct()->getId(),
                'quantity' => $item->getQuantity(),
                'isEditing' => false,
            ];
        }

        return $lineItems;
    }

    private function findProduct(int $id): Product
    {
        return $this->productRepository->find($id);
    }
}
