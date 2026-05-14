<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

use Livewire\Attributes\On;

class NotificationBell extends Component
{
    public ?int $userId = null;

    public function mount(): void
    {
        $this->userId = Auth::id();
    }

    public function getListeners(): array
    {
        if (! $this->userId) {
            return [];
        }

        return [
            "echo-private:App.Models.User.{$this->userId},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => 'refreshNotifications',
        ];
    }

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
