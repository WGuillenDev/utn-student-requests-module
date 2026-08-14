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
        <div style="padding:16px; display:flex; flex-direction:column; gap:16px; max-width:520px;">
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
        <div style="padding:16px; display:flex; flex-direction:column; gap:16px; max-width:520px;">
            <div class="form-field">
                <label for="validationCourse">{{ __('Equivalent internal course') }}</label>
                <select id="validationCourse" wire:model="validationForm.courseId" class="{{ $errors->has('validationForm.courseId') ? 'has-error' : '' }}">
                    <option value="">{{ __('Select a course') }}</option>
                    @foreach ($courseOptions as $option)
                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('validationForm.courseId') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-field">
                <label for="validationInstitution">{{ __('Origin institution') }}</label>
                <input type="text" id="validationInstitution" wire:model="validationForm.originInstitution" class="{{ $errors->has('validationForm.originInstitution') ? 'has-error' : '' }}">
                @error('validationForm.originInstitution') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-field">
                <label for="validationExternalCourse">{{ __('External course name') }}</label>
                <input type="text" id="validationExternalCourse" wire:model="validationForm.externalCourse" class="{{ $errors->has('validationForm.externalCourse') ? 'has-error' : '' }}">
                @error('validationForm.externalCourse') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-field">
                <label for="validationExternalProgram">{{ __('External course syllabus') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
                @if ($validationForm->externalProgramFile && ! $errors->has('validationForm.externalProgramFile'))
                    <div class="file-chip">
                        <span class="file-chip-name">{{ $validationForm->externalProgramFile->getClientOriginalName() }}</span>
                        <button type="button" class="file-chip-remove" wire:click="removeFile('validationForm.externalProgramFile')" aria-label="{{ __('Remove file') }}">&times;</button>
                    </div>
                @else
                    <div
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="
                            dragging = false;
                            $refs.validationExternalProgram.files = $event.dataTransfer.files;
                            $refs.validationExternalProgram.dispatchEvent(new Event('change'));
                        "
                        :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                    >
                        <input type="file" id="validationExternalProgram" x-ref="validationExternalProgram" wire:model="validationForm.externalProgramFile" class="{{ $errors->has('validationForm.externalProgramFile') ? 'has-error' : '' }}">
                        <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                    </div>
                @endif
                @error('validationForm.externalProgramFile')
                    <span class="form-error">{{ $message }}</span>
                @elseif ($validationForm->externalProgramFile)
                    <span class="form-success">{{ __('File attached') }}</span>
                @enderror
            </div>

            <div class="form-field">
                <label for="validationGradeCertification">{{ __('Grade certification') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
                @if ($validationForm->gradeCertificationFile && ! $errors->has('validationForm.gradeCertificationFile'))
                    <div class="file-chip">
                        <span class="file-chip-name">{{ $validationForm->gradeCertificationFile->getClientOriginalName() }}</span>
                        <button type="button" class="file-chip-remove" wire:click="removeFile('validationForm.gradeCertificationFile')" aria-label="{{ __('Remove file') }}">&times;</button>
                    </div>
                @else
                    <div
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="
                            dragging = false;
                            $refs.validationGradeCertification.files = $event.dataTransfer.files;
                            $refs.validationGradeCertification.dispatchEvent(new Event('change'));
                        "
                        :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                    >
                        <input type="file" id="validationGradeCertification" x-ref="validationGradeCertification" wire:model="validationForm.gradeCertificationFile" class="{{ $errors->has('validationForm.gradeCertificationFile') ? 'has-error' : '' }}">
                        <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                    </div>
                @endif
                @error('validationForm.gradeCertificationFile')
                    <span class="form-error">{{ $message }}</span>
                @elseif ($validationForm->gradeCertificationFile)
                    <span class="form-success">{{ __('File attached') }}</span>
                @enderror
            </div>

            <div class="form-field">
                <label for="validationInstitutionProof">{{ __('Institution proof') }} <span style="opacity:.6;">({{ __('PDF or image, max. 5MB') }})</span></label>
                @if ($validationForm->institutionProofFile && ! $errors->has('validationForm.institutionProofFile'))
                    <div class="file-chip">
                        <span class="file-chip-name">{{ $validationForm->institutionProofFile->getClientOriginalName() }}</span>
                        <button type="button" class="file-chip-remove" wire:click="removeFile('validationForm.institutionProofFile')" aria-label="{{ __('Remove file') }}">&times;</button>
                    </div>
                @else
                    <div
                        x-data="{ dragging: false }"
                        x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="
                            dragging = false;
                            $refs.validationInstitutionProof.files = $event.dataTransfer.files;
                            $refs.validationInstitutionProof.dispatchEvent(new Event('change'));
                        "
                        :class="dragging ? 'dropzone dropzone-active' : 'dropzone'"
                    >
                        <input type="file" id="validationInstitutionProof" x-ref="validationInstitutionProof" wire:model="validationForm.institutionProofFile" class="{{ $errors->has('validationForm.institutionProofFile') ? 'has-error' : '' }}">
                        <p class="dropzone-hint">{{ __('or drag a file here') }}</p>
                    </div>
                @endif
                @error('validationForm.institutionProofFile')
                    <span class="form-error">{{ $message }}</span>
                @elseif ($validationForm->institutionProofFile)
                    <span class="form-success">{{ __('File attached') }}</span>
                @enderror
            </div>

            <div>
                <button type="button" class="btn btn-primary" wire:click="submitValidation" wire:loading.attr="disabled" wire:target="submitValidation,validationForm.externalProgramFile,validationForm.gradeCertificationFile,validationForm.institutionProofFile">
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
                ['key' => 'result', 'label' => __('Result'), 'sortable' => false],
                ['key' => 'estimated_resolution_date', 'label' => __('Estimated date'), 'sortable' => true],
            ]"
        mode="server"
        :rows="[]"
        :searchable="[]"
        :paginator="$requests ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1.6fr 2fr 1.4fr 1.6fr 1.2fr 1fr"
        :can-create="false"
        :can-search="false"
        :can-export-pdf="false"
        :can-export-excel="false"
        :title="__('My requests')">

        @forelse ($requests as $row)
        <div class="data-row" role="row">
            <span>{{ __($row['type']) }}</span>
            <span>{{ $row['course'] }}</span>
            <span>
                <span class="status-badge {{ $row['statusVariant'] }}">{{ __($row['status']) }}</span>
            </span>
            <span>{{ $row['result'] ? __($row['result']) : __('Pending') }}</span>
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
        <p>{{ __('Type') }}: {{ __($successType) }}</p>
        <p>{{ __('Course') }}: {{ $successCourse }}</p>
        <span class="status-badge pending">{{ __('Pending Review') }}</span>
    </x-ui.success-modal>

    <x-ui.modal :show="$showViewModal" :title="__('Request detail')" close-action="closeViewModal">
        @if ($viewingRequest)
        <div class="form-field">
            <label>{{ __('Type') }}</label>
            <p>{{ __($viewingRequest['type']) }}</p>
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
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeViewModal">{{ __('Close') }}</button>
        </x-slot:footer>
    </x-ui.modal>
</div>
