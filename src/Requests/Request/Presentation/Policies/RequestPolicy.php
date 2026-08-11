<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Policies;

use App\Models\User;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 */
class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('requests.view');
    }

    public function view(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('requests.view');
    }

    public function search(User $user): bool
    {
        return $user->hasPermissionTo('requests.search');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('requests.create');
    }

    public function update(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('requests.edit');
    }

    public function delete(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('requests.delete');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('requests.export_pdf');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('requests.export_excel');
    }

    /**
     * Custom action beyond the standard 7: reviewing (changing the
     * status of) a request. Not part of PermissionSeeder::ACTIONS —
     * seeded separately as 'requests.review'.
     */
    public function review(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('requests.review');
    }
}
