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
    confirmStatusChange: { open: false, status: null },
    askConfirmStatusChange(status) {
        this.confirmStatusChange = { open: true, status };
    },
    runConfirmStatusChange() {
        $wire.changeStatus(this.confirmStatusChange.status);
        this.confirmStatusChange.open = false;
    },
    closeConfirmStatusChange() {
        this.confirmStatusChange.open = false;
    },
    confirmIssueResolution: { open: false, decision: null },
    askConfirmIssueResolution(decision) {
        this.confirmIssueResolution = { open: true, decision };
    },
    runConfirmIssueResolution() {
        $wire.issueResolution(this.confirmIssueResolution.decision);
        this.confirmIssueResolution.open = false;
    },
    closeConfirmIssueResolution() {
        this.confirmIssueResolution.open = false;
    },
}">
    {{-- Split into per-type tabs, but entered from three separate
         sidebar links (see sidebar.blade.php's "Solicitudes
         estudiantiles" submenu) rather than in-page buttons here —
         $activeTab is URL-bound (?tab=...) so each sidebar href just
         points straight at the tab it names. ES-04's "filterable by ...
         status, and received date" (now that type has its own tab) is
         still satisfied through the single search box below rather than
         a separate filter panel (Docencia's explicit UX call) — see
         EloquentRequestRepository::baseQuery() for the matching against
         status labels and the received date, on top of the existing
         student/course text match. --}}
    @if ($activeTab === 'history')
    {{-- "Historial de solicitudes resueltas": both Requirement Waiver
         and Validation together, but only once Registro has actually
         closed them (see RequestComponent::activeTypeFilter()'s
         'history' case) — the Estado badge is deliberately the plain
         Aprobada/Denegada wording rather than the full "... por
         Registro" status label, since every row here is by definition
         a Registro resolution. --}}
    <x-ui.data-table
        :headers="[
                ['key' => 'created_at', 'label' => __('Received'), 'sortable' => true],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'student', 'label' => __('Student'), 'sortable' => false],
                ['key' => 'course', 'label' => __('Course'), 'sortable' => false],
                ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
            ]"
        :mode="$tableMode"
        :rows="[]"
        :searchable="['student', 'course']"
        :paginator="$requests ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1fr 1.6fr 1.8fr 1.8fr 1fr 1fr"
        :can-create="false"
        :can-search="Auth::user()->can('search', \Src\Requests\Request\Domain\Entities\Request::class)"
        :can-export-pdf="Auth::user()->can('exportPdf', \Src\Requests\Request\Domain\Entities\Request::class)"
        :can-export-excel="Auth::user()->can('exportExcel', \Src\Requests\Request\Domain\Entities\Request::class)"
        :title="__('Resolved requests history')">

        @forelse ($requests as $request)
        <div class="data-row" role="row">
            <span>{{ $request->createdAt() ? date('Y-m-d', strtotime($request->createdAt())) : '—' }}</span>
            <span>{{ match ($request->type()) {
                    'Requirement Waiver' => __('Requirement Waiver'),
                    'Validation' => __('Course Validation'),
                    default => $request->type(),
                } }}</span>
            <span>{{ $studentLabels[$request->studentId()] ?? $request->studentId() }}</span>
            <span>{{ $courseLabels[$request->courseId()] ?? $request->courseId() }}</span>
            <span>
                <span class="status-badge {{ $request->status() === 'Approved by Registro' ? 'positive' : 'negative' }}">
                    {{ $request->status() === 'Approved by Registro' ? __('Approved') : __('Denied') }}
                </span>
            </span>
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
    @else
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
        :title="$activeTab === 'validation' ? __('Course validation requests management') : __('Waiver requests management')">

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
                @php
                    $status = $request->status();
                    // Registro's bandeja only ever contains 'Approved by
                    // Docencia'/'Denied by Docencia' rows (see
                    // activeTypeFilter()) — Registro hasn't looked at the
                    // request yet, so the list intentionally doesn't leak
                    // Docencia's verdict here; it shows once Registro
                    // opens the detail modal instead.
                    $isRegistroBandeja = Auth::user()->hasPermissionTo('requests.finalize');
                @endphp
                @if ($isRegistroBandeja && in_array($status, ['Approved by Docencia', 'Denied by Docencia'], true))
                <span class="status-badge system">{{ __('Resolved by Docencia') }}</span>
                @else
                <span class="status-badge {{ match(true) {
                        in_array($status, ['Approved by Docencia', 'Approved by Registro'], true) => 'positive',
                        in_array($status, ['Denied by Docencia', 'Denied by Registro'], true) => 'negative',
                        $status === 'Pending Review' => 'pending',
                        default => '',
                    } }}">{{ __($status) }}</span>
                @endif
            </span>
            <span>{{ $request->createdAt() ? date('Y-m-d', strtotime($request->createdAt())) : '—' }}</span>
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
    @endif

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
                    in_array($viewingRequest['status'], ['Approved by Docencia', 'Approved by Registro'], true) => 'positive',
                    in_array($viewingRequest['status'], ['Denied by Docencia', 'Denied by Registro'], true) => 'negative',
                    default => 'pending',
                } }}">{{ __($viewingRequest['status']) }}</span>
        </x-slot:titleExtra>
        @php
            // Docencia's decision (Approved/Denied by Docencia) is, by
            // design, the same click that both sends the request to
            // Registro and hands it received — there's no separate
            // "receive" action in this synchronous system (see
            // ChangeRequestStatusUseCase's synthetic history rows), so
            // both middle steps light up together. The last one only
            // once Registro has actually published the resolution.
            $progressSentToRegistro = ! in_array($viewingRequest['status'], ['Pending Review', 'In Review'], true);
            $progressPublished = in_array($viewingRequest['status'], ['Approved by Registro', 'Denied by Registro'], true);
        @endphp
        <div class="form-field">
            <label>{{ __('Request progress') }}</label>
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <span style="display:flex; align-items:center; gap:6px; font-size:13px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:var(--badgeCustomText); flex-shrink:0;"></span>
                    {{ __('Received by Docencia') }}
                </span>
                <span style="display:flex; align-items:center; gap:6px; font-size:13px; opacity:{{ $progressSentToRegistro ? '1' : '.45' }};">
                    <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background:{{ $progressSentToRegistro ? 'var(--badgeCustomText)' : 'var(--textMuted)' }};"></span>
                    {{ __('Sent to Registro') }}
                </span>
                <span style="display:flex; align-items:center; gap:6px; font-size:13px; opacity:{{ $progressSentToRegistro ? '1' : '.45' }};">
                    <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background:{{ $progressSentToRegistro ? 'var(--badgeCustomText)' : 'var(--textMuted)' }};"></span>
                    {{ __('Received by Registro') }}
                </span>
                <span style="display:flex; align-items:center; gap:6px; font-size:13px; opacity:{{ $progressPublished ? '1' : '.45' }};">
                    <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background:{{ $progressPublished ? 'var(--badgeCustomText)' : 'var(--textMuted)' }};"></span>
                    {{ __('Published by Registro') }}
                </span>
            </div>
        </div>
        @if ($viewingRequest['type'] === 'Requirement Waiver')
        <div class="form-field">
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px 24px;">
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Student') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['student'] }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Type') }}</span>
                    <p style="margin:2px 0 0;">{{ __('Requirement Waiver') }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Course') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['course'] }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Unmet requirement') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['requiredCourse'] ?? '—' }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Justification') }} (SLR-002)</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['waiverJustification'] ? __($viewingRequest['waiverJustification']) : '—' }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Received date') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['submittedAt'] ? date('Y-m-d', strtotime($viewingRequest['submittedAt'])) : '—' }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Engine result') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['engineResult'] ? __($viewingRequest['engineResult']) : __('Requires manual review') }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Current status') }}</span>
                    <p style="margin:2px 0 0;">
                        <span class="status-badge {{ match(true) {
                                in_array($viewingRequest['status'], ['Approved by Docencia', 'Approved by Registro'], true) => 'positive',
                                in_array($viewingRequest['status'], ['Denied by Docencia', 'Denied by Registro'], true) => 'negative',
                                default => 'pending',
                            } }}">{{ __($viewingRequest['status']) }}</span>
                    </p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;" id="waiverEstimatedDateLabel">{{ __('Resolution date') }}</span>
                    @if ($viewingRequest['canReview'] && $editingResolutionDate)
                    <div style="display:flex; gap:8px; align-items:flex-start; margin-top:2px;">
                        <input type="date" aria-labelledby="waiverEstimatedDateLabel" wire:model="reviewEstimatedDate" style="flex:1;" class="{{ $errors->has('reviewEstimatedDate') ? 'has-error' : '' }}">
                        <button type="button" class="btn btn-secondary" wire:click="saveEstimatedDate" wire:loading.attr="disabled" wire:target="saveEstimatedDate">{{ __('Save') }}</button>
                    </div>
                    @error('reviewEstimatedDate') <span class="form-error">{{ $message }}</span> @enderror
                    @elseif ($viewingRequest['canReview'])
                    <p style="margin:2px 0 0;">{{ $viewingRequest['estimatedResolutionDate'] ?? '—' }}</p>
                    <button type="button" class="btn btn-secondary" style="font-size:12px; padding:4px 10px; margin-top:6px;" wire:click="$set('editingResolutionDate', true)">{{ __('Change resolution date') }}</button>
                    @else
                    <p style="margin:2px 0 0;">{{ $viewingRequest['estimatedResolutionDate'] ?? '—' }}</p>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div class="form-field">
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px 24px;">
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Student') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['student'] }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Type') }}</span>
                    <p style="margin:2px 0 0;">{{ __('Course Validation') }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Course') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['course'] }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Origin institution') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['originInstitution'] ?? '—' }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('External course name') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['externalCourse'] ?? '—' }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Received date') }}</span>
                    <p style="margin:2px 0 0;">{{ $viewingRequest['submittedAt'] ? date('Y-m-d', strtotime($viewingRequest['submittedAt'])) : '—' }}</p>
                </div>
                <div>
                    {{-- Unlike Requirement Waiver, Validation has no
                         automated engine outcome — every course validation
                         always goes to a human reviewer (the Comisión
                         Técnica), so this cell is a fixed label rather than
                         $viewingRequest['engineResult']. --}}
                    <span style="font-size:12px; opacity:.6;">{{ __('Engine result') }}</span>
                    <p style="margin:2px 0 0;">{{ __('Requires Committee review') }}</p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;">{{ __('Current status') }}</span>
                    <p style="margin:2px 0 0;">
                        <span class="status-badge {{ match(true) {
                                in_array($viewingRequest['status'], ['Approved by Docencia', 'Approved by Registro'], true) => 'positive',
                                in_array($viewingRequest['status'], ['Denied by Docencia', 'Denied by Registro'], true) => 'negative',
                                default => 'pending',
                            } }}">{{ __($viewingRequest['status']) }}</span>
                    </p>
                </div>
                <div>
                    <span style="font-size:12px; opacity:.6;" id="validationEstimatedDateLabel">{{ __('Resolution date') }}</span>
                    @if ($viewingRequest['canReview'] && $editingResolutionDate)
                    <div style="display:flex; gap:8px; align-items:flex-start; margin-top:2px;">
                        <input type="date" aria-labelledby="validationEstimatedDateLabel" wire:model="reviewEstimatedDate" style="flex:1;" class="{{ $errors->has('reviewEstimatedDate') ? 'has-error' : '' }}">
                        <button type="button" class="btn btn-secondary" wire:click="saveEstimatedDate" wire:loading.attr="disabled" wire:target="saveEstimatedDate">{{ __('Save') }}</button>
                    </div>
                    @error('reviewEstimatedDate') <span class="form-error">{{ $message }}</span> @enderror
                    @elseif ($viewingRequest['canReview'])
                    <p style="margin:2px 0 0;">{{ $viewingRequest['estimatedResolutionDate'] ?? '—' }}</p>
                    <button type="button" class="btn btn-secondary" style="font-size:12px; padding:4px 10px; margin-top:6px;" wire:click="$set('editingResolutionDate', true)">{{ __('Change resolution date') }}</button>
                    @else
                    <p style="margin:2px 0 0;">{{ $viewingRequest['estimatedResolutionDate'] ?? '—' }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="form-field">
            <label>{{ __('Courses to validate') }}</label>

            <div style="overflow-x:auto; border:1px solid var(--border); border-radius:10px;">
                <div style="min-width:760px;">
                    <div class="data-row data-row-head" role="row" style="--table-cols: 1.3fr 1.5fr 1.3fr 0.8fr 0.7fr 0.6fr 0.9fr; gap:14px;">
                        <span>{{ __('UTN course') }}</span>
                        <span>{{ __('External course name') }}</span>
                        <span>{{ __('Origin institution') }}</span>
                        <span>{{ __('Code') }}</span>
                        <span>{{ __('Credits') }}</span>
                        <span>{{ __('Grade') }}</span>
                        <span>{{ __('Resolution') }}</span>
                    </div>
                    <div class="data-row" role="row" style="--table-cols: 1.3fr 1.5fr 1.3fr 0.8fr 0.7fr 0.6fr 0.9fr; gap:14px;">
                        <span>{{ $viewingRequest['course'] }}</span>
                        <span>{{ $viewingRequest['externalCourse'] ?? '—' }}</span>
                        <span>{{ $viewingRequest['originInstitution'] ?? '—' }}</span>
                        <span>
                            @if ($viewingRequest['canReview'])
                            <input type="text" aria-label="{{ __('Code') }}" wire:model="viewingExternalCourseCode" class="{{ $errors->has('viewingExternalCourseCode') ? 'has-error' : '' }}" style="width:100%; font-size:13px; padding:4px 6px;">
                            @error('viewingExternalCourseCode') <span class="form-error" style="font-size:11px;">{{ $message }}</span> @enderror
                            @else
                            {{ $viewingRequest['externalCourseCode'] ?? '—' }}
                            @endif
                        </span>
                        <span>
                            @if ($viewingRequest['canReview'])
                            <input type="number" min="0" max="255" aria-label="{{ __('Credits') }}" wire:model="viewingExternalCourseCredits" class="{{ $errors->has('viewingExternalCourseCredits') ? 'has-error' : '' }}" style="width:100%; font-size:13px; padding:4px 6px;">
                            @error('viewingExternalCourseCredits') <span class="form-error" style="font-size:11px;">{{ $message }}</span> @enderror
                            @else
                            {{ $viewingRequest['externalCourseCredits'] ?? '—' }}
                            @endif
                        </span>
                        <span>
                            @if ($viewingRequest['canReview'])
                            <input type="number" min="0" max="100" step="0.01" aria-label="{{ __('Grade') }}" wire:model="viewingExternalCourseGrade" class="{{ $errors->has('viewingExternalCourseGrade') ? 'has-error' : '' }}" style="width:100%; font-size:13px; padding:4px 6px;">
                            @error('viewingExternalCourseGrade') <span class="form-error" style="font-size:11px;">{{ $message }}</span> @enderror
                            @else
                            {{ $viewingRequest['externalCourseGrade'] ?? '—' }}
                            @endif
                        </span>
                        @php
                            // Prefer the staged (not-yet-sent) decision over
                            // the real persisted status, so this badge
                            // previews what "Resolver y enviar a Registro"
                            // is about to commit.
                            $resolutionPreviewStatus = $stagedValidationDecision ?? $viewingRequest['status'];
                        @endphp
                        <span>
                            <span class="status-badge {{ match(true) {
                                    in_array($resolutionPreviewStatus, ['Approved by Docencia', 'Approved by Registro'], true) => 'positive',
                                    in_array($resolutionPreviewStatus, ['Denied by Docencia', 'Denied by Registro'], true) => 'negative',
                                    default => 'pending',
                                } }}">{{ match(true) {
                                    in_array($resolutionPreviewStatus, ['Approved by Docencia', 'Approved by Registro'], true) => __('Recognized'),
                                    in_array($resolutionPreviewStatus, ['Denied by Docencia', 'Denied by Registro'], true) => __('Not recognized'),
                                    default => __('Pending'),
                                } }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Marking Reconocer/No reconocer here only stages the
                 decision (markValidationDecision() — no DB write, no
                 email, no history row) so the Resolución badge above
                 updates immediately as a preview. Nothing is actually
                 sent to Registro until "Resolver y enviar a Registro" is
                 confirmed further down — that single action is what
                 finally calls changeStatus() with whatever was staged.
                 Docencia-only: Registro (canFinalize) never re-decides
                 Reconocer/No reconocer — its own actions are the "Nuevo
                 estado" buttons and the "Emitir resolución" panel below. --}}
            @if ($viewingRequest['canReview'] && ! $viewingRequest['canFinalize'])
            <div style="display:flex; gap:10px; align-items:flex-start; margin-top:10px;">
                <div class="form-field" style="gap:4px; flex:1;">
                    <textarea id="viewingCourseReason" wire:model="reviewComment" rows="1" placeholder="{{ __('Reason, required to not recognize this course') }}" class="{{ $errors->has('viewingCourseReason') ? 'has-error' : '' }}"></textarea>
                    @error('viewingCourseReason') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <button type="button" class="btn {{ $stagedValidationDecision === 'Approved by Docencia' ? 'btn-primary' : 'btn-secondary' }}" wire:click="markValidationDecision('Approved by Docencia')" wire:loading.attr="disabled" wire:target="markValidationDecision">{{ __('Recognize') }}</button>
                <button type="button" class="btn {{ $stagedValidationDecision === 'Denied by Docencia' ? 'btn-orange' : 'btn-secondary' }}" wire:click="markValidationDecision('Denied by Docencia')" wire:loading.attr="disabled" wire:target="markValidationDecision">{{ __('Do not recognize') }}</button>
            </div>
            @endif

        </div>
        @if ($viewingRequest['precedentResolution'])
        <div class="form-field">
            <div class="status-badge positive" style="display:inline-flex;">
                {{ __('Approved precedent found in the historical catalog') }} — {{ __('Reference resolution') }}: {{ $viewingRequest['precedentResolution'] }}
            </div>
        </div>
        @endif
        @if ($viewingRequest['canReview'] && ! $viewingRequest['canFinalize'])
        <div class="form-field">
            <div style="border:1px solid var(--border); border-radius:10px; padding:16px;">
                <label>{{ __('Send to Registro for publication') }}</label>
                <p style="opacity:.6; font-size:12.5px; margin:2px 0 10px;">{{ __('The decision comes from Reconocer/No reconocer above. Registro publishes it; it cannot be changed afterward.') }}</p>
                <button type="button" class="btn btn-primary" @click="askConfirmStatusChange('{{ $stagedValidationDecision }}')" @if ($stagedValidationDecision === null) disabled @endif wire:loading.attr="disabled" wire:target="changeStatus">{{ __('Resolve and send to Registro') }}</button>
            </div>
        </div>
        @endif
        @endif
        @if ($viewingRequest['canReview'])
        @if ($viewingRequest['canFinalize'])
        {{-- Registro's manual "Nuevo estado" 3-button picker (Verificada/
             Aprobada/Denegada por Registro) was removed — "Emitir
             resolución" below is now the only closing action, so a
             second, redundant way to set the same final status (one
             that skipped the resolution document + email + academic
             credit entirely) no longer belongs here. --}}
        @if (in_array($viewingRequest['status'], ['Approved by Docencia', 'Denied by Docencia'], true))
        {{-- RSREC-001: Registro's actual closing action. "Docencia
             resolvió: ..." below is context only — Registro's own
             Publicar y Aprobar/Denegar buttons are the real decision,
             confirmed via the del-overlay dialog (askConfirmIssueResolution())
             before issueResolution() runs. That call generates and
             archives the resolution document on this request and
             registers the academic credit on approval — no email is
             sent, the student checks the outcome by logging into the
             system. The four resolution fields below are required
             server-side (issueResolution()'s $this->validate()) — Registro
             cannot publish without filling all of them. Only offered
             while the request is in the "Docencia decided, Registro
             hasn't" state — once final, the request leaves this bandeja
             for the Historial tab. --}}
        <div class="form-field">
            <div style="border:1px solid var(--border); border-radius:10px; padding:16px;">
                <label>{{ __('Issue resolution') }} (RSREC-001)</label>
                <p style="margin:8px 0 12px; display:flex; align-items:center; gap:8px;">
                    {{ __('Docencia resolved:') }}
                    <span class="status-badge {{ $viewingRequest['status'] === 'Approved by Docencia' ? 'positive' : 'negative' }}">
                        {{ $viewingRequest['status'] === 'Approved by Docencia' ? __('Approved') : __('Denied') }}
                    </span>
                </p>
                <div style="display:grid; grid-template-columns:1.2fr 1fr 1fr 1fr; gap:12px;">
                    <div class="form-field" style="gap:4px;">
                        <label for="resolutionNumber" style="font-size:12px;">{{ __('Resolution number') }}</label>
                        <input type="text" id="resolutionNumber" wire:model="resolutionNumber" class="{{ $errors->has('resolutionNumber') ? 'has-error' : '' }}">
                        @error('resolutionNumber') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field" style="gap:4px;">
                        <label for="resolutionSessionNumber" style="font-size:12px;">{{ __('Session No.') }}</label>
                        <input type="text" id="resolutionSessionNumber" wire:model="resolutionSessionNumber" class="{{ $errors->has('resolutionSessionNumber') ? 'has-error' : '' }}">
                        @error('resolutionSessionNumber') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field" style="gap:4px;">
                        <label for="resolutionActNumber" style="font-size:12px;">{{ __('Act No.') }}</label>
                        <input type="text" id="resolutionActNumber" wire:model="resolutionActNumber" class="{{ $errors->has('resolutionActNumber') ? 'has-error' : '' }}">
                        @error('resolutionActNumber') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-field" style="gap:4px;">
                        <label for="resolutionSessionDate" style="font-size:12px;">{{ __('Session date') }}</label>
                        <input type="date" id="resolutionSessionDate" wire:model="resolutionSessionDate" class="{{ $errors->has('resolutionSessionDate') ? 'has-error' : '' }}">
                        @error('resolutionSessionDate') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:14px;">
                    <button type="button" class="btn btn-primary" @click="askConfirmIssueResolution('Approved by Registro')" wire:loading.attr="disabled" wire:target="issueResolution">{{ __('Publish and approve the resolution') }}</button>
                    <button type="button" class="btn btn-orange" @click="askConfirmIssueResolution('Denied by Registro')" wire:loading.attr="disabled" wire:target="issueResolution">{{ __('Publish and deny the resolution') }}</button>
                </div>
            </div>
        </div>
        @endif
        @elseif ($reviewingType === 'Requirement Waiver')
        {{--
            Docencia's decision on a Requirement Waiver, simplified to
            the two outcomes that actually matter: no more manually
            toggling through Pending Review / In Review first — one
            click both decides and hands it off to Registro. Denying
            still requires the Comment field below (changeStatus()'s
            existing rule), unchanged.
        --}}
        <div class="form-field" style="border-top:1px solid var(--border); padding-top:14px;">
            <label>{{ __('Send to Registro for publication') }}</label>
            <p style="opacity:.6; font-size:12.5px; margin:2px 0 10px;">{{ __('Registro publishes whatever you decide here; it cannot be changed afterward.') }}</p>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <button type="button" class="btn btn-primary" @click="askConfirmStatusChange('Approved by Docencia')" wire:loading.attr="disabled" wire:target="changeStatus">{{ __('Approve and send to Registro') }}</button>
                <button type="button" class="btn btn-orange" @click="askConfirmStatusChange('Denied by Docencia')" wire:loading.attr="disabled" wire:target="changeStatus">{{ __('Deny and send to Registro') }}</button>
            </div>
        </div>
        @endif
        @endif
        {{-- Docencia reviewing a Course Validation has no "Nuevo estado"
             block at all any more: Reconocer/No reconocer + "Resolver y
             enviar a Registro" above already set Approved/Denied by
             Docencia by themselves, so a second manual status picker
             here would be redundant. --}}
        {{-- The resolution date's edit control now lives inline in the
             summary grid above (Resolution date cell) for both types, via
             the "Cambiar fecha de resolución" toggle — no separate block
             needed here any more. --}}
        @if ($viewingRequest['canReview'])
        <div class="form-field">
            <label for="reviewComment">{{ __('Additional comment') }}</label>
            <textarea id="reviewComment" wire:model="reviewComment" class="{{ $errors->has('reviewComment') ? 'has-error' : '' }}"></textarea>
            @error('reviewComment') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        @endif
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
                           @click.prevent="window.open($el.href, 'documentPreview', 'width=900,height=750,resizable=yes,scrollbars=yes,noopener,noreferrer')"
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
            {{-- Docencia's own "attach a supporting document while
                 reviewing" step — available for both types; a
                 Validation reviewer needs this just as much as a
                 Waiver reviewer (e.g. attaching the foreign transcript
                 or a signed resolution). Registro gets a stripped-down
                 version instead: no "Tipo de documento" (it's never
                 shown anywhere, so a free-text label for it added
                 nothing) and no separate upload step — the file just
                 stays selected and is moved to permanent storage
                 together with the resolution when "Publicar y Aprobar/
                 Denegar" is confirmed (see issueResolution()), the same
                 "pick now, persist on the real action" pattern already
                 used for Validation's external course fields. --}}
            @if ($viewingRequest['canReview'] && ! $viewingRequest['canFinalize'])
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
            @elseif ($viewingRequest['canFinalize'] && in_array($viewingRequest['status'], ['Approved by Docencia', 'Denied by Docencia'], true))
            <div style="border-top:1px solid var(--border); padding-top:12px; margin-top:4px;">
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
                    <p style="opacity:.6; font-size:12.5px; margin-top:4px;">{{ __('Uploaded together with the resolution when you publish it above.') }}</p>
                </div>
            </div>
            @endif
        </div>
        <div class="form-field">
            <label>{{ __('Status history') }}</label>
            @if (count($viewingRequest['statusHistory']) === 0)
            <p style="opacity:.6;">{{ __('No status changes recorded yet.') }}</p>
            @else
            @php
                // Validation reaches 'Approved by Docencia'/'Denied by
                // Docencia' through Reconocer/No reconocer rather than an
                // Aprobar/Denegar action, so the history reuses the same
                // "Reconocido"/"No reconocido" wording as the table's
                // Resolución badge instead of the raw status label — for
                // both slots an entry can appear in (newStatus, and a
                // later row's previousStatus).
                $historyStatusLabel = function (?string $status) use ($viewingRequest): string {
                    if ($status === null) {
                        return __('(new)');
                    }

                    if ($viewingRequest['type'] === 'Validation') {
                        $status = match ($status) {
                            'Approved by Docencia' => 'Recognized by Docencia',
                            'Denied by Docencia' => 'Not recognized by Docencia',
                            default => $status,
                        };
                    }

                    return __($status);
                };
            @endphp
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach ($viewingRequest['statusHistory'] as $entry)
                <div style="border-left:2px solid var(--border); padding-left:10px;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="font-size:13px;">{{ $historyStatusLabel($entry['previousStatus']) }} → {{ $historyStatusLabel($entry['newStatus']) }}</span>
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
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px 24px; font-size:13px;">
                    <div><strong>{{ __('Student') }}:</strong> {{ $viewingRequest['studentRecord']['fullName'] }}</div>
                    <div style="grid-row:span 2;">
                        @foreach ($viewingRequest['studentRecord']['studyPlans'] as $plan)
                        <div><strong>{{ __('Career') }}:</strong> {{ $plan['career'] ?? '—' }}</div>
                        @endforeach
                    </div>
                    <div><strong>{{ __('National ID') }}:</strong> {{ $viewingRequest['studentRecord']['nationalId'] }}</div>
                </div>

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
                    <div style="min-width:640px;">
                        <div class="data-row data-row-head" role="row" style="--table-cols: 0.8fr 2.2fr 0.7fr 0.9fr 0.7fr 1fr;">
                            <span>{{ __('Code') }}</span>
                            <span>{{ __('Subject') }}</span>
                            <span>{{ __('Grade') }}</span>
                            <span>{{ __('Period') }}</span>
                            <span>{{ __('Credits') }}</span>
                            <span>{{ __('Status') }}</span>
                        </div>
                        @foreach ($viewingRequest['studentRecord']['courses'] as $course)
                        <div class="data-row" role="row" style="--table-cols: 0.8fr 2.2fr 0.7fr 0.9fr 0.7fr 1fr;">
                            <span>{{ $course['code'] }}</span>
                            <span>{{ $course['name'] }}</span>
                            <span>{{ $course['grade'] ?? '—' }}</span>
                            <span>{{ $course['period'] }}</span>
                            <span>{{ $course['credits'] ?? '—' }}</span>
                            <span>
                                <span class="status-badge {{ match(true) {
                                        in_array($course['status'], ['Approved', 'Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'], true) => 'positive',
                                        $course['status'] === 'Failed' => 'negative',
                                        default => '',
                                    } }}">{{ __($course['status']) }}</span>
                            </span>
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

    <div class="del-overlay" :class="{ 'open': confirmStatusChange.open }">
        <div class="del-card">
            <div class="del-icon-warn">!</div>
            <p class="del-title">{{ __('Warning') }}</p>
            <p class="del-text">{{ __('Are you sure? This will be sent to Registro and cannot be changed afterward.') }}</p>
            <div class="del-actions">
                <button type="button" class="del-btn-confirm" @click="runConfirmStatusChange()">{{ __('Yes, continue') }}</button>
                <button type="button" class="del-btn-cancel" @click="closeConfirmStatusChange()">{{ __('Cancel') }}</button>
            </div>
        </div>
    </div>

    <div class="del-overlay" :class="{ 'open': confirmIssueResolution.open }">
        <div class="del-card">
            <div class="del-icon-warn">!</div>
            <p class="del-title">{{ __('Warning') }}</p>
            <p class="del-text">{{ __('Are you sure you want to issue this resolution? It will be published and sent to the student, and cannot be changed afterward.') }}</p>
            <div class="del-actions">
                <button type="button" class="del-btn-confirm" @click="runConfirmIssueResolution()">{{ __('Yes, continue') }}</button>
                <button type="button" class="del-btn-cancel" @click="closeConfirmIssueResolution()">{{ __('Cancel') }}</button>
            </div>
        </div>
    </div>

    <x-ui.success-modal :show="$showSentToRegistroModal" :title="__('Successfully completed')" close-action="closeSentToRegistroModal">
        {{ __('Sent to Registro for publication.') }}
    </x-ui.success-modal>

    <x-ui.success-modal :show="$showResolutionPublishedModal" :title="__('Successfully completed')" close-action="closeResolutionPublishedModal">
        {{ __('The resolution was published successfully.') }}
    </x-ui.success-modal>
</div>
