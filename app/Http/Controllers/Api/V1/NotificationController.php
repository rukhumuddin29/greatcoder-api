<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->getUserNotifications(
            $request->user()->id,
            $request->input('limit', 20)
        );
        return $this->success($data);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::forUser($request->user()->id)->unread()->count();
        return $this->success(['unread_count' => $count]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $this->service->markAsRead($id, $request->user()->id);
        return $this->success(null, 'Notification marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        $count = $this->service->markAllAsRead($request->user()->id);
        return $this->success(['marked' => $count], 'All notifications marked as read');
    }
}
