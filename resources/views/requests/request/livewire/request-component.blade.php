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
    {{-- ES-04's "filterable by type, program, status, and received date"
         is satisfied through the single search box below instead of a
         separate filter panel (Docencia's explicit UX call) — see
         EloquentRequestRepository::baseQuery() for the matching against
         type/status labels and the received date, on top of the
         existing student/course text match. --}}

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
            <span>{{ $studentLabels[$request->studentId()] ?? $request->studentId() }}</span>
            <span>{{ match ($request->type()) {
                    'Requirement Waiver' => __('Requirement Waiver'),
                    'Validation' => __('Course Validation'),
                    default => $request->type(),
                } }}
                @if ($request->validationPrecedentId() !== null)
                <span class="status-badge positive" title="{{ __('Approved precedent found in the historical catalog') }}">{{ __('Precedent found') }}</span>
                @endif
            </span>
            <span>{{ $courseLabels[$request->courseId()] ?? $request->courseId() }}</span>
            <span>
                @php $status = $request->status(); @endphp
                <span class="status-badge {{ match(true) {
                        $status === 'Approved' => 'positive',
                        $status === 'Denied' => 'negative',
                        $status === 'Pending Review' => 'pending',
                        default => '',
                    } }}">{{ __($status) }}</span>
            </span>
            <span>{{ $request->estimatedResolutionDate() ?? '—' }}</span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-view="Auth::user()->can('view', $request)"
                    :can-edit="Auth::user()->can('review', $request) && ! $request->isFinal()"
                    :can-delete="Auth::user()->can('delete', $request)"
                    view-action="$wire.openViewModal({{ $request->id() }})"
                    view-label="{{ __('View details and documents') }}"
                    edit-action="$wire.openReviewModal({{ $request->id() }})"
                    delete-id="{{ $request->id() }}" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
    </x-ui.data-table>

    {{-- Create modal --}}
    <x-ui.modal :show="$showCreateModal" :title="__('New request')" close-action="closeCreateModal">
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
                <option value="Validation">{{ __('Course Validation') }}</option>
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

        <div class="form-field">
            <label for="requestSupportDocument">{{ __('Supporting document') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
            @if ($form->supportDocument && ! $errors->has('form.supportDocument'))
                <div class="file-chip">
                    <span class="file-chip-name">{{ $form->supportDocument->getClientOriginalName() }}</span>
                    <button type="button" class="file-chip-remove" wire:click="removeFile('form.supportDocument')" aria-label="{{ __('Remove file') }}">&times;</button>
                </div>
            @else
                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="
                        dragging = false;
                        $refs.requestSupportDocument.files = $event.dataTransfer.files;
                        $refs.requestSupportDocument.dispatchEvent(new Event('change'));
                    "
                    :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                >
                    <input type="file" id="requestSupportDocument" x-ref="requestSupportDocument" wire:model="form.supportDocument" class="{{ $errors->has('form.supportDocument') ? 'has-error' : '' }}">
                    <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                </div>
            @endif
            @error('form.supportDocument')
                <span class="form-error">{{ $message }}</span>
            @elseif ($form->supportDocument)
                <span class="form-success">{{ __('File attached') }}</span>
            @enderror
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

        <div class="form-field">
            <label for="requestExternalProgramFile">{{ __('External course syllabus') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
            @if ($form->externalProgramFile && ! $errors->has('form.externalProgramFile'))
                <div class="file-chip">
                    <span class="file-chip-name">{{ $form->externalProgramFile->getClientOriginalName() }}</span>
                    <button type="button" class="file-chip-remove" wire:click="removeFile('form.externalProgramFile')" aria-label="{{ __('Remove file') }}">&times;</button>
                </div>
            @else
                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="
                        dragging = false;
                        $refs.requestExternalProgramFile.files = $event.dataTransfer.files;
                        $refs.requestExternalProgramFile.dispatchEvent(new Event('change'));
                    "
                    :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                >
                    <input type="file" id="requestExternalProgramFile" x-ref="requestExternalProgramFile" wire:model="form.externalProgramFile" class="{{ $errors->has('form.externalProgramFile') ? 'has-error' : '' }}">
                    <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                </div>
            @endif
            @error('form.externalProgramFile')
                <span class="form-error">{{ $message }}</span>
            @elseif ($form->externalProgramFile)
                <span class="form-success">{{ __('File attached') }}</span>
            @enderror
        </div>

        <div class="form-field">
            <label for="requestGradeCertificationFile">{{ __('Grade certification') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
            @if ($form->gradeCertificationFile && ! $errors->has('form.gradeCertificationFile'))
                <div class="file-chip">
                    <span class="file-chip-name">{{ $form->gradeCertificationFile->getClientOriginalName() }}</span>
                    <button type="button" class="file-chip-remove" wire:click="removeFile('form.gradeCertificationFile')" aria-label="{{ __('Remove file') }}">&times;</button>
                </div>
            @else
                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="
                        dragging = false;
                        $refs.requestGradeCertificationFile.files = $event.dataTransfer.files;
                        $refs.requestGradeCertificationFile.dispatchEvent(new Event('change'));
                    "
                    :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                >
                    <input type="file" id="requestGradeCertificationFile" x-ref="requestGradeCertificationFile" wire:model="form.gradeCertificationFile" class="{{ $errors->has('form.gradeCertificationFile') ? 'has-error' : '' }}">
                    <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                </div>
            @endif
            @error('form.gradeCertificationFile')
                <span class="form-error">{{ $message }}</span>
            @elseif ($form->gradeCertificationFile)
                <span class="form-success">{{ __('File attached') }}</span>
            @enderror
        </div>

        <div class="form-field">
            <label for="requestInstitutionProofFile">{{ __('Institution proof') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
            @if ($form->institutionProofFile && ! $errors->has('form.institutionProofFile'))
                <div class="file-chip">
                    <span class="file-chip-name">{{ $form->institutionProofFile->getClientOriginalName() }}</span>
                    <button type="button" class="file-chip-remove" wire:click="removeFile('form.institutionProofFile')" aria-label="{{ __('Remove file') }}">&times;</button>
                </div>
            @else
                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="
                        dragging = false;
                        $refs.requestInstitutionProofFile.files = $event.dataTransfer.files;
                        $refs.requestInstitutionProofFile.dispatchEvent(new Event('change'));
                    "
                    :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                >
                    <input type="file" id="requestInstitutionProofFile" x-ref="requestInstitutionProofFile" wire:model="form.institutionProofFile" class="{{ $errors->has('form.institutionProofFile') ? 'has-error' : '' }}">
                    <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                </div>
            @endif
            @error('form.institutionProofFile')
                <span class="form-error">{{ $message }}</span>
            @elseif ($form->institutionProofFile)
                <span class="form-success">{{ __('File attached') }}</span>
            @enderror
        </div>
        @endif

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeCreateModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save,form.supportDocument,form.externalProgramFile,form.gradeCertificationFile,form.institutionProofFile">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Review modal (the real "edit" of this CRUD: change status, not fields) --}}
    {{-- Read-only detail — available for every request regardless of
         status, so closed requests remain reviewable after the fact. --}}
    <x-ui.modal :show="$showViewModal" :title="__('Request detail')" close-action="closeViewModal">
        @if ($viewingRequest)
        <x-slot:titleExtra>
            <span class="status-badge {{ match(true) {
                    $viewingRequest['status'] === 'Approved' => 'positive',
                    $viewingRequest['status'] === 'Denied' => 'negative',
                    default => 'pending',
                } }}">{{ __($viewingRequest['status']) }}</span>
        </x-slot:titleExtra>
        @if ($viewingRequest['type'] === 'Requirement Waiver')
        <div class="form-field">
            <label>{{ __('Student') }}</label>
            <p>{{ $viewingRequest['student'] }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Type') }}</label>
            <p>{{ __('Requirement Waiver') }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Course') }}</label>
            <p>{{ $viewingRequest['course'] }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Unmet requirement') }}</label>
            <p>{{ $viewingRequest['requiredCourse'] ?? '—' }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Justification') }}</label>
            <p>{{ $viewingRequest['waiverJustification'] ? __($viewingRequest['waiverJustification']) : '—' }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Engine result') }}</label>
            <p>{{ $viewingRequest['engineResult'] ? __($viewingRequest['engineResult']) : __('Requires manual review') }}</p>
        </div>
        @else
        <div class="form-field">
            <label>{{ __('Courses to validate') }}</label>
            <div style="border:1px solid var(--border); border-radius:10px; padding:14px; display:flex; flex-direction:column; gap:12px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('UTN course') }}</span>
                        <p style="margin:2px 0 0;">{{ $viewingRequest['course'] }}</p>
                    </div>
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('External course name') }}</span>
                        <p style="margin:2px 0 0;">{{ $viewingRequest['externalCourse'] ?? '—' }}</p>
                    </div>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Origin institution') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['originInstitution'] ?? '—' }}</p>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:end;">
                    <div class="form-field" style="gap:4px;">
                        <label for="viewingExternalCourseCode" style="font-size:12px;">{{ __('External course code') }}</label>
                        <input type="text" id="viewingExternalCourseCode" wire:model="viewingExternalCourseCode" @if (! $viewingRequest['canReview']) disabled @endif>
                    </div>
                    <div class="form-field" style="gap:4px;">
                        <label for="viewingExternalCourseCredits" style="font-size:12px;">{{ __('External course credits') }}</label>
                        <input type="number" min="0" max="255" id="viewingExternalCourseCredits" wire:model="viewingExternalCourseCredits" @if (! $viewingRequest['canReview']) disabled @endif>
                    </div>
                    @if ($viewingRequest['canReview'])
                    <button type="button" class="btn btn-secondary" wire:click="saveExternalCourseData" wire:loading.attr="disabled" wire:target="saveExternalCourseData">{{ __('Save external course data') }}</button>
                    @endif
                </div>

                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Resolution') }}</span>
                    <p style="margin:2px 0 0;">
                        <span class="status-badge {{ match(true) {
                                $viewingRequest['status'] === 'Approved' => 'positive',
                                $viewingRequest['status'] === 'Denied' => 'negative',
                                default => 'pending',
                            } }}">{{ match(true) {
                                $viewingRequest['status'] === 'Approved' => __('Recognized'),
                                $viewingRequest['status'] === 'Denied' => __('Not recognized'),
                                default => __('Pending'),
                            } }}</span>
                    </p>
                </div>

                @if ($viewingRequest['canReview'])
                <div class="form-field" style="gap:6px;">
                    <label for="viewingCourseReason" style="font-size:12px;">{{ __('Reason, required to not recognize this course') }}</label>
                    <textarea id="viewingCourseReason" wire:model="reviewComment" class="{{ $errors->has('reviewComment') ? 'has-error' : '' }}"></textarea>
                    @error('reviewComment') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-primary" wire:click="changeStatus('Approved')" wire:loading.attr="disabled" wire:target="changeStatus">{{ __('Recognize') }}</button>
                    <button type="button" class="btn btn-orange" wire:click="changeStatus('Denied')" wire:loading.attr="disabled" wire:target="changeStatus">{{ __('Do not recognize') }}</button>
                </div>
                @endif
            </div>
        </div>
        <div class="form-field">
            <label>{{ __('Student') }}</label>
            <p>{{ $viewingRequest['student'] }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Type') }}</label>
            <p>{{ __('Course Validation') }}</p>
        </div>
        @if ($viewingRequest['precedentResolution'])
        <div class="form-field">
            <div class="status-badge positive" style="display:inline-flex;">
                {{ __('Approved precedent found in the historical catalog') }} — {{ __('Reference resolution') }}: {{ $viewingRequest['precedentResolution'] }}
            </div>
        </div>
        @endif
        @endif
        <div class="form-field">
            <label>{{ __('Estimated resolution date') }}</label>
            <p>{{ $viewingRequest['estimatedResolutionDate'] ?? '—' }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Submitted') }}</label>
            <p>{{ $viewingRequest['submittedAt'] ?? '—' }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Attached documents') }}</label>
            @if (count($viewingRequest['documents']) === 0)
            <p style="opacity:.6;">{{ __('No documents attached') }}</p>
            @else
            <div style="display:flex; flex-direction:column; gap:6px;">
                @foreach ($viewingRequest['documents'] as $document)
                <a href="{{ route('requests.request.attachment-download', ['fileId' => $document['id']]) }}"
                   target="_blank"
                   class="file-chip"
                   style="text-decoration:none;">
                    <span class="file-chip-name">{{ $document['originalName'] }} ({{ $document['sizeKb'] }} KB)</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>
        <div class="form-field">
            <label>{{ __('Student academic record') }}</label>
            @if (count($viewingRequest['studentRecord']['studyPlans']) > 0)
            <div style="display:flex; flex-direction:column; gap:4px;">
                @foreach ($viewingRequest['studentRecord']['studyPlans'] as $plan)
                <span style="font-size:13px; opacity:.8;">{{ $plan['name'] }} — {{ __('Current level') }}: {{ $plan['currentLevel'] }}</span>
                @endforeach
            </div>
            @endif
            @if (count($viewingRequest['studentRecord']['courses']) === 0)
            <p style="opacity:.6;">{{ __('No academic record found') }}</p>
            @else
            <div style="display:flex; flex-direction:column; gap:6px; max-height:220px; overflow-y:auto;">
                @foreach ($viewingRequest['studentRecord']['courses'] as $course)
                <div class="file-chip">
                    <span class="file-chip-name">{{ $course['course'] }}</span>
                    <span style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
                        <span class="status-badge {{ match(true) {
                                in_array($course['status'], ['Approved', 'Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'], true) => 'positive',
                                $course['status'] === 'Failed' => 'negative',
                                default => '',
                            } }}">{{ __($course['status']) }}</span>
                        @if ($course['grade'] !== null)
                        <span style="font-size:12.5px; opacity:.7;">{{ $course['grade'] }}</span>
                        @endif
                        <span style="font-size:12.5px; opacity:.6;">{{ $course['period'] }}</span>
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </x-ui.modal>

    <x-ui.modal :show="$showReviewModal" :title="__('Review request')" close-action="closeReviewModal">
        @if ($reviewPrecedentResolution !== null)
        <div class="form-field">
            <div class="status-badge positive" style="display:inline-flex;">
                {{ __('Approved precedent found in the historical catalog') }} — {{ __('Reference resolution') }}: {{ $reviewPrecedentResolution }}
            </div>
        </div>
        @endif
        <div class="form-field">
            <label for="reviewStatus">{{ __('New status') }}</label>
            <select id="reviewStatus" wire:model="reviewStatus">
                <option value="Pending Review">{{ __('Pending Review') }}</option>
                <option value="Approved">{{ __('Approved') }}</option>
                <option value="Denied">{{ __('Denied') }}</option>
            </select>
        </div>
        <div class="form-field">
            <label for="reviewEstimatedDate">{{ __('Estimated resolution date') }} <span style="opacity:.6;">({{ __('optional — auto-assigned after 24h if left blank') }})</span></label>
            <input type="date" id="reviewEstimatedDate" wire:model="reviewEstimatedDate">
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
