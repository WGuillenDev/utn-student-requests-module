<div x-data="{
    confirmDelete: { open: false, step: 'confirm', id: null },
    askDelete(id) {
        this.confirmDelete = { open: true, step: 'confirm', id };
    },
    runDelete() {
        $wire.delete(this.confirmDelete.id)
            .then(() => { this.confirmDelete.step = 'success'; })
            .catch(() => { this.confirmDelete.open = false; });
    },
    closeDeleteModal() {
        this.confirmDelete.open = false;
    },
}">
    <x-ui.data-table
        :headers="[
                ['key' => 'institution', 'label' => __('Institution'), 'sortable' => true],
                ['key' => 'external_course', 'label' => __('External course'), 'sortable' => true],
                ['key' => 'course', 'label' => __('Course'), 'sortable' => false],
                ['key' => 'result', 'label' => __('Result'), 'sortable' => true],
                ['key' => 'resolution_number', 'label' => __('Resolution number'), 'sortable' => false],
            ]"
        :mode="$tableMode"
        :rows="[]"
        :searchable="['institution', 'external_course', 'resolution_number']"
        :paginator="$validationPrecedents ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1.6fr 1.6fr 1.6fr 1fr 1.2fr 1fr"
        :can-create="Auth::user()->can('create', \Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent::class)"
        :can-search="Auth::user()->can('search', \Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent::class)"
        :can-export-pdf="Auth::user()->can('exportPdf', \Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent::class)"
        :can-export-excel="Auth::user()->can('exportExcel', \Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent::class)"
        :title="__('Validation Precedents management')">

        @forelse ($validationPrecedents as $precedent)
        <div class="data-row" role="row">
            <span>{{ $precedent->institution() }}</span>
            <span>{{ $precedent->externalCourse() }}</span>
            <span>{{ $precedent->courseId() }}</span>
            <span>
                @if ($precedent->result() === 'Approved')
                <span class="status-badge system">{{ __('Approved') }}</span>
                @else
                <span class="status-badge custom">{{ __('Denied') }}</span>
                @endif
            </span>
            <span>{{ $precedent->resolutionNumber() }}</span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-edit="Auth::user()->can('update', $precedent)"
                    :can-delete="Auth::user()->can('delete', $precedent)"
                    edit-action="$wire.openEditModal({{ $precedent->id() }})"
                    delete-id="{{ $precedent->id() }}" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
    </x-ui.data-table>

    <x-ui.modal :show="$showModal" :title="$editingId === null ? __('New precedent') : __('Edit precedent')">
        <div class="form-field">
            <label for="precedentInstitution">{{ __('Institution') }}</label>
            <input type="text" id="precedentInstitution" wire:model="form.institution" class="{{ $errors->has('form.institution') ? 'has-error' : '' }}">
            @error('form.institution') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="precedentExternalCourse">{{ __('External course') }}</label>
            <input type="text" id="precedentExternalCourse" wire:model="form.externalCourse" class="{{ $errors->has('form.externalCourse') ? 'has-error' : '' }}">
            @error('form.externalCourse') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="precedentCourse">{{ __('Equivalent internal course') }}</label>
            <select id="precedentCourse" wire:model="form.courseId" class="{{ $errors->has('form.courseId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a course') }}</option>
                @foreach ($courseOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            @error('form.courseId') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="precedentResult">{{ __('Result') }}</label>
            <select id="precedentResult" wire:model="form.result">
                <option value="Approved">{{ __('Approved') }}</option>
                <option value="Denied">{{ __('Denied') }}</option>
            </select>
        </div>

        <div class="form-field">
            <label for="precedentResolutionNumber">{{ __('Resolution number') }}</label>
            <input type="text" id="precedentResolutionNumber" wire:model="form.resolutionNumber" class="{{ $errors->has('form.resolutionNumber') ? 'has-error' : '' }}">
            @error('form.resolutionNumber') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The precedent has been deleted.')" />
</div>
