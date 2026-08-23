@props([
'show' => false,
'title' => '',
'closeAction' => 'closeModal',
'size' => 'default',
])

{{--
    Generic modal chrome — reused by every CRUD's create/edit form (see
    role-component / permission-component blade views). Only the body
    (form fields) and footer (action buttons) differ per module; the
    backdrop/header/close-button markup and classes are ported 1:1 from
    the approved design so every future modal looks identical.
--}}
<div class="modal-backdrop {{ $show ? 'open' : '' }}">
    <div class="modal {{ $size === 'lg' ? 'lg' : '' }}">
        <div class="modal-head">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="modal-title">{{ $title }}</span>
                @if (isset($titleExtra) && $titleExtra->isNotEmpty())
                {{ $titleExtra }}
                @endif
            </div>
            <button type="button" class="modal-close" wire:click="{{ $closeAction }}" aria-label="{{ __('Close') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            {{ $slot }}
        </div>
        @if (isset($footer) && $footer->isNotEmpty())
        <div class="modal-footer">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>
