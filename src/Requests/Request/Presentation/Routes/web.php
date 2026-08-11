<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Requests\Request\Presentation\Livewire\RequestComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('solicitudes', RequestComponent::class)
    ->name('requests.request.index');
