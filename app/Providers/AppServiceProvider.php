<?php

namespace App\Providers;

use App\Services\AcademicStructureService;
use App\Services\ExamSummaryService;
use App\Services\MarksService;
use App\Services\PromotionService;
use App\Services\SubjectService;
use App\Services\Timetable\TimetableDayService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PromotionService::class, function ($app) {
            return new PromotionService;
        });

        $this->app->singleton(AcademicStructureService::class);

        $this->app->singleton(SubjectService::class, function ($app) {
            return new SubjectService;
        });

        $this->app->singleton(MarksService::class, function ($app) {
            return new MarksService;
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
        View::composer('components.app-layout', function ($view) {
            $view->with('schoolDayLabel', app(TimetableDayService::class)->label());
        });
    }
}
