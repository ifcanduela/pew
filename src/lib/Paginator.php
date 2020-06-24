<?php

namespace pew\lib;

use pew\model\Table;

/**
 * Paginates database results.
 */
class Paginator
{
    /** @var Table */
    public $finder;

    /** @var int */
    public $itemsPerPage = 15;

    /** @var int */
    public $resultCount = 0;

    /** @var array */
    public $items = [];

    /** @var int */
    public $page = 1;

    /** @var int */
    public $firstPage = 1;

    /** @var int */
    public $lastPage = 1;

    /**
     * Create and initialize a paginator.
     *
     * @param Table $finder
     * @param int $itemsPerPage
     * @param int $page
     */
    public function __construct(Table $finder, int $itemsPerPage = 15, int $page = 1)
    {
        $this->finder = $finder;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;

        $this->getPage();
    }

    /**
     * Get the items in the current page.
     *
     * @param int|null $page
     * @param int|null $itemsPerPage
     * @return array
     */
    public function getPageItems(int $page = null, int $itemsPerPage = null)
    {
        $page = $page ?? $this->page;
        $itemsPerPage = $itemsPerPage ?? $this->itemsPerPage;

        $this->resultCount = $this->finder->count();

        $this->pageCount = ceil($this->resultCount / $itemsPerPage);
        $this->firstPage = 1;
        $this->lastPage = $this->pageCount;

        $items = $this->finder
            ->limit($itemsPerPage)
            ->offset(($page - 1) * $itemsPerPage)
            ->all();

        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->items = $items;

        return $this->items;
    }

    /**
     * Set the current page number.
     *
     * @param int $page [description]
     * @return self
     */
    public function setPageNumber(int $page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Check if the supplied page number is the current one.
     *
     * @param int $page
     * @return bool
     */
    public function isCurrentPage(int $page): bool
    {
        return $page === $this->page;
    }

    /**
     * Check if the supplied page number is the first one.
     *
     * @param int $page
     * @return bool
     */
    public function isFirstPage(int $page): bool
    {
        return $page == 1;
    }

    /**
     * Check if the supplied page number is the previous one.
     *
     * @param int $page
     * @return bool
     */
    public function isPreviousPage(int $page): bool
    {
        return $page === $this->page - 1;
    }

    /**
     * Check if the supplied page number is the next one.
     *
     * @param int $page
     * @return bool
     */
    public function isNextPage(int $page): bool
    {
        return $page === $this->page + 1;
    }

    /**
     * Check if the supplied page number is the last one.
     *
     * @param int $page
     * @return bool
     */
    public function isLastPage(int $page): bool
    {
        return $page === $this->lastPage;
    }

    /**
     * Check if there is a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->page > 1;
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->page < $this->lastPage;
    }

    /**
     * Retrieve the items for the previous page.
     *
     * @return array|null
     */
    public function previousPage()
    {
        if ($this->hasPreviousPage()) {
            $this->page -= 1;
            return $this->getPage();
        }

        return null;
    }

    /**
     * Retrieve the items for the next page.
     *
     * @return array|null
     */
    public function nextPage()
    {
        if ($this->hasNextPage()) {
            $this->page += 1;
            return $this->getPage();
        }

        return null;
    }

    /**
     * Generate a list of pages for navigation.
     *
     * Works better with odd `$maxItems` values.
     *
     * @param int $maxItems
     * @param int $currentPage
     * @return array
     */
    public function pages(int $maxItems = 7, int $currentPage = null)
    {
        $lastPage = $this->lastPage;
        $currentPage = $currentPage ?? $this->page;

        # all items fit
        if ($lastPage < $maxItems) {
            $pp = [];

            for ($p = 1; $p <= $lastPage; $p++) {
                $pp[] = $p;
            }

            return $pp;
        }

        # page is at the beginning of the list
        if ($currentPage < ($maxItems - 2)) {
            $r = [];

            for ($i = 1; $i <= ($maxItems - 2); $i++) {
                $r[] = $i;
            }

            $r[] = "...";
            $r[] = $lastPage;

            return $r;
        }

        # page is at the end of the list
        if ($currentPage > $lastPage - ($maxItems - 3)) {
            $r = [
                1,
                "...",
            ];

            $boundary = $maxItems -  3;

            for ($i = $lastPage - $boundary; $i <= $lastPage; $i++) {
                $r[] = $i;
            }

            return $r;
        }

        # page is in the middle of the list

        $r = [1, "..."];
        $boundary = ceil($maxItems / 2) - 3;

        for ($i = $currentPage - $boundary; $i <= $currentPage + $boundary; $i++) {
            $r[] = $i;
        }

        $r[] = "...";
        $r[] = $lastPage;

        return $r;
    }
}
