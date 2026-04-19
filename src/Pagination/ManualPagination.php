<?php
// src/Pagination/ManualPagination.php

namespace App\Pagination;

/**
 * Drop-in replacement for KnpPaginator's SlidingPagination.
 * Exposes the same properties the Twig template uses:
 *   courses.currentPageNumber
 *   courses.pageCount
 *   courses.totalItemCount
 * and is iterable so {% for cours in courses %} works normally.
 */
class ManualPagination implements \IteratorAggregate, \Countable
{
    private array $items;
    private int   $currentPageNumber;
    private int   $pageCount;
    private int   $totalItemCount;

    public function __construct(
        array $items,
        int   $currentPageNumber,
        int   $pageCount,
        int   $totalItemCount
    ) {
        $this->items             = $items;
        $this->currentPageNumber = $currentPageNumber;
        $this->pageCount         = $pageCount;
        $this->totalItemCount    = $totalItemCount;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getCurrentPageNumber(): int { return $this->currentPageNumber; }
    public function getPageCount(): int         { return $this->pageCount; }
    public function getTotalItemCount(): int     { return $this->totalItemCount; }
}