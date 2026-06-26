<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Services
use App\Services\AIService;
use App\Services\CvBuilderService;
use App\Services\CvAnalysisService;
use App\Services\CvIntegrityService;
use App\Services\CvSourceResolverService;
use App\Services\CvFileExtractionService;
use App\Services\GenericAtsScoreService;
use App\Services\JobKeywordExtractorService;
use App\Services\JobMatchService;
use App\Services\SkillInsightService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ✅ تسجيل الـ Services كـ Singletons
        $this->app->singleton(AIService::class);
        $this->app->singleton(CvBuilderService::class);
        $this->app->singleton(CvIntegrityService::class);
        $this->app->singleton(SkillInsightService::class);
        $this->app->singleton(JobKeywordExtractorService::class);
        $this->app->singleton(JobMatchService::class);

        // Services التي تعتمد على بعضها
        $this->app->singleton(GenericAtsScoreService::class, function ($app) {
            return new GenericAtsScoreService(
                $app->make(JobKeywordExtractorService::class)
            );
        });

        $this->app->singleton(CvFileExtractionService::class, function ($app) {
            return new CvFileExtractionService(
                $app->make(AIService::class)
            );
        });

        $this->app->singleton(CvSourceResolverService::class, function ($app) {
            return new CvSourceResolverService(
                $app->make(CvBuilderService::class),
                $app->make(CvFileExtractionService::class)
            );
        });

        $this->app->singleton(CvAnalysisService::class, function ($app) {
            return new CvAnalysisService(
                $app->make(AIService::class),
                $app->make(JobMatchService::class),
                $app->make(CvBuilderService::class),
                $app->make(SkillInsightService::class),
                $app->make(CvIntegrityService::class),
                $app->make(GenericAtsScoreService::class)
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
