<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Presentation\Policies;

use App\Models\User;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 *
 * Standard 7-action CRUD policy — no `review` method here, unlike
 * RequestPolicy: a waiver rule has no state machine to review.
 */
class WaiverRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('waiver_rules.view');
    }

    public function view(User $user, WaiverRule $waiverRule): bool
    {
        return $user->hasPermissionTo('waiver_rules.view');
    }

    public function search(User $user): bool
    {
        return $user->hasPermissionTo('waiver_rules.search');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('waiver_rules.create');
    }

    public function update(User $user, WaiverRule $waiverRule): bool
    {
        return $user->hasPermissionTo('waiver_rules.edit');
    }

    public function delete(User $user, WaiverRule $waiverRule): bool
    {
        return $user->hasPermissionTo('waiver_rules.delete');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('waiver_rules.export_pdf');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('waiver_rules.export_excel');
    }
}
