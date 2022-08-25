<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use File;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    protected $fillable = [
        'sender_id', 'sender_phone', 'receiver_id', 'receiver_phone', 'amount', 'notes', 'receiver_name', 'receiver_image', 'payment_type'
    ];

    public function createTransaction($request) {
        $data = $request->all();
        if(empty($data)) {
            return false;
        }
        $this->sender_id = $data['sender_id'];
        $this->sender_phone = $data['sender_phone'];
        $this->receiver_id = $data['receiver_id'] ?? null;
        $this->receiver_phone = $data['receiver_phone'];
        $this->amount = $data['amount'];
        $this->notes = $data['notes'] ?? null;
        $this->receiver_name = $data['receiver_name'] ?? null;
        $this->payment_type = $data['payment_type'] ?? 0;
        $this->receiver_image = $data['receiver_image'] ?? null;
        return $this->save();

    }

    public function getTransactionsByUserId($user_id) {
        $country_code = auth()->user()->country_code;
        if($country_code == 1) {
            return $this->with(['sender', 'receiver'])->where('payment_type', 0)->where('sender_id', $user_id)->orderBy('created_at', 'DESC')->get()->toArray();
        }   elseif($country_code == 254) {
            return $this->with(['sender', 'receiver'])->where('payment_type', 0)->where('receiver_id', $user_id)->orderBy('created_at', 'DESC')->get()->toArray();
        }   else {
            return [];
        }
        // return $this->with(['sender', 'receiver'])->where('payment_type', 0)->where('sender_id', $user_id)->orWhere('receiver_id', $user_id)->orderBy('created_at', 'DESC')->get()->toArray();

    }

    public function sender() {
        return $this->belongsTo('App\Models\User', 'sender_id');
    }

    public function receiver() {
        return $this->belongsTo('App\Models\User', 'receiver_id');
    }

}
