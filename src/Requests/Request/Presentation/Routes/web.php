<?php

declare(strict_types=1);

use App\Http\Controllers\RequestAttachmentDownloadController;
use Illuminate\Support\Facades\Route;
use Src\Requests\Request\Presentation\Livewire\RequestComponent;
use Src\Requests\Request\Presentation\Livewire\StudentRequestComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('solicitudes', RequestComponent::class)
    ->name('requests.request.index');

Route::middleware(['web', 'auth', 'verified'])
    ->get('mis-solicitudes', StudentRequestComponent::class)
    ->name('requests.student-request.index');

Route::middleware(['web', 'auth', 'verified'])
    ->get('solicitudes/documentos/{fileId}', RequestAttachmentDownloadController::class)
    ->whereNumber('fileId')
    ->name('requests.request.attachment-download');
