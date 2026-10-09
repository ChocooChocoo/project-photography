<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useBootstrapFive();
        foreach ([
            \App\Models\BookingModel::class,
            \App\Models\BookingContractModel::class,
            \App\Models\StudioOwner\BookingAssignedPhotographerModel::class,
            \App\Models\StudioOwner\BookingEquipmentModel::class,
            \App\Models\StudioOwner\StudioOnlineGalleryModel::class,
            \App\Models\Freelancer\FreelanceOnlineGalleryModel::class,
        ] as $model) {
            $model::saved(fn ($record) => app(\App\Services\BookingEventRecorder::class)->saved($record));
        }
    }
}
