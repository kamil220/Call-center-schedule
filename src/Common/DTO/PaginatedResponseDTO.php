<?php

declare(strict_types=1);

namespace App\Common\DTO;

final class PaginatedResponseDTO
{
    private array $items;
    private int $total;
    private int $page;
    private int $limit;
    private int $totalPages;

    public function __construct(array $items, int $total, int $page, int $limit)
    {
        $this->items = $items;
        $this->total = $total;
        $this->page = $page;
        $this->limit = $limit;
        $this->totalPages = $limit > 0 ? (int)ceil($total / $limit) : 0;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'page' => $this->page,
            'limit' => $this->limit,
            'totalPages' => $this->totalPages,
        ];
    }
} 