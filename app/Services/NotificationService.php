<?php
namespace App\Services;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationService {
    protected $notification;
    public function __construct(Notification $notification) {
        $this->notification = $notification;
    }

    public function createNotification($request, $type) {
        return $this->notification->createNotification($request, $type);
    }

    public function getUserNotifications($data) {
        $all_notifications = $this->notification->getNotificationsByUserId($data);
        // $result['today_notifications'] = $this->notification->getTodayNotifications($data['user_id']);
        // $result['yesterday_notifications'] = $this->notification->getYesterdayNotifications($data['user_id']);
        // $result['week_notifications'] = $this->notification->getThisWeekNotifications($data['user_id']);
        // dd($all_notifications['data']);
        $notifications = [];
        foreach($all_notifications['data'] as $notif) {
            $date = new Carbon($notif['created_at']);
            $notif['time'] = $date->diffForHumans();
            $notifications[] = $notif;
        }
        $all_notifications['data'] = $notifications;

        $result['all_notifications'] = $all_notifications;
        $result['unread_count'] = $this->notification->getUnreadNotificationCount($data['user_id']);
        return $result;
    }

    public function readNotifications($user_id) {
        return $this->notification->readNotifications($user_id);
    }
}
?>