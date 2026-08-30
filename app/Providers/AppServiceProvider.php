<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\Enums\ImageType;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSeoDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }

    /**
     * Site-wide SEO defaults resolved by the @head directive.
     */
    protected function configureSeoDefaults(): void
    {
        Head::defaults(fn (HeadBuilder $head) => $head
            ->title('Walfa', suffix: ' - Walfa')
            ->description('Software developer with over 6 years of experience in application development, API management, and deployment platforms.')
            ->canonical()
            ->og(siteName: 'Walfa', type: OgType::Website)
            ->twitter(card: TwitterCard::SummaryWithLargeImage)
            ->searchableByRobots());

        Head::inertiaGlobals(fn (HeadBuilder $head) => $head
            ->viewport('width=device-width, initial-scale=1')
            ->colorScheme('light dark')
            ->favicon('/favicon.svg', type: ImageType::Svg)
            ->appleTouchIcon('/apple-touch-icon.png', sizes: '180x180'));
    }
}
