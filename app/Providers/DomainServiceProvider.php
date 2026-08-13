<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;

final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $domainBindings = [
        \Src\IdentityAccess\Role\Domain\Contracts\RoleRepositoryInterface::class
        => \Src\IdentityAccess\Role\Infrastructure\Persistence\Repositories\EloquentRoleRepository::class,
        \Src\IdentityAccess\Permission\Domain\Contracts\PermissionRepositoryInterface::class
        => \Src\IdentityAccess\Permission\Infrastructure\Persistence\Repositories\EloquentPermissionRepository::class,
        \Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface::class
        => \Src\Requests\Request\Infrastructure\Persistence\Repositories\EloquentRequestRepository::class,
        \Src\Requests\Request\Domain\Contracts\RequestStatusHistoryRepositoryInterface::class
        => \Src\Requests\Request\Infrastructure\Persistence\Repositories\EloquentRequestStatusHistoryRepository::class,
        \Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface::class
        => \Src\Requests\Request\Infrastructure\Persistence\Repositories\EloquentRequestAttachmentRepository::class,
        \Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface::class
        => \Src\Requests\WaiverRule\Infrastructure\Persistence\Repositories\EloquentWaiverRuleRepository::class,
        \Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface::class
        => \Src\Requests\ValidationPrecedent\Infrastructure\Persistence\Repositories\EloquentValidationPrecedentRepository::class,
        \Src\Shared\Export\Contracts\ExcelExporterInterface::class
        => \Src\Shared\Export\Infrastructure\SpatieExcelExporter::class,
        \Src\Shared\Export\Contracts\PdfExporterInterface::class
        => \Src\Shared\Export\Infrastructure\SpatiePdfExporter::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    private array $domainPolicies = [
        \Src\IdentityAccess\Role\Domain\Entities\Role::class
        => \Src\IdentityAccess\Role\Presentation\Policies\RolePolicy::class,
        \Src\IdentityAccess\Permission\Domain\Entities\Permission::class
        => \Src\IdentityAccess\Permission\Presentation\Policies\PermissionPolicy::class,
        \Src\Requests\Request\Domain\Entities\Request::class
        => \Src\Requests\Request\Presentation\Policies\RequestPolicy::class,
        \Src\Requests\WaiverRule\Domain\Entities\WaiverRule::class
        => \Src\Requests\WaiverRule\Presentation\Policies\WaiverRulePolicy::class,
        \Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent::class
        => \Src\Requests\ValidationPrecedent\Presentation\Policies\ValidationPrecedentPolicy::class,
    ];

    public function register(): void
    {
        foreach ($this->domainBindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerSuperAdminBypass();
        $this->loadContextRoutes();
    }

    private function registerPolicies(): void
    {
        foreach ($this->domainPolicies as $entity => $policy) {
            Gate::policy($entity, $policy);
        }
    }

    /**
     * Superadmin passes every authorization check unconditionally. The
     * RoleSeeder already syncs it every existing permission — this is the
     * safety net that also covers permissions introduced after the last
     * seed run, without needing to re-sync anything.
     */

    private function registerSuperAdminBypass(): void
    {
        Gate::before(function (Authenticatable $user): ?bool {
            return method_exists($user, 'hasRole') && $user->hasRole('Superadmin')
                ? true
                : null;
        });
    }

    private function loadContextRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (File::glob(base_path('src/*/*/Presentation/Routes/web.php')) as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }
    }
}
