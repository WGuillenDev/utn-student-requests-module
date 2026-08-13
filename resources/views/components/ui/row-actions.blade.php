@props([
'canView' => false,
'canEdit' => false,
'canDelete' => false,
'viewAction' => null,
'editAction' => null,
'deleteId' => null,
'deleteVisible' => 'true',
])

{{--
    Actions are invoked via Alpine's `$wire` magic (`@click="$wire.foo(...)"`)
    instead of `wire:click`, so the exact same markup works whether the row
    comes from a server @forelse (PHP-interpolated id) or a client-side
    `<template x-for>` (Alpine `row.id`) — see role-component / permission-
    component blade views. `$wire` resolves the nearest Livewire component
    root regardless of local x-data, so no extra wrapping is required here.

    "view" (blue eye icon) is opt-in via `canView`/`viewAction` — Role/
    Permission don't pass them, so they keep the Edit/Delete-only layout;
    modules with a read-only detail screen (e.g. ValidationPrecedent) wire
    both.

    Delete no longer uses the native confirm() dialog — it opens the
    reusable <x-ui.confirm-delete-modal> via askDelete(id), a method
    declared on the Livewire component's root element (see
    role-component.blade.php / permission-component.blade.php).
    `deleteId` is just the raw id expression ('row.id' in client mode,
    '{{ $entity->id() }}' in server mode) — askDelete() owns the actual
    confirm → delete → success flow from there.
--}}
@if ($canView && $viewAction)
<button type="button" class="action-icon view" @click="{{ $viewAction }}" title="{{ __('View') }}" aria-label="{{ __('View') }}">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
    </svg>
</button>
@endif

@if ($canEdit && $editAction)
<button type="button" class="action-icon edit" @click="{{ $editAction }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"></path>
    </svg>
</button>
@endif

@if ($canDelete && $deleteId)
<button type="button" class="action-icon delete" x-show="{{ $deleteVisible }}" @click="askDelete({{ $deleteId }})" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
        <path d="M10 11v6"></path>
        <path d="M14 11v6"></path>
        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
    </svg>
</button>
@endif
