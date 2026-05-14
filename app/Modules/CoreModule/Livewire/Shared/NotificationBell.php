<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Livewire\Attributes\On;

class NotificationBell extends Component
{
    #[On('echo-private:App.Models.User.{authUser.id},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated')]
    public function refreshNotifications(): void
    {
        // Livewire refrescará las propiedades computadas automáticamente al dispararse el render
    }

    public function getNotificationsProperty()
    {
        return Auth::user()
            ? Auth::user()->unreadNotifications()->take(5)->get()
            : collect();
    }

    public function getUnreadCountProperty()
    {
        return Auth::user() ? Auth::user()->unreadNotifications()->count() : 0;
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()->unreadNotifications()->findOrFail($id);
        $notification->markAsRead();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('core::livewire.shared.notification-bell');
    }
}
