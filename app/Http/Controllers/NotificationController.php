<?php

namespace App\Http\Controllers;

use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

class NotificationController extends Controller
{
    use Response;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->success('User notifications', 200, [
            'notifications' => request()->user()?->notifications
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(string $id): \Illuminate\Http\JsonResponse
    {
        $notification = request()->user()
            ?->unreadNotifications()
            ?->firstWhere('id', $id);

        $notification?->markAsRead();

        return $this->success('Notification read.', 200, [
            'notification' => $notification
        ]);
    }
}
