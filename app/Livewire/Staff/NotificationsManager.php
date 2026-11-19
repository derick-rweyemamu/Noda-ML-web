<?php

namespace App\Livewire\Staff;

use App\Models\Reminder;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NotificationsManager extends Component
{
    public string $flashMessage = '';

    public function dispatchDue(NotificationService $notifications): void
    {
        abort_unless(Auth::user()?->can('notifications.dispatch'), 403);
        $due = Reminder::with('member')
            ->where('status', 'pending')
            ->where('scheduled_date', '<=', now())
            ->get();

        $count = 0;
        foreach ($due as $reminder) {
            $notifications->dispatchReminder($reminder);
            $count++;
        }

        if ($count > 0) {
            AuditService::log(Auth::user(), 'DISPATCH_REMINDERS', "{$count} reminder(s)", 'Dispatched scheduled SMS/Email reminders');
        }

        $this->flashMessage = $count > 0 ? "Dispatched {$count} due reminder(s)." : 'No pending reminders are due yet.';
    }

    public function render()
    {
        return view('livewire.staff.notifications-manager', [
            'reminders' => Reminder::with('member')->latest('scheduled_date')->get(),
        ]);
    }
}
