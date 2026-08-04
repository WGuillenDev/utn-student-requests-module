<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Domain interface => infrastructure implementation.
     *
     * \App\Domain\Students\Repositories\StudentRepository::class =>
     *     \App\Infrastructure\Persistence\Eloquent\Students\Repositories\EloquentStudentRepository::class,
     */
    protected array $repositoryBindings = [
        //
    ];

    public function register(): void
    {
        foreach ($this->repositoryBindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
