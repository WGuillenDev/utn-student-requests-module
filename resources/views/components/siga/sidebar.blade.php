<aside class="sidebar" x-persist="sidebar" :class="{ 'mobile-open': mobileOpen, 'collapsed': collapsed }" id="sidebar">
    <div class="logo-row" id="logoRow">
        <div class="logo-wrap" id="logoWrap">
            <img src="{{ asset('images/logo-utn.AVIF') }}" alt="UTN" class="logo-img">
        </div>
        <div class="logo-text" id="logoText" data-labels>
            <span class="logo-title">{{ __('UTN System') }}</span>
            <span class="logo-sub">{{ __('Academic Management') }}</span>
        </div>
    </div>

    <nav class="nav-scroll">
        <div class="nav-group">
            <span class="nav-label" data-labels>{{ __('MAIN') }}</span>
            <a href="{{ route('dashboard') }}" wire:navigate wire:current="active" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                </svg>
                <span class="nav-text" data-labels>{{ __('Main Panel') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>

            {{--
                Gated purely on the "Estudiante" role, not a permission —
                this screen (StudentRequestComponent) is intentionally the
                only one that role can reach in the Requests bounded
                context; RequestPolicy::viewAny() denies it the staff
                inbox link below even though it also holds the blanket
                'requests.view' permission (see that policy's docblock).
            --}}
            @if(auth()->user()?->hasRole('Estudiante'))
            <a href="{{ route('requests.student-request.index') }}" wire:navigate wire:current="active" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="9" y1="13" x2="15" y2="13"></line>
                    <line x1="9" y1="17" x2="13" y2="17"></line>
                </svg>
                <span class="nav-text" data-labels>{{ __('My requests') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>
            @endif
        </div>

        @php
            $canSeeRoles = auth()->user()?->can('viewAny', \Src\IdentityAccess\Role\Domain\Entities\Role::class);
            $canSeePermissions = auth()->user()?->can('viewAny', \Src\IdentityAccess\Permission\Domain\Entities\Permission::class);
            $canSeeStudentRequests = auth()->user()?->can('viewAny', \Src\Requests\Request\Domain\Entities\Request::class)
                || auth()->user()?->can('viewAny', \Src\Requests\WaiverRule\Domain\Entities\WaiverRule::class)
                || auth()->user()?->can('viewAny', \Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent::class);
        @endphp
        {{--
            The Estudiante role has none of the 3 permissions above (it
            only reaches its own screen via the role-gated link in the
            MAIN group), so without this wrapper it would see an empty
            "SYSTEM ADMINISTRATION" header with nothing underneath.
        --}}
        @if($canSeeRoles || $canSeePermissions || $canSeeStudentRequests)
        <div class="nav-group">
            <span class="nav-label" data-labels>{{ __('SYSTEM ADMINISTRATION') }}</span>


            @can('viewAny', \Src\IdentityAccess\Role\Domain\Entities\Role::class)
            <a href="{{ route('identityaccess.role.index') }}" wire:navigate wire:current="active" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <path d="M9 12l2 2 4-4"></path>
                </svg>
                <span class="nav-text" data-labels>{{ __('Roles') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>
            @endcan

            @can('viewAny', \Src\IdentityAccess\Permission\Domain\Entities\Permission::class)
            <a href="{{ route('identityaccess.permission.index') }}" wire:navigate wire:current="active" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span class="nav-text" data-labels>{{ __('Permissions') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>
            @endcan

            @if($canSeeStudentRequests)
            <div
                x-data="{
                    open: true,
                    currentTab: new URLSearchParams(window.location.search).get('tab') || 'waiver',
                }"
                x-on:livewire:navigated.window="
                    open = false;
                    currentTab = new URLSearchParams(window.location.search).get('tab') || 'waiver';
                "
            >
                <button type="button" class="nav-item nav-parent" @click="open = !open" :aria-expanded="open">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="9" y1="13" x2="15" y2="13"></line>
                        <line x1="9" y1="17" x2="13" y2="17"></line>
                    </svg>
                    <span class="nav-text" data-labels>{{ __('Student Requests') }}</span>
                    <svg class="nav-chevron chevron-toggle" :class="{ 'open': open }" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="nav-children" :class="{ 'open': open }" data-labels>
                    @can('viewAny', \Src\Requests\Request\Domain\Entities\Request::class)
                    {{-- Three separate links into the same RequestComponent,
                         one per ?tab= value it reads (see
                         RequestComponent::$activeTab) — replaces the single
                         combined "Requests Inbox" entry this submenu used to
                         have. wire:current can't tell these apart: Livewire's
                         own pathMatches() (js/directives/wire-current.js)
                         only ever compares URL.pathname, even with .exact —
                         it never looks at the query string — so all three
                         would light up together since they share the same
                         path. currentTab (above) is plain Alpine reading
                         location.search instead, refreshed on the same
                         livewire:navigated event Livewire uses internally
                         (needed because this sidebar is x-persist'd across
                         wire:navigate, so it never gets a fresh server
                         render to recompute an @if from). --}}
                    <a href="{{ route('requests.request.index', ['tab' => 'waiver']) }}" wire:navigate class="nav-child" :class="{ 'active': currentTab === 'waiver' }">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('Waiver inbox') }}</span>
                    </a>
                    <a href="{{ route('requests.request.index', ['tab' => 'validation']) }}" wire:navigate class="nav-child" :class="{ 'active': currentTab === 'validation' }">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('Validation inbox') }}</span>
                    </a>
                    <a href="{{ route('requests.request.index', ['tab' => 'history']) }}" wire:navigate class="nav-child" :class="{ 'active': currentTab === 'history' }">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('History') }}</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="nav-group">
            <span class="nav-label" data-labels>{{ __('ACADEMIC') }}</span>

            <a href="#" @click.prevent="setSection('oferta')" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 6.5c-1.6-1.3-3.8-2-6-2-.6 0-1 .4-1 1v11c0 .6.4 1 1 1 2.2 0 4.4.7 6 2 1.6-1.3 3.8-2 6-2 .6 0 1-.4 1-1v-11c0-.6-.4-1-1-1-2.2 0-4.4.7-6 2z"></path>
                    <line x1="12" y1="6.5" x2="12" y2="19.5"></line>
                </svg>
                <span class="nav-text" data-labels>{{ __('Academic Offer') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>

            <a href="#" @click.prevent="setSection('docentes')" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                    <circle cx="10" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span class="nav-text" data-labels>{{ __('Teachers') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>

            <a href="#" @click.prevent="setSection('aulas')" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="2" width="16" height="20" rx="1"></rect>
                    <line x1="9" y1="7" x2="9" y2="7.01"></line>
                    <line x1="15" y1="7" x2="15" y2="7.01"></line>
                    <line x1="9" y1="11" x2="9" y2="11.01"></line>
                    <line x1="15" y1="11" x2="15" y2="11.01"></line>
                    <line x1="9" y1="15" x2="9" y2="15.01"></line>
                    <line x1="15" y1="15" x2="15" y2="15.01"></line>
                    <line x1="10" y1="22" x2="10" y2="18.5"></line>
                    <line x1="14" y1="22" x2="14" y2="18.5"></line>
                </svg>
                <span class="nav-text" data-labels>{{ __('Classrooms') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>

            <div x-data="{ open: true }" x-on:livewire:navigated.window="open = false">
                <button type="button" class="nav-item nav-parent" @click="open = !open; setSection('grupos', currentSub || 'activos')" :aria-expanded="open">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span class="nav-text" data-labels>{{ __('Groups') }}</span>
                    <svg class="nav-chevron chevron-toggle" :class="{ 'open': open }" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="nav-children" :class="{ 'open': open }" data-labels>
                    <a href="#" class="nav-child" @click.prevent="setSection('grupos', 'activos')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('Active Groups') }}</span>
                    </a>
                    <a href="#" class="nav-child" @click.prevent="setSection('grupos', 'historial')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('Group History') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="nav-group">
            <span class="nav-label" data-labels>{{ __('TRACKING') }}</span>

            <a href="#" @click.prevent="setSection('riesgos')" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3 2 20h20L12 3z"></path>
                    <line x1="12" y1="9.5" x2="12" y2="14"></line>
                    <circle cx="12" cy="16.7" r="0.9" fill="currentColor" stroke="none"></circle>
                </svg>
                <span class="nav-text" data-labels>{{ __('Risks') }}</span>
                <svg class="nav-chevron" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </a>

            <div x-data="{ open: false }" x-on:livewire:navigated.window="open = false">
                <button type="button" class="nav-item nav-parent" @click="open = !open; setSection('reportes', currentSub || 'reporte1')" :aria-expanded="open">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="8" y1="13" x2="16" y2="13"></line>
                        <line x1="8" y1="17" x2="12" y2="17"></line>
                    </svg>
                    <span class="nav-text" data-labels>{{ __('Reports') }}</span>
                    <svg class="nav-chevron chevron-toggle" :class="{ 'open': open }" data-labels width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="nav-children" :class="{ 'open': open }" data-labels>
                    <a href="#" class="nav-child" @click.prevent="setSection('reportes', 'reporte1')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('Report 1') }}</span>
                    </a>
                    <a href="#" class="nav-child" @click.prevent="setSection('reportes', 'reporte2')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <span>{{ __('Report 2') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</aside>