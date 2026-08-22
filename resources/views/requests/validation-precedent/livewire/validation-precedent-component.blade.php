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
                ['key' => 'result', 'label' => __('Status'), 'sortable' => true],
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
            <span>{{ $courseLabels[$precedent->courseId()] ?? $precedent->courseId() }}</span>
            <span>
                @if ($precedent->result() === 'Approved')
                <span class="status-badge positive">{{ __('Approved') }}</span>
                @else
                <span class="status-badge negative">{{ __('Denied') }}</span>
                @endif
            </span>
            <span>{{ $precedent->resolutionNumber() }}</span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-view="Auth::user()->can('view', $precedent)"
                    :can-edit="Auth::user()->can('update', $precedent)"
                    :can-delete="Auth::user()->can('delete', $precedent)"
                    view-action="$wire.openViewModal({{ $precedent->id() }})"
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
            <label for="precedentCareer">{{ __('Career') }}</label>
            <select id="precedentCareer" wire:model.live="filterCareerId">
                <option value="">{{ __('Select a career') }}</option>
                @foreach ($careerOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
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
            @if ($filterCareerId === null)
            <span class="form-error">{{ __('You must select a career before choosing a course.') }}</span>
            @endif
        </div>

        <div class="form-field">
            <label for="precedentResult">{{ __('Status') }}</label>
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

    <x-ui.modal :show="$showViewModal" :title="__('Precedent detail')" close-action="closeViewModal">
        @if ($viewing)
        <div class="form-field">
            <label>{{ __('Institution') }}</label>
            <p>{{ $viewing['institution'] }}</p>
        </div>

        <div class="form-field">
            <label>{{ __('External course') }}</label>
            <p>{{ $viewing['externalCourse'] }}</p>
        </div>

        <div class="form-field">
            <label>{{ __('Equivalent internal course') }}</label>
            <p>{{ $courseLabels[$viewing['courseId']] ?? $viewing['courseId'] }}</p>
        </div>

        <div class="form-field">
            <label>{{ __('Status') }}</label>
            <p>
                @if ($viewing['result'] === 'Approved')
                <span class="status-badge positive">{{ __('Approved') }}</span>
                @else
                <span class="status-badge negative">{{ __('Denied') }}</span>
                @endif
            </p>
        </div>

        <div class="form-field">
            <label>{{ __('Resolution number') }}</label>
            <p>{{ $viewing['resolutionNumber'] }}</p>
        </div>
        @endif
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The precedent has been deleted.')" />
</div>
