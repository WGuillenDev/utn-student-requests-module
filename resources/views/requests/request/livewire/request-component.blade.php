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
                ['key' => 'student', 'label' => __('Student'), 'sortable' => false],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'course', 'label' => __('Course'), 'sortable' => false],
                ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'created_at', 'label' => __('Submitted'), 'sortable' => true],
            ]"
        :mode="$tableMode"
        :rows="[]"
        :searchable="['student', 'course']"
        :paginator="$requests ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="2fr 1.4fr 1.6fr 1.2fr 1fr 1fr"
        :can-create="Auth::user()->can('create', \Src\Requests\Request\Domain\Entities\Request::class)"
        :can-search="Auth::user()->can('search', \Src\Requests\Request\Domain\Entities\Request::class)"
        :can-export-pdf="Auth::user()->can('exportPdf', \Src\Requests\Request\Domain\Entities\Request::class)"
        :can-export-excel="Auth::user()->can('exportExcel', \Src\Requests\Request\Domain\Entities\Request::class)"
        :title="__('Requests management')">

        @forelse ($requests as $request)
        <div class="data-row" role="row">
            <span>{{ $request->studentId() }}</span>
            <span>{{ __($request->type()) }}</span>
            <span>{{ $request->courseId() }}</span>
            <span>
                @php $status = $request->status(); @endphp
                <span class="status-badge {{ match(true) {
                        $status === 'Approved' => 'positive',
                        $status === 'Denied' => 'negative',
                        $status === 'Pending Review', $status === 'In Review' => 'pending',
                        default => '',
                    } }}">{{ __($status) }}</span>
            </span>
            <span>{{ $request->estimatedResolutionDate() ?? '—' }}</span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-edit="Auth::user()->can('review', $request) && ! $request->isFinal()"
                    :can-delete="Auth::user()->can('delete', $request)"
                    edit-action="$wire.openReviewModal({{ $request->id() }})"
                    delete-id="{{ $request->id() }}" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
    </x-ui.data-table>

    {{-- Create modal --}}
    <x-ui.modal :show="$showCreateModal" :title="__('New request')">
        <div class="form-field">
            <label for="requestStudent">{{ __('Student') }}</label>
            <select id="requestStudent" wire:model="form.studentId" class="{{ $errors->has('form.studentId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a student') }}</option>
                @foreach ($studentOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            @error('form.studentId') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="requestType">{{ __('Type') }}</label>
            <select id="requestType" wire:model.live="form.type">
                <option value="Requirement Waiver">{{ __('Requirement Waiver') }}</option>
                <option value="Validation">{{ __('Validation') }}</option>
            </select>
        </div>

        <div class="form-field">
            <label for="requestCourse">{{ __('Course to enroll') }}</label>
            <select id="requestCourse" wire:model="form.courseId" class="{{ $errors->has('form.courseId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a course') }}</option>
                @foreach ($courseOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            @error('form.courseId') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        @if ($form->type === 'Requirement Waiver')
        <div class="form-field">
            <label for="requestRequiredCourse">{{ __('Unmet requirement') }}</label>
            <select id="requestRequiredCourse" wire:model="form.requiredCourseId" class="{{ $errors->has('form.requiredCourseId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a course') }}</option>
                @foreach ($courseOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            @error('form.requiredCourseId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        @else
        <div class="form-field">
            <label for="requestOriginInstitution">{{ __('Origin institution') }}</label>
            <input type="text" id="requestOriginInstitution" wire:model="form.originInstitution" class="{{ $errors->has('form.originInstitution') ? 'has-error' : '' }}">
            @error('form.originInstitution') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="requestExternalCourse">{{ __('External course') }}</label>
            <input type="text" id="requestExternalCourse" wire:model="form.externalCourse" class="{{ $errors->has('form.externalCourse') ? 'has-error' : '' }}">
            @error('form.externalCourse') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        @endif

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeCreateModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Review modal (the real "edit" of this CRUD: change status, not fields) --}}
    <x-ui.modal :show="$showReviewModal" :title="__('Review request')">
        <div class="form-field">
            <label for="reviewStatus">{{ __('New status') }}</label>
            <select id="reviewStatus" wire:model="reviewStatus">
                <option value="Pending Review">{{ __('Pending Review') }}</option>
                <option value="In Review">{{ __('In Review') }}</option>
                <option value="Approved">{{ __('Approved') }}</option>
                <option value="Denied">{{ __('Denied') }}</option>
            </select>
        </div>
        <div class="form-field">
            <label for="reviewComment">{{ __('Comment') }} <span style="opacity:.6;">({{ __('required to deny') }})</span></label>
            <textarea id="reviewComment" wire:model="reviewComment" class="{{ $errors->has('reviewComment') ? 'has-error' : '' }}"></textarea>
            @error('reviewComment') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeReviewModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="changeStatus">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The request has been deleted.')" />
</div>
