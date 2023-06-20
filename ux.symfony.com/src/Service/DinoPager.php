<?php

namespace App\Service;

use function Symfony\Component\String\u;

class DinoPager
{
    private int $elementsPerPage;
    private int $currentPage;
    private int $totalElements;
    private int $numberPages;

    private ?array $rawData = null;

    private ?string $nameFilter = null;
    private ?string $typeFilter = null;

    public function __construct()
    {
        $data = $this->getRawData();

        $this->elementsPerPage = 10;
        $this->totalElements = count($data);
        $this->numberPages = ceil($this->totalElements / $this->elementsPerPage);
        $this->currentPage = 1;
    }

    private function getRawData(): array
    {
        if (null === $this->rawData) {
            $this->rawData = json_decode(file_get_contents(__DIR__.'/data/dino-stats.json'), true);
        }

        return $this->rawData;
    }

    public function getCurrentPageResults(): array
    {
        $data = $this->applyFilters();
        $this->numberPages = ceil($this->totalElements / $this->elementsPerPage);
        $offset = ($this->currentPage - 1) * $this->elementsPerPage;

        return array_slice($data, $offset, $this->elementsPerPage);

    }

    public function getNumberPages(): int
    {
        return $this->numberPages;
    }

    public function getTotalElements(): int
    {
        $this->applyFilters();
        return $this->totalElements;
    }

    public function setCurrentPage(int $page): void
    {
        $this->currentPage = $page;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function filter(?string $name, ?string $type): void
    {
        if ($name) {
            $this->nameFilter = $name;
        }

        if ($type) {
            $this->typeFilter = $type;
        }
    }

    /**
     * @return array|null
     */
    private function applyFilters(): ?array
    {
        $data = array_filter($this->rawData, function (array $dino) {
            if (null !== $this->nameFilter) {
                return u($dino['name'])->containsAny($this->nameFilter);
            }

            return true;
        });

        $data = array_filter($data, function (array $dino) {
            if (null !== $this->typeFilter) {
                return u($dino['type'])->containsAny($this->typeFilter);
            }

            return true;
        });

        $this->totalElements = count($data);

        return $data;
    }
}
