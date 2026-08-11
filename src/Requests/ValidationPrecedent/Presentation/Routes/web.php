<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Requests\ValidationPrecedent\Presentation\Livewire\ValidationPrecedentComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('precedentes-validacion', ValidationPrecedentComponent::class)
    ->name('requests.validation-precedent.index');
