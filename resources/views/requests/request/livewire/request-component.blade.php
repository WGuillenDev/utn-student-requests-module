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
                    :can-delete="Auth::user()->can('delete', $request)"
                    view-action="$wire.openViewModal({{ $request->id() }})"
                    view-label="{{ __('View details and documents') }}"
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
    <x-ui.modal :show="$showViewModal" :title="__('Request detail')" close-action="closeViewModal" size="lg">
        @if ($viewingRequest)
        <x-slot:titleExtra>
            <span class="status-badge {{ match(true) {
                    $viewingRequest['status'] === 'Approved' => 'positive',
                    $viewingRequest['status'] === 'Denied' => 'negative',
                    default => 'pending',
                } }}">{{ __($viewingRequest['status']) }}</span>
        </x-slot:titleExtra>
        @php
            $progressInTramite = $viewingRequest['status'] !== 'Pending Review' || count($viewingRequest['statusHistory']) > 0;
            $progressResolved = in_array($viewingRequest['status'], ['Approved', 'Denied'], true);
            $progressThirdLabel = match ($viewingRequest['status']) {
                'Approved' => __('Approved'),
                'Denied' => __('Denied'),
                default => __('Approved').' / '.__('Denied'),
            };
            $progressThirdColor = match ($viewingRequest['status']) {
                'Approved' => 'var(--badgeCustomText)',
                'Denied' => 'var(--actionDeleteText)',
                default => 'var(--textMuted)',
            };
        @endphp
        <div class="form-field">
            <label>{{ __('Request progress') }}</label>
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <span style="display:flex; align-items:center; gap:6px; font-size:13px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:var(--badgeCustomText); flex-shrink:0;"></span>
                    {{ __('Received') }}
                </span>
                <span style="display:flex; align-items:center; gap:6px; font-size:13px; opacity:{{ $progressInTramite ? '1' : '.45' }};">
                    <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background:{{ $progressInTramite ? 'var(--badgeCustomText)' : 'var(--textMuted)' }};"></span>
                    {{ __('In progress') }}
                </span>
                <span style="display:flex; align-items:center; gap:6px; font-size:13px; opacity:{{ $progressResolved ? '1' : '.45' }};">
                    <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background:{{ $progressResolved ? $progressThirdColor : 'var(--textMuted)' }};"></span>
                    {{ $progressThirdLabel }}
                </span>
            </div>
        </div>
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
        @if ($viewingRequest['canReview'])
        <div class="form-field" style="border-top:1px solid var(--border); padding-top:14px;">
            <label>{{ __('New status') }}</label>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                {{--
                    Course Validation reaches Approved via "Reconocer" in
                    the "Cursos a convalidar" table above instead, so it
                    doesn't get its own button here — Requirement Waiver
                    has no such alternate path, so it keeps a 5th
                    "Aprobada" button. Each button commits immediately
                    (calls changeStatus() directly, same one-click
                    pattern as Reconocer/No reconocer) instead of staging
                    a value for a separate "Confirmar" — there's no
                    modal footer here to hold that second step anymore.
                --}}
                @foreach (($reviewingType === 'Validation'
                    ? ['Pending Review', 'Verified by Registro', 'In Review', 'Denied']
                    : ['Pending Review', 'Verified by Registro', 'In Review', 'Approved', 'Denied']
                ) as $statusValue)
                <button type="button"
                    class="btn {{ $reviewStatus === $statusValue ? 'btn-primary' : 'btn-secondary' }}"
                    wire:click="changeStatus('{{ $statusValue }}')"
                    wire:loading.attr="disabled"
                    wire:target="changeStatus">{{ __($statusValue) }}</button>
                @endforeach
            </div>
        </div>
        @endif
        <div class="form-field">
            <label for="reviewEstimatedDate">{{ __('Estimated resolution date') }}</label>
            @if ($viewingRequest['canReview'])
            <span style="opacity:.6; font-size:12.5px;">{{ __('optional — auto-assigned after 24h if left blank') }}</span>
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <input type="date" id="reviewEstimatedDate" wire:model="reviewEstimatedDate" style="flex:1;" class="{{ $errors->has('reviewEstimatedDate') ? 'has-error' : '' }}">
                <button type="button" class="btn btn-secondary" wire:click="saveEstimatedDate" wire:loading.attr="disabled" wire:target="saveEstimatedDate">{{ __('Save date') }}</button>
            </div>
            @error('reviewEstimatedDate') <span class="form-error">{{ $message }}</span> @enderror
            @else
            <p>{{ $viewingRequest['estimatedResolutionDate'] ?? '—' }}</p>
            @endif
        </div>
        @if ($viewingRequest['canReview'])
        <div class="form-field">
            <label for="reviewComment">{{ __('Comment') }} <span style="opacity:.6;">({{ __('required to deny') }})</span></label>
            <textarea id="reviewComment" wire:model="reviewComment" class="{{ $errors->has('reviewComment') ? 'has-error' : '' }}"></textarea>
            @error('reviewComment') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        @endif
        <div class="form-field">
            <label>{{ __('Submitted') }}</label>
            <p>{{ $viewingRequest['submittedAt'] ?? '—' }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Attached documents') }}</label>
            @if (count($viewingRequest['documents']) === 0)
            <p style="opacity:.6;">{{ __('No documents attached') }}</p>
            @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach ($viewingRequest['documents'] as $document)
                <div class="file-chip" style="flex-direction:column; align-items:stretch; gap:8px;">
                    <span class="file-chip-name">{{ $document['originalName'] }} ({{ $document['sizeKb'] }} KB)</span>
                    <div style="display:flex; gap:8px;">
                        <a href="{{ route('requests.request.attachment-preview', ['fileId' => $document['id']]) }}"
                           target="_blank"
                           class="btn btn-secondary"
                           style="text-decoration:none; flex:1; justify-content:center;">
                            {{ __('Preview') }}
                        </a>
                        <a href="{{ route('requests.request.attachment-download', ['fileId' => $document['id']]) }}"
                           class="btn btn-primary"
                           style="text-decoration:none; flex:1; justify-content:center;">
                            {{ __('Download') }}
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            @if ($viewingRequest['canReview'])
            <div style="border-top:1px solid var(--border); padding-top:12px; margin-top:4px; display:flex; flex-direction:column; gap:10px;">
                <div class="form-field">
                    <label for="reviewDocumentType">{{ __('Document type') }}</label>
                    <input type="text" id="reviewDocumentType" wire:model="reviewDocumentType" class="{{ $errors->has('reviewDocumentType') ? 'has-error' : '' }}">
                    @error('reviewDocumentType') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-field">
                    <label for="reviewDocumentFile">{{ __('Attach a document') }} <span style="opacity:.6;">({{ __('PDF or image, max. 10MB') }})</span></label>
                    @if ($reviewDocumentFile && ! $errors->has('reviewDocumentFile'))
                        <div class="file-chip">
                            <span class="file-chip-name">{{ $reviewDocumentFile->getClientOriginalName() }}</span>
                            <button type="button" class="file-chip-remove" wire:click="$set('reviewDocumentFile', null)" aria-label="{{ __('Remove file') }}">&times;</button>
                        </div>
                    @else
                        <div
                            x-data="{ dragging: false }"
                            x-on:dragover.prevent="dragging = true"
                            x-on:dragleave.prevent="dragging = false"
                            x-on:drop.prevent="
                                dragging = false;
                                $refs.reviewDocumentFile.files = $event.dataTransfer.files;
                                $refs.reviewDocumentFile.dispatchEvent(new Event('change'));
                            "
                            :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                        >
                            <input type="file" id="reviewDocumentFile" x-ref="reviewDocumentFile" wire:model="reviewDocumentFile" class="{{ $errors->has('reviewDocumentFile') ? 'has-error' : '' }}">
                            <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                        </div>
                    @endif
                    @error('reviewDocumentFile')
                        <span class="form-error">{{ $message }}</span>
                    @elseif ($reviewDocumentFile)
                        <span class="form-success">{{ __('File attached') }}</span>
                    @enderror
                    <button type="button" class="btn btn-secondary" style="width:fit-content;" wire:click="uploadReviewDocument" wire:loading.attr="disabled" wire:target="uploadReviewDocument,reviewDocumentFile">{{ __('Upload document') }}</button>
                </div>
            </div>
            @endif
        </div>
        <div class="form-field">
            <label>{{ __('Status history') }}</label>
            @if (count($viewingRequest['statusHistory']) === 0)
            <p style="opacity:.6;">{{ __('No status changes recorded yet.') }}</p>
            @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach ($viewingRequest['statusHistory'] as $entry)
                <div style="border-left:2px solid var(--border); padding-left:10px;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:13px;">{{ $entry['previousStatus'] ? __($entry['previousStatus']) : __('(new)') }} → {{ __($entry['newStatus']) }}</span>
                        <span class="status-badge neutral" style="font-size:11px;">{{ $entry['changedBy'] }}</span>
                    </div>
                    <span style="font-size:11.5px; opacity:.5;">{{ $entry['createdAt'] }}</span>
                    @if ($entry['comment'])
                    <p style="font-size:12.5px; opacity:.7; margin:4px 0 0;">{{ $entry['comment'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <div class="form-field" x-data="{ recordOpen: true }">
            <div style="display:flex; align-items:center; justify-content:space-between; cursor:pointer;" @click="recordOpen = !recordOpen">
                <label style="margin:0; cursor:pointer;">{{ __('Student academic record') }} <span style="opacity:.6; font-weight:400;">· {{ count($viewingRequest['studentRecord']['courses']) }} {{ __('subjects') }}</span></label>
                <svg class="chevron-toggle" :class="{ 'open': recordOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div x-show="recordOpen" style="display:flex; flex-direction:column; gap:14px; margin-top:10px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('Full name') }}</span>
                        <p style="margin:2px 0 0;">{{ $viewingRequest['studentRecord']['fullName'] }}</p>
                    </div>
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('National ID') }}</span>
                        <p style="margin:2px 0 0;">{{ $viewingRequest['studentRecord']['nationalId'] }}</p>
                    </div>
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('Institutional email') }}</span>
                        <p style="margin:2px 0 0;">{{ $viewingRequest['studentRecord']['email'] ?? '—' }}</p>
                    </div>
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('Enrollment') }}</span>
                        <p style="margin:2px 0 0;">
                            <span class="status-badge {{ $viewingRequest['studentRecord']['active'] ? 'positive' : 'negative' }}">
                                {{ $viewingRequest['studentRecord']['active'] ? __('Enrollment active') : __('Enrollment inactive') }}
                            </span>
                        </p>
                    </div>
                </div>

                @foreach ($viewingRequest['studentRecord']['studyPlans'] as $plan)
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('Current career') }}</span>
                        <p style="margin:2px 0 0;">{{ $plan['career'] ?? '—' }}</p>
                    </div>
                    <div>
                        <span style="font-size:12px; opacity:.6;">{{ __('Term in the career') }}</span>
                        <p style="margin:2px 0 0;">{{ $plan['currentLevel'] }}</p>
                        <span style="font-size:11px; opacity:.5;">{{ $plan['planLabel'] }}</span>
                    </div>
                </div>
                @endforeach

                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    <span class="status-badge positive">{{ __('Approved courses') }}: {{ $viewingRequest['studentRecord']['summary']['approved'] }}</span>
                    <span class="status-badge negative">{{ __('Failed courses') }}: {{ $viewingRequest['studentRecord']['summary']['failed'] }}</span>
                    <span class="status-badge system">{{ __('Credited courses') }}: {{ $viewingRequest['studentRecord']['summary']['credited'] }}</span>
                    <span style="font-size:12.5px; opacity:.75;">{{ __('Average grade') }}: {{ $viewingRequest['studentRecord']['summary']['averageGrade'] ?? '—' }}</span>
                    <span style="font-size:12.5px; opacity:.75;">{{ __('Credits') }}: {{ $viewingRequest['studentRecord']['summary']['earnedCredits'] }} / {{ $viewingRequest['studentRecord']['summary']['totalCredits'] }}</span>
                </div>

                @if (count($viewingRequest['studentRecord']['courses']) === 0)
                <p style="opacity:.6;">{{ __('No academic record found') }}</p>
                @else
                <div style="max-height:260px; overflow-y:auto; overflow-x:auto; border:1px solid var(--border); border-radius:10px;">
                    <div style="min-width:560px;">
                        <div class="data-row" role="row" style="--table-cols: 3fr 0.9fr 0.9fr 1fr 0.7fr; font-size:12.5px;">
                            <span>{{ __('Course') }}</span>
                            <span>{{ __('Plan term') }}</span>
                            <span>{{ __('Taken in') }}</span>
                            <span>{{ __('Status') }}</span>
                            <span>{{ __('Grade') }}</span>
                        </div>
                        @foreach ($viewingRequest['studentRecord']['courses'] as $course)
                        <div class="data-row" role="row" style="--table-cols: 3fr 0.9fr 0.9fr 1fr 0.7fr;">
                            <span>{{ $course['course'] }}</span>
                            <span>{{ $course['planLevel'] ?? '—' }}</span>
                            <span>{{ $course['period'] }}</span>
                            <span>
                                <span class="status-badge {{ match(true) {
                                        in_array($course['status'], ['Approved', 'Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'], true) => 'positive',
                                        $course['status'] === 'Failed' => 'negative',
                                        default => '',
                                    } }}">{{ __($course['status']) }}</span>
                            </span>
                            <span>{{ $course['grade'] ?? '—' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The request has been deleted.')" />
</div>
