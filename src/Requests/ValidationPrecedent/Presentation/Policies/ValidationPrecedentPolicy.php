<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Presentation\Policies;

use App\Models\User;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 *
 * Standard 7-action CRUD policy — no `review` method, same reasoning as
 * WaiverRulePolicy: no state machine to review here either.
 */
class ValidationPrecedentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('validation_precedents.view');
    }

    public function view(User $user, ValidationPrecedent $validationPrecedent): bool
    {
        return $user->hasPermissionTo('validation_precedents.view');
    }

    public function search(User $user): bool
    {
        return $user->hasPermissionTo('validation_precedents.search');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('validation_precedents.create');
    }

    public function update(User $user, ValidationPrecedent $validationPrecedent): bool
    {
        return $user->hasPermissionTo('validation_precedents.edit');
    }

    public function delete(User $user, ValidationPrecedent $validationPrecedent): bool
    {
        return $user->hasPermissionTo('validation_precedents.delete');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('validation_precedents.export_pdf');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('validation_precedents.export_excel');
    }
}
