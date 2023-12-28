<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ProductFixtureEntity;

class HoldsArrayCollectionAndEntity
{
    public ?ProductFixtureEntity $product = null;
    public ArrayCollection $productList;

    public function __construct()
    {
        $this->productList = new ArrayCollection();
    }

    public function getProduct(): ?ProductFixtureEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductFixtureEntity $product): void
    {
        $this->product = $product;
    }

    public function getProductList(): ArrayCollection
    {
        return $this->productList;
    }

    public function setProductList(ArrayCollection $productList): void
    {
        $this->productList = $productList;
    }
}
