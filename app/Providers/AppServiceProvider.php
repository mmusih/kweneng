<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PromotionService;
use App\Services\AcademicStructureService;
use App\Services\SubjectService;
use App\Services\MarksService;
use App\Services\ExamSummaryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PromotionService::class, function ($app) {
            return new PromotionService();
        });

        $this->app->singleton(AcademicStructureService::class, function ($app) {
            return new AcademicStructureService();
        });

        $this->app->singleton(SubjectService::class, function ($app) {
            return new SubjectService();
        });

        $this->app->singleton(MarksService::class, function ($app) {
            return new MarksService();
        });

        $this->app->singleton(ExamSummaryService::class, function ($app) {
            return new ExamSummaryService(
                $app->make(MarksService::class)
            );
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
