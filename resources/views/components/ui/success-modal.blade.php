@props([
'show' => false,
'title' => '',
'closeAction' => 'closeSuccessModal',
])

{{--
    Success-only variant of confirm-delete-modal.blade.php's visual
    pattern (.del-overlay/.del-card, animated green check) for flows
    where the action already happened server-side by the time this
    renders — no confirm step, always the "success" state. Driven by a
    plain Livewire boolean prop instead of Alpine step state, since
    there is no confirm -> delete -> success transition to manage
    locally (see confirm-delete-modal.blade.php for that case).
--}}
<div class="del-overlay {{ $show ? 'open' : '' }}">
    <div class="del-card">
        <div class="del-icon-success">
            <svg width="38" height="38" viewBox="0 0 52 52">
                <path d="M14 27l8 8 16-16" fill="none" stroke="#a5dc86" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="60"></path>
            </svg>
        </div>
        <p class="del-title">{{ $title }}</p>
        <div class="del-text">
            {{ $slot }}
        </div>
        <button type="button" class="del-btn-ok" wire:click="{{ $closeAction }}">OK</button>
    </div>
</div>
