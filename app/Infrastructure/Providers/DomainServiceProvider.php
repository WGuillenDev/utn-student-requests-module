<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Interfaz de dominio => implementación de infraestructura.
     *
     * \App\Domain\Estudiantes\Repositories\EstudianteRepository::class =>
     *     \App\Infrastructure\Persistence\Eloquent\Estudiantes\Repositories\EloquentEstudianteRepository::class,
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
