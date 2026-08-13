<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Requests\Request\Presentation\Livewire\RequestComponent;
use Src\Requests\Request\Presentation\Livewire\StudentRequestComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('solicitudes', RequestComponent::class)
    ->name('requests.request.index');

Route::middleware(['web', 'auth', 'verified'])
    ->get('mis-solicitudes', StudentRequestComponent::class)
    ->name('requests.student-request.index');
