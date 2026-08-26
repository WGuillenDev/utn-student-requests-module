@props([
'show' => false,
'title' => '',
'closeAction' => 'closeModal',
'size' => 'default',
])

@php($modalTitleId = 'modal-title-'.Str::random(8))

{{--
    Generic modal chrome — reused by every CRUD's create/edit form (see
    role-component / permission-component blade views). Only the body
    (form fields) and footer (action buttons) differ per module.

    Accessibility: the dialog is announced as such (role/aria-modal/
    aria-labelledby), Escape closes it, focus moves inside when it opens
    and returns to the trigger when it closes, and Tab cycles within the
    dialog instead of escaping to the page behind it. The open state is
    driven by a server-rendered class, so it is observed rather than
    bound — Livewire re-renders replace the attribute directly.
--}}
<div class="modal-backdrop {{ $show ? 'open' : '' }}"
    x-data="{
        isOpen: false,
        lastFocused: null,
        init() {
            this.isOpen = this.$el.classList.contains('open');
            if (this.isOpen) this.handleOpen();
            new MutationObserver(() => {
                const nowOpen = this.$el.classList.contains('open');
                if (nowOpen === this.isOpen) return;
                this.isOpen = nowOpen;
                nowOpen ? this.handleOpen() : this.handleClose();
            }).observe(this.$el, { attributes: true, attributeFilter: ['class'] });
        },
        handleOpen() {
            this.lastFocused = document.activeElement;
            this.$nextTick(() => {
                // Focus the dialog itself rather than its first control, so
                // screen readers announce the title first and the body stays
                // scrolled to the top.
                this.$el.querySelector('.modal').focus({ preventScroll: true });
            });
        },
        handleClose() {
            if (this.lastFocused && document.contains(this.lastFocused)) {
                this.lastFocused.focus();
            }
            this.lastFocused = null;
        },
        focusables() {
            const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])';
            return Array.from(this.$el.querySelectorAll(selector))
                .filter(el => el.offsetParent !== null && el.getAttribute('tabindex') !== '-1');
        },
        trapTab(event) {
            const targets = this.focusables();
            if (targets.length === 0) return;
            const first = targets[0];
            const last = targets[targets.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    }"
    @keydown.tab="trapTab($event)"
    @keydown.escape.window="if (isOpen) $wire.{{ $closeAction }}()">
    <div class="modal {{ $size === 'lg' ? 'lg' : '' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalTitleId }}" tabindex="-1">
        <div class="modal-head">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="modal-title" id="{{ $modalTitleId }}">{{ $title }}</span>
                @if (isset($titleExtra) && $titleExtra->isNotEmpty())
                {{ $titleExtra }}
                @endif
            </div>
            <button type="button" class="modal-close" wire:click="{{ $closeAction }}" aria-label="{{ __('Close') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
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
