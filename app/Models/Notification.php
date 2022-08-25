<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use File;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Notification extends Model
{
    use HasFactory;

    protected $table = "notifications";

    protected $fillable = [
        'sender_id', 'sender_phone', 'receiver_id', 'receiver_phone', 'amount', 'notes', 'receiver_name', 'receiver_image', 'payment_type', 'notification_type'
    ];

    public function createNotification($request, $type = 1) {
        $data = $request->all();
        if(empty($data)) {
            return false;
        }
        $this->sender_id = $data['sender_id'];
        $this->sender_phone = $data['sender_phone'];
        $this->receiver_id = $data['receiver_id']; // receiver id is required here
        $this->receiver_phone = $data['receiver_phone'];
        $this->amount = $data['amount'];
        $this->notes = $data['notes'] ?? null;
        $this->receiver_name = $data['receiver_name'] ?? null;
        $this->payment_type = $data['payment_type'] ?? 0;
        $this->notification_type = $type;
        $this->receiver_image = $data['receiver_image'] ?? null;
        return $this->save();

    }

    public function getNotificationsByUserId($data) {
        $skip = 0;
        if(isset($data['page']) && $data['page'] != NULL) {
            $skip = ($data['page'] - 1) * $data['per_page'];
        }
        $country_code = auth()->user()->country_code;
        if($country_code == 1) {
            return $this->with(['sender'])->where('payment_type', 1)->where('receiver_id', $data['user_id'])->skip($skip)->orderBy('created_at', 'DESC')->paginate($data['per_page'])->toArray();
        }   elseif($country_code == 254) {
            return $this->with(['sender'])->where('payment_type', 0)->where('receiver_id', $data['user_id'])->skip($skip)->orderBy('created_at', 'DESC')->paginate($data['per_page'])->toArray();
        }   else {
            return [];
        }

    }

    public function getTodayNotifications($user_id) {
        return $this->whereDate('created_at', Carbon::today())->where('receiver_id', $user_id)->orderBy('created_at', 'DESC')->get()->toArray();
    }

    public function getThisWeekNotifications($user_id) {
        return $this->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->where('receiver_id', $user_id)->orderBy('created_at', 'DESC')->get()->toArray();
    }

    public function getYesterdayNotifications($user_id) {
        return $this->whereDate('created_at', Carbon::yesterday())->where('receiver_id', $user_id)->orderBy('created_at', 'DESC')->get()->toArray();
    }

    public function getUnreadNotificationCount($user_id) {
        return $this->whereDate('created_at', Carbon::today())->where(['receiver_id' => $user_id, 'read' => 0])->orderBy('created_at', 'DESC')->get()->count();
    }

    public function readNotifications($user_id) {
        return $this->where('receiver_id', $user_id)->update(['read' => 1]);
    }

    public function sender() {
        return $this->belongsTo('App\Models\User', 'sender_id');
    }


}
