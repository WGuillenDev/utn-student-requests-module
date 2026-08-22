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
                ['key' => 'courseLabel', 'label' => __('Course'), 'sortable' => false],
                ['key' => 'order', 'label' => __('Order'), 'sortable' => true],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'active', 'label' => __('Status'), 'sortable' => true],
            ]"
        :mode="$tableMode"
        :rows="$rows ?? []"
        :searchable="['courseLabel']"
        :paginator="$waiverRules ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="2fr 0.8fr 1.8fr 0.8fr 1fr"
        :can-create="Auth::user()->can('create', \Src\Requests\WaiverRule\Domain\Entities\WaiverRule::class)"
        :can-search="Auth::user()->can('search', \Src\Requests\WaiverRule\Domain\Entities\WaiverRule::class)"
        :can-export-pdf="Auth::user()->can('exportPdf', \Src\Requests\WaiverRule\Domain\Entities\WaiverRule::class)"
        :can-export-excel="Auth::user()->can('exportExcel', \Src\Requests\WaiverRule\Domain\Entities\WaiverRule::class)"
        :title="__('Waiver Rules management')">

        @if ($tableMode === 'client')
        {{-- Client mode: Alpine renders rows from the in-browser `pageRows`
                     collection (search/sort/pagination already resolved with zero
                     server round-trips). See resources/js/data-table.js. --}}
        <template x-for="row in pageRows" :key="row.id">
            <div class="data-row" role="row">
                <span x-text="row.courseLabel"></span>
                <span x-text="row.order"></span>
                <span x-text="{
                    'Approved requirement with minimum grade': '{{ __('Approved requirement with minimum grade') }}',
                    'Accumulated credits or courses': '{{ __('Accumulated credits or courses') }}',
                    'Terminal plan membership': '{{ __('Terminal plan membership') }}',
                    'Always manual review': '{{ __('Always manual review') }}',
                }[row.type]"></span>
                <span>
                    <span class="status-badge positive" x-show="row.active">{{ __('Active') }}</span>
                    <span class="status-badge negative" x-show="!row.active">{{ __('Inactive') }}</span>
                </span>
                <div class="actions-cell">
                    <x-ui.row-actions
                        :can-view="Auth::user()->hasPermissionTo('waiver_rules.view')"
                        :can-edit="Auth::user()->hasPermissionTo('waiver_rules.edit')"
                        :can-delete="Auth::user()->hasPermissionTo('waiver_rules.delete')"
                        view-action="$wire.openViewModal(row.id)"
                        edit-action="$wire.openEditModal(row.id)"
                        delete-id="row.id" />
                </div>
            </div>
        </template>
        <div class="empty-row" x-show="pageRows.length === 0">{{ __('No records found') }}</div>
        @else
        {{-- Server mode: unchanged Livewire-driven pagination, preserved for
                     any future catalog too large to ship to the client at once. --}}
        @forelse ($waiverRules as $waiverRule)
        <div class="data-row" role="row">
            <span>{{ $waiverRule->courseId() }}</span>
            <span>{{ $waiverRule->order() }}</span>
            <span>{{ __($waiverRule->type()) }}</span>
            <span>
                @if ($waiverRule->active())
                <span class="status-badge positive">{{ __('Active') }}</span>
                @else
                <span class="status-badge negative">{{ __('Inactive') }}</span>
                @endif
            </span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-view="Auth::user()->can('view', $waiverRule)"
                    :can-edit="Auth::user()->can('update', $waiverRule)"
                    :can-delete="Auth::user()->can('delete', $waiverRule)"
                    view-action="$wire.openViewModal({{ $waiverRule->id() }})"
                    edit-action="$wire.openEditModal({{ $waiverRule->id() }})"
                    delete-id="{{ $waiverRule->id() }}" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
        @endif
    </x-ui.data-table>

    <x-ui.modal :show="$showModal" :title="$editingId === null ? __('New waiver rule') : __('Edit waiver rule')">
        <div class="form-field">
            <label for="waiverRuleCareer">{{ __('Career') }}</label>
            <select id="waiverRuleCareer" wire:model.live="filterCareerId">
                <option value="">{{ __('Select a career') }}</option>
                @foreach ($careerOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="waiverRuleCourse">{{ __('Course') }}</label>
            <select id="waiverRuleCourse" wire:model="form.courseId" class="{{ $errors->has('form.courseId') ? 'has-error' : '' }}">
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
            <label for="waiverRuleOrder">{{ __('Order') }}</label>
            <input type="number" id="waiverRuleOrder" min="1" wire:model="form.order" class="{{ $errors->has('form.order') ? 'has-error' : '' }}">
            @error('form.order') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="waiverRuleType">{{ __('Type') }}</label>
            <select id="waiverRuleType" wire:model.live="form.type">
                <option value="Approved requirement with minimum grade">{{ __('Approved requirement with minimum grade') }}</option>
                <option value="Accumulated credits or courses">{{ __('Accumulated credits or courses') }}</option>
                <option value="Terminal plan membership">{{ __('Terminal plan membership') }}</option>
                <option value="Always manual review">{{ __('Always manual review') }}</option>
            </select>
        </div>

        {{-- Conditional-by-type fields — same Alpine/Livewire pattern already
             used in the Request create modal (show/hide per selected type). --}}
        @if ($form->type === 'Approved requirement with minimum grade')
        <div class="form-field">
            <label for="waiverRuleRequiredCourse">{{ __('Required course') }}</label>
            <select id="waiverRuleRequiredCourse" wire:model="form.requiredCourseId" class="{{ $errors->has('form.requiredCourseId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a course') }}</option>
                @foreach ($courseOptions as $option)
                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            @error('form.requiredCourseId') <span class="form-error">{{ $message }}</span> @enderror
            @if ($filterCareerId === null)
            <span class="form-error">{{ __('You must select a career before choosing a course.') }}</span>
            @endif
        </div>
        <div class="form-field">
            <label for="waiverRuleMinimumGrade">{{ __('Minimum grade') }}</label>
            <input type="number" id="waiverRuleMinimumGrade" step="0.01" min="0" max="100" wire:model="form.minimumGrade" class="{{ $errors->has('form.minimumGrade') ? 'has-error' : '' }}">
            @error('form.minimumGrade') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        @elseif ($form->type === 'Accumulated credits or courses')
        <div class="form-field">
            <label for="waiverRuleMinimumAccumulated">{{ __('Minimum accumulated') }}</label>
            <input type="number" id="waiverRuleMinimumAccumulated" min="0" wire:model="form.minimumAccumulated" class="{{ $errors->has('form.minimumAccumulated') ? 'has-error' : '' }}">
            @error('form.minimumAccumulated') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        @endif

        <div class="form-field">
            <label>
                <input type="checkbox" wire:model="form.active">
                {{ __('Active') }}
            </label>
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal :show="$showViewModal" :title="__('Waiver rule detail')" close-action="closeViewModal">
        @if ($viewing)
        <div class="form-field">
            <label>{{ __('Course') }}</label>
            <p>{{ $viewing['courseLabel'] }}</p>
        </div>

        <div class="form-field">
            <label>{{ __('Order') }}</label>
            <p>{{ $viewing['order'] }}</p>
        </div>

        <div class="form-field">
            <label>{{ __('Type') }}</label>
            <p>{{ __($viewing['type']) }}</p>
        </div>

        @if ($viewing['type'] === 'Approved requirement with minimum grade')
        <div class="form-field">
            <label>{{ __('Required course') }}</label>
            <p>{{ $viewing['requiredCourseLabel'] }}</p>
        </div>
        <div class="form-field">
            <label>{{ __('Minimum grade') }}</label>
            <p>{{ $viewing['minimumGrade'] }}</p>
        </div>
        @elseif ($viewing['type'] === 'Accumulated credits or courses')
        <div class="form-field">
            <label>{{ __('Minimum accumulated') }}</label>
            <p>{{ $viewing['minimumAccumulated'] }}</p>
        </div>
        @endif

        <div class="form-field">
            <label>{{ __('Status') }}</label>
            <p>
                @if ($viewing['active'])
                <span class="status-badge positive">{{ __('Active') }}</span>
                @else
                <span class="status-badge negative">{{ __('Inactive') }}</span>
                @endif
            </p>
        </div>
        @endif
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The waiver rule has been deleted.')" />
</div>
