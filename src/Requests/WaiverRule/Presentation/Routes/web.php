<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Requests\WaiverRule\Presentation\Livewire\WaiverRuleComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('reglas-dispensa', WaiverRuleComponent::class)
    ->name('requests.waiver-rule.index');
