<?php

namespace App\Providers;

use App\Models\User;
use App\Support\DashboardRedirector;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use App\Listeners\LogAuthenticationEventListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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
        Event::listen(Login::class, [LogAuthenticationEventListener::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthenticationEventListener::class, 'handleLogout']);

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
                $notifications = $user->notifications()->latest()->limit(4)->get();
                $unreadNotificationCount = $user->unreadNotifications()->count();

                if ($user->role === 'admin') {
                    $notificationIndexUrl = route('admin.notifications.index', absolute: false);
                    $notificationMarkAllUrl = route('admin.notifications.read-all', absolute: false);
                    $notificationShowRouteName = 'admin.notifications.show';
                } elseif ($user->role === 'pegawai') {
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
