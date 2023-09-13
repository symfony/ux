<?php

namespace App\Twig;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
class InvoiceCreatorLineItem
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public int $key;

    #[LiveProp(writable: true)]
    #[Assert\NotNull]
    public ?Product $product = null;

    #[LiveProp(writable: true)]
    #[Assert\Positive]
    public int $quantity = 1;

    #[LiveProp]
    public bool $isEditing = false;

    public function __construct(private ProductRepository $productRepository)
    {
    }

    public function mount(?int $productId): void
    {
        if ($productId) {
            $this->product = $this->productRepository->find($productId);
        }
    }

    #[LiveAction]
    public function save(LiveResponder $responder): void
    {
        $this->validate();

        $responder->emitUp('line_item:save', [
            'key' => $this->key,
            'product' => $this->product->getId(),
            'quantity' => $this->quantity,
        ]);

        $this->changeEditMode(false, $responder);
    }

    #[LiveAction]
    public function edit(LiveResponder $responder): void
    {
        $this->changeEditMode(true, $responder);
    }

    #[ExposeInTemplate]
    public function getProducts(): array
    {
        return $this->productRepository->findAll();
    }

    private function changeEditMode(bool $isEditing, LiveResponder $responder): void
    {
        $this->isEditing = $isEditing;

        // emit to InvoiceCreator so it can track which items are being edited
        $responder->emitUp('line_item:change_edit_mode', [
            'key' => $this->key,
            'isEditing' => $this->isEditing,
        ]);
    }
}
