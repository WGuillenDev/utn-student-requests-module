<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Pagination, sorting and search state shared by every CRUD data-table
 * component.
 *
 * Two modes, selected per component via $tableMode:
 *  - 'client': the full collection is handed to Alpine once per render
 *    (see resources/js/data-table.js) and filtered in the browser. For
 *    small reference datasets such as roles or permissions.
 *  - 'server': Livewire-driven pagination, for datasets too large to
 *    ship in one response.
 *
 * A component sets $tableMode, then branches on isServerMode() in
 * render() to call the matching use case method. Everything else is
 * inherited.
 */
trait InteractsWithDataTable
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    public int $perPage = 10;

    public int $page = 1;

    public string $sortKey = '';

    public string $sortDir = 'asc';

    /**
     * Lets the Blade view pick which directives to render: Livewire
     * bindings for server mode, Alpine bindings for client mode.
     */
    public function tableMode(): string
    {
        return $this->tableMode;
    }

    public function isServerMode(): bool
    {
        return $this->tableMode() === 'server';
    }

    public function isClientMode(): bool
    {
        return ! $this->isServerMode();
    }

    /**
     * Server mode only — client mode resets its own page inside Alpine
     * and never touches this property over the wire.
     */
    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingPerPage(): void
    {
        $this->page = 1;
    }

    /**
     * Server mode only. In client mode, sorting is handled by Alpine's
     * `sort()` method in resources/js/data-table.js and this method is
     * simply never wired up in the Blade view.
     */
    public function sort(string $key): void
    {
        $this->sortDir = $this->sortKey === $key && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortKey = $key;
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Pushes freshly re-fetched rows into the Alpine table after a
     * mutation. Necessary because Alpine evaluates x-data only on first
     * insert, and Livewire's DOM morph preserves that state — so a new
     * x-data attribute is never re-read and `rows` would go stale.
     *
     * No-op in server mode, so components can call it unconditionally.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function refreshTable(array $rows): void
    {
        if ($this->isClientMode()) {
            $this->dispatch('data-table-refresh', rows: $rows);
        }
    }
}
