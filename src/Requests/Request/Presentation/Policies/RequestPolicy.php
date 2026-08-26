<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Policies;

use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\User;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * Registered via Gate::policy() in DomainServiceProvider. Superadmin
 * bypasses all of it through Gate::before.
 *
 * Students hold 'requests.view' so they can see their own requests, but
 * that permission alone cannot tell "mine" from "everyone's" — on its
 * own it would open the unscoped staff inbox to them. So two methods add
 * a role and ownership check on top:
 *  - viewAny() denies students outright; they use the self-service
 *    screen, which never authorizes against it.
 *  - view() restricts students to requests owned by their own student
 *    row.
 *
 * No other role is affected.
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
     * Custom action outside the standard seven, seeded separately as
     * 'requests.review'.
     */
    public function review(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('requests.review');
    }

    /**
     * Gates the closing step separately from review(): Docencia can take
     * a request as far as its own decision, but only a holder of
     * 'requests.finalize' can close it for good.
     */
    public function finalize(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('requests.finalize');
    }

    private function ownsRequest(User $user, Request $request): bool
    {
        return StudentModel::query()
            ->where('user_id', $user->id)
            ->where('id', $request->studentId())
            ->exists();
    }
}
