<div>
    <div class="card" style="margin-bottom:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; padding:16px;">
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn {{ $activeTab === 'waiver' ? 'btn-primary' : 'btn-secondary' }}" wire:click="setTab('waiver')">
                    {{ __('New waiver request') }}
                </button>
                <button type="button" class="btn {{ $activeTab === 'validation' ? 'btn-primary' : 'btn-secondary' }}" wire:click="setTab('validation')">
                    {{ __('New validation request') }}
                </button>
            </div>
            <button type="button" class="btn {{ $activeTab === 'my-requests' ? 'btn-primary' : 'btn-secondary' }}" wire:click="setTab('my-requests')">
                {{ __('My requests') }}
            </button>
        </div>
    </div>

    @if ($activeTab === 'waiver')
    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('New waiver request') }}</span>
        </div>
        <div style="padding:16px; display:flex; flex-direction:column; gap:16px; max-width:640px;">
            <div class="form-field">
                <label for="waiverCourse">{{ __('Course to enroll') }}</label>
                <select id="waiverCourse" wire:model="waiverForm.courseId" class="{{ $errors->has('waiverForm.courseId') ? 'has-error' : '' }}">
                    <option value="">{{ __('Select a course') }}</option>
                    @foreach ($courseOptions as $option)
                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('waiverForm.courseId') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-field">
                <label for="waiverRequiredCourse">{{ __('Course requirement not met') }}</label>
                <select id="waiverRequiredCourse" wire:model="waiverForm.requiredCourseId" class="{{ $errors->has('waiverForm.requiredCourseId') ? 'has-error' : '' }}">
                    <option value="">{{ __('Select a course') }}</option>
                    @foreach ($courseOptions as $option)
                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('waiverForm.requiredCourseId') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-field">
                <label>{{ __('Justification') }} <span style="opacity:.6;">({{ __('Select only one option') }})</span></label>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach (\Src\Requests\Request\Presentation\Livewire\Forms\WaiverRequestForm::JUSTIFICATIONS as $letter => $option)
                    <label
                        class="option-card"
                        style="display:flex; align-items:flex-start; gap:10px; border:1px solid var(--border); border-radius:10px; padding:14px 16px; cursor:pointer;"
                    >
                        <input type="radio" wire:model="waiverForm.justification" value="{{ $option }}" style="margin-top:3px; flex-shrink:0;">
                        <span><strong>{{ chr(97 + $letter) }}.</strong> {{ __($option) }}</span>
                    </label>
                    @endforeach
                </div>
                @error('waiverForm.justification') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-field">
                <label for="waiverSupportDocument">{{ __('Supporting document') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
                @if ($waiverForm->supportDocument && ! $errors->has('waiverForm.supportDocument'))
                    <div class="file-chip">
                        <span class="file-chip-name">{{ $waiverForm->supportDocument->getClientOriginalName() }}</span>
                        <button type="button" class="file-chip-remove" wire:click="removeFile('waiverForm.supportDocument')" aria-label="{{ __('Remove file') }}">&times;</button>
                    </div>
                @else
                    <div
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="
                            dragging = false;
                            $refs.waiverSupportDocument.files = $event.dataTransfer.files;
                            $refs.waiverSupportDocument.dispatchEvent(new Event('change'));
                        "
                        :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                    >
                        <input type="file" id="waiverSupportDocument" x-ref="waiverSupportDocument" wire:model="waiverForm.supportDocument" class="{{ $errors->has('waiverForm.supportDocument') ? 'has-error' : '' }}">
                        <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                    </div>
                @endif
                @error('waiverForm.supportDocument')
                    <span class="form-error">{{ $message }}</span>
                @elseif ($waiverForm->supportDocument)
                    <span class="form-success">{{ __('File attached') }}</span>
                @enderror
            </div>

            <div style="background:var(--actionEditBg); color:var(--actionEditText); border-radius:10px; padding:16px; display:flex; flex-direction:column; gap:10px;">
                <strong>{{ __('Important notes') }}</strong>
                <p style="margin:0;">{{ __('You must enroll in the course for which you are requesting the requirement waiver in the same term this request is approved. Administrative Directive DA-VDOC-01-2020.') }}</p>
                <p style="margin:0;">{{ __('If you fail the course for which you requested the waiver, you will not be able to request a waiver for that course again. Administrative Directive DA-VDOC-01-2020.') }}</p>
                <label class="permission-item" style="color:inherit; font-weight:600;">
                    <input type="checkbox" wire:model="waiverForm.noticeAccepted">
                    <span>{{ __('I have read and accept the important notes above.') }}</span>
                </label>
                @error('waiverForm.noticeAccepted') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <button type="button" class="btn btn-primary" wire:click="submitWaiver" wire:loading.attr="disabled" wire:target="submitWaiver,waiverForm.supportDocument">
                    {{ __('Submit request') }}
                </button>
            </div>
        </div>
    </div>
    @elseif ($activeTab === 'validation')
    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('New validation request') }}</span>
        </div>
        <div style="padding:16px; display:flex; flex-direction:column; gap:16px;">
            <div>
                <p style="font-weight:700; margin-bottom:4px;">{{ __('Courses to validate') }}</p>
                <p style="opacity:.6; font-size:13px; margin:0;">{{ __('You can request one or several courses in the same submission (up to :max).', ['max' => \Src\Requests\Request\Presentation\Livewire\Forms\ValidationRequestForm::MAX_COURSES]) }}</p>
            </div>

            <div class="table-scroll">
                <div class="table-inner" style="--table-cols: 1.4fr 1.6fr 1.6fr 56px; min-width:640px;">
                    <div class="data-row-head">
                        <span>{{ __('UTN course') }}</span>
                        <span>{{ __('External course name') }}</span>
                        <span>{{ __('Origin university') }}</span>
                        <span></span>
                    </div>
                    @foreach ($validationForm->courses as $index => $course)
                    <div class="data-row" wire:key="validation-course-row-{{ $index }}" style="align-items:start;">
                        <div class="form-field" style="margin:0;">
                            <select wire:model="validationForm.courses.{{ $index }}.courseId" class="{{ $errors->has("validationForm.courses.$index.courseId") ? 'has-error' : '' }}">
                                <option value="">{{ __('Select a course') }}</option>
                                @foreach ($courseOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error("validationForm.courses.$index.courseId") <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-field" style="margin:0;">
                            <input type="text" placeholder="{{ __('Eg. Programming I') }}" wire:model="validationForm.courses.{{ $index }}.externalCourse" class="{{ $errors->has("validationForm.courses.$index.externalCourse") ? 'has-error' : '' }}">
                            @error("validationForm.courses.$index.externalCourse") <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-field" style="margin:0;">
                            <input type="text" placeholder="{{ __('Eg. University of Costa Rica') }}" wire:model="validationForm.courses.{{ $index }}.originInstitution" class="{{ $errors->has("validationForm.courses.$index.originInstitution") ? 'has-error' : '' }}">
                            @error("validationForm.courses.$index.originInstitution") <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                        <button type="button" class="btn btn-secondary" wire:click="removeValidationCourse({{ $index }})" @disabled(count($validationForm->courses) <= 1) aria-label="{{ __('Remove course') }}">&times;</button>
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <button type="button" class="btn btn-secondary" wire:click="addValidationCourse" @disabled(count($validationForm->courses) >= \Src\Requests\Request\Presentation\Livewire\Forms\ValidationRequestForm::MAX_COURSES)>
                    + {{ __('Add course') }}
                </button>
            </div>

            <div class="form-field">
                <label>{{ __('Required documents') }}: <span style="opacity:.6;">({{ __('PDF, JPG or PNG · Max. 10MB each') }})</span></label>

                @foreach ($validationForm->documents as $index => $document)
                <div class="file-chip" wire:key="validation-document-chip-{{ $index }}">
                    <span class="file-chip-name">{{ $document->getClientOriginalName() }}</span>
                    <button type="button" class="file-chip-remove" wire:click="removeValidationDocument({{ $index }})" aria-label="{{ __('Remove file') }}">&times;</button>
                </div>
                @endforeach

                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="
                        dragging = false;
                        $refs.validationDocuments.files = $event.dataTransfer.files;
                        $refs.validationDocuments.dispatchEvent(new Event('change'));
                    "
                    :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                >
                    <input type="file" multiple x-ref="validationDocuments" wire:model="validationForm.documents" class="{{ $errors->has('validationForm.documents') ? 'has-error' : '' }}">
                    <p class="dropzone-hint">{{ __('Upload the files your request needs, or drag them here') }}</p>
                </div>
                @error('validationForm.documents') <span class="form-error">{{ $message }}</span> @enderror
                @error('validationForm.documents.*') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <button type="button" class="btn btn-primary" wire:click="submitValidation" wire:loading.attr="disabled" wire:target="submitValidation,validationForm.documents">
                    {{ __('Submit request') }}
                </button>
            </div>
        </div>
    </div>
    @else
    <x-ui.data-table
        :headers="[
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'course', 'label' => __('Course'), 'sortable' => false],
                ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
                ['key' => 'estimated_resolution_date', 'label' => __('Estimated date'), 'sortable' => true],
            ]"
        mode="server"
        :rows="[]"
        :searchable="[]"
        :paginator="$requests ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1.6fr 2fr 1.4fr 1.2fr 1fr"
        :can-create="false"
        :can-search="false"
        :can-export-pdf="false"
        :can-export-excel="false"
        :title="__('My requests')">

        @forelse ($requests as $row)
        <div class="data-row" role="row">
            <span>{{ match ($row['type']) {
                    'Requirement Waiver' => __('Requirement Waiver'),
                    'Validation' => __('Course Validation'),
                    default => $row['type'],
                } }}</span>
            <span>{{ $row['course'] }}</span>
            <span>
                <span class="status-badge {{ $row['statusVariant'] }}">{{ __($row['status']) }}</span>
            </span>
            <span>{{ $row['estimatedDate'] ?? '—' }}</span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-view="true"
                    view-action="$wire.viewRequest({{ $row['id'] }})"
                    :can-edit="false"
                    :can-delete="false" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
    </x-ui.data-table>
    @endif

    <x-ui.success-modal :show="$showSuccessModal" :title="__('Request submitted!')" close-action="closeSuccessModal">
        <p>{{ __('Type') }}: {{ match ($successType) {
                'Requirement Waiver' => __('Requirement Waiver'),
                'Validation' => __('Course Validation'),
                default => $successType,
            } }}</p>
        <p>{{ __('Course') }}: {{ $successCourse }}</p>
        @if ($successEngineResult)
        <p>{{ __('Immediate result') }}: <strong>{{ __($successEngineResult) }}</strong></p>
        @endif
        <span class="status-badge pending">{{ __('Pending Review') }}</span>
    </x-ui.success-modal>

    <x-ui.modal :show="$showViewModal" :title="__('Request detail')" close-action="closeViewModal">
        @if ($viewingRequest)
        <div class="form-field">
            <label>{{ __('Type') }}</label>
            <p>{{ match ($viewingRequest['type']) {
                    'Requirement Waiver' => __('Requirement Waiver'),
                    'Validation' => __('Course Validation'),
                    default => $viewingRequest['type'],
                } }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Course') }}</label>
            <p>{{ $viewingRequest['course'] }}</p>
        </div>
        @if ($viewingRequest['type'] === 'Requirement Waiver')
        <div class="form-field">
            <label>{{ __('Unmet requirement') }}</label>
            <p>{{ $viewingRequest['requiredCourse'] }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Justification') }}</label>
            <p>{{ $viewingRequest['waiverJustification'] ? __($viewingRequest['waiverJustification']) : '—' }}</p>
        </div>
        @else
        <div class="form-field">
            <label>{{ __('Origin institution') }}</label>
            <p>{{ $viewingRequest['originInstitution'] }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('External course name') }}</label>
            <p>{{ $viewingRequest['externalCourse'] }}</p>
        </div>
        @endif
        <div class="form-field">
            <label>{{ __('Status') }}</label>
            <p><span class="status-badge {{ $viewingRequest['statusVariant'] }}">{{ __($viewingRequest['status']) }}</span></p>
        </div>
        <div class="form-field">
            <label>{{ __('Result') }}</label>
            <p>{{ $viewingRequest['result'] ? __($viewingRequest['result']) : __('Pending') }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Estimated date') }}</label>
            <p>{{ $viewingRequest['estimatedDate'] ?? '—' }}</p>
        </div>
        @endif
    </x-ui.modal>
</div>
