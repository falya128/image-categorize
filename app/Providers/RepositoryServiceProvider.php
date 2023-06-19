<?php

namespace App\Providers;

use App\Interfaces\RekognitionRepositoryInterface;
use App\Repositories\RekognitionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RekognitionRepositoryInterface::class, RekognitionRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
