<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Policies;

use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\User;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 *
 * The Estudiante role holds the blanket 'requests.view' permission too
 * (it needs to view its own requests), but that permission alone can't
 * distinguish "my requests" from "everyone's requests" — a plain
 * hasPermissionTo() check would let a student into the staff inbox
 * (RequestComponent, whose query is intentionally unscoped) and see
 * every student's data. So both methods below add an explicit role +
 * ownership check on top of the permission check:
 *  - viewAny() denies the Estudiante role outright — the staff inbox is
 *    not for them; they use the dedicated self-service screen instead
 *    (StudentRequestComponent), which never calls authorize('viewAny', ...).
 *  - view() restricts the Estudiante role to requests belonging to
 *    their own students row (via students.user_id), used e.g. by the
 *    self-service screen's "Ver" action.
 * Every other role (Superadmin/Admin/Coordinadora de Docencia) is
 * unaffected — hasRole('Estudiante') is false for all of them.
 */
class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Estudiante')) {
            return false;
        }

        return $user->hasPermissionTo('requests.view');
    }

    public function view(User $user, Request $request): bool
    {
        if (! $user->hasPermissionTo('requests.view')) {
            return false;
        }

        if ($user->hasRole('Estudiante')) {
            return $this->ownsRequest($user, $request);
        }

        return true;
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

    private function ownsRequest(User $user, Request $request): bool
    {
        return StudentModel::query()
            ->where('user_id', $user->id)
            ->where('id', $request->studentId())
            ->exists();
    }
}
