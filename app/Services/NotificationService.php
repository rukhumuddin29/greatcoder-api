<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationService extends BaseService
{
    /**
     * Create a notification for a single user.
     */
    public function notify(int $userId, string $type, string $title, string $message, array $options = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'icon'    => $options['icon'] ?? $this->getDefaultIcon($type),
            'color'   => $options['color'] ?? 'primary',
            'link'    => $options['link'] ?? null,
            'data'    => $options['data'] ?? null,
        ]);
    }

    /**
     * Notify multiple users (e.g. all admins).
     */
    public function notifyMany(array $userIds, string $type, string $title, string $message, array $options = []): void
    {
        foreach ($userIds as $userId) {
            $this->notify($userId, $type, $title, $message, $options);
        }
    }

    /**
     * Notify all users with a specific permission.
     */
    public function notifyByPermission(string $permission, string $type, string $title, string $message, array $options = []): void
    {
        $userIds = User::whereHas('permissions', fn($q) => $q->where('name', $permission))
            ->orWhereHas('roles.permissions', fn($q) => $q->where('name', $permission))
            ->pluck('id')
            ->toArray();

        $this->notifyMany($userIds, $type, $title, $message, $options);
    }

    /**
     * Get recent notifications for a user.
     */
    public function getUserNotifications(int $userId, int $limit = 20): array
    {
        $notifications = Notification::forUser($userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $unreadCount = Notification::forUser($userId)->unread()->count();

        return [
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ];
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['read_at' => now()]) > 0;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Default icon mapping per notification type.
     */
    private function getDefaultIcon(string $type): string
    {
        return match($type) {
            'lead_assigned'     => 'mdi-account-plus',
            'lead_converted'    => 'mdi-check-circle',
            'expense_approved'  => 'mdi-cash-check',
            'expense_rejected'  => 'mdi-cash-remove',
            'payroll_approved'  => 'mdi-cash-multiple',
            'payroll_paid'      => 'mdi-bank-transfer',
            'follow_up_due'     => 'mdi-clock-alert',
            'leave_approved'    => 'mdi-calendar-check',
            'leave_rejected'    => 'mdi-calendar-remove',
            'leave_requested'   => 'mdi-calendar-clock',
            default             => 'mdi-bell',
        };
    }
}
