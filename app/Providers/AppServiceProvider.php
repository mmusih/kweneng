<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PromotionService;
use App\Services\AcademicStructureService;
use App\Services\SubjectService;
use App\Services\MarksService; // Add this

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind PromotionService to the container
        $this->app->singleton(PromotionService::class, function ($app) {
            return new PromotionService();
        });
        
        // Bind AcademicStructureService to the container
        $this->app->singleton(AcademicStructureService::class, function ($app) {
            return new AcademicStructureService();
        });
        
        // Bind SubjectService to the container
        $this->app->singleton(SubjectService::class, function ($app) {
            return new SubjectService();
        });
        
        // Bind MarksService to the container
        $this->app->singleton(MarksService::class, function ($app) {
            return new MarksService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
