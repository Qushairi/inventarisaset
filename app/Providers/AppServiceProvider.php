<?php

namespace App\Providers;

use App\Models\User;
use App\Support\DashboardRedirector;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;

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
        RedirectIfAuthenticated::redirectUsing(
            fn () => DashboardRedirector::pathFor(auth()->user()),
        );

        View::composer('layouts.navbar', function (ViewContract $view): void {
            $user = auth()->user();
            $notificationIndexUrl = null;
            $notificationMarkAllUrl = null;
            $notificationShowRouteName = null;
            $notifications = collect();
            $unreadNotificationCount = 0;

            if ($user instanceof User && Schema::hasTable('notifications')) {
                $notifications = $user->notifications()->latest()->limit(6)->get();
                $unreadNotificationCount = $user->unreadNotifications()->count();

                if ($user->role === 'pegawai') {
                    $notificationIndexUrl = route('pegawai.notifications.index', absolute: false);
                    $notificationMarkAllUrl = route('pegawai.notifications.read-all', absolute: false);
                    $notificationShowRouteName = 'pegawai.notifications.show';
                }
            }

            $view->with([
                'navbarNotifications' => $notifications,
                'navbarUnreadNotificationCount' => $unreadNotificationCount,
                'notificationIndexUrl' => $notificationIndexUrl,
                'notificationMarkAllUrl' => $notificationMarkAllUrl,
                'notificationShowRouteName' => $notificationShowRouteName,
            ]);
        });
    }
}
