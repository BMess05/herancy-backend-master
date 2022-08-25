<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Transaction;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    use SoftDeletes;
    protected $appends = ['image'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'phone_number', 'name', 'email', 'password', 'image', 'social_id', 'social_type', 'is_pasword_generated', 'email_notification', 'push_notification'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }
    public function getImageAttribute() {

        if($this->attributes['image'] == "") {
            return null;
        }
        return url('/uploads/users/'.$this->attributes['image']);
    }
    public function device_token() {
       return $this->hasMany('App\Models\DeviceToken', 'user_id', 'id');
    }
    public function card() {
       return $this->hasMany('App\Models\Card', 'user_id', 'id');
    }

    public function getUserByPhone($phone) {
        return User::select('id', 'name', 'email', 'phone_number', 'image')->where('phone_number', $phone)->first();
    }

    public function getUsersByPhone($phone) {
        return User::select('id', 'name', 'email', 'phone_number', 'image', 'country_code')->where('phone_number', 'LIKE', '%'.$phone.'%')->orderBy('name')->where('id', '!=', auth()->user()->id)->get();
    }

    public function getAllAppUsers() {
        // 1=USA
        // 254= Kenya
        $country = auth()->user()->country_code;
        if($country == "1") {
            return User::select('id', 'name', 'email', 'phone_number', 'image', 'country_code')->where('country_code', '254')->where('id', '!=', auth()->user()->id)->orderBy('name')->get()->toArray();
        }   elseif ($country == "254") {
            return User::select('id', 'name', 'email', 'phone_number', 'image', 'country_code')->where('country_code', '1')->where('id', '!=', auth()->user()->id)->orderBy('name')->get()->toArray();
        }   else {
            return [];
        }
    }

    public function notifications() {
        return $this->hasMany('App\Models\Notification', 'sender_id', 'id');
    }

    public function transactions() {
        return $this->hasMany('App\Models\Transaction', 'sender_id', 'id');
    }

    public function updateNotificationSettings($data) {
        $user = User::find(auth()->user()->id);
        if(!$user) {
            return false;
        }
        if($data['type'] == 0) {
            $user->email_notification = $data['value'];
        }   else {
            $user->push_notification = $data['value'];
        }
        return $user->save();
    }

    public function getUserRecentRequests() {
        // 1=USA
        // 254= Kenya
        $country = auth()->user()->country_code;
        $user_id = auth()->user()->id;
        if($country == '1') { // for USA users, list payments those he did
            $transactions = User::rightJoin('transactions','transactions.receiver_id','=','users.id')->select('users.id','users.name','users.email','users.phone_number','users.image', 'users.country_code', 'transactions.created_at')
                    ->where('users.country_code', 254)
                    ->where('transactions.payment_type', 0) // money sent
                    ->where('transactions.sender_id', $user_id)
                    ->orderBy('transactions.created_at', 'DESC')
                    ->distinct()
                    ->get()->toArray();
        }   else { // if ($country == '254') { // For kenya users, list requests those he made
            $transactions = User::rightJoin('transactions','transactions.receiver_id','=','users.id')->select('users.id','users.name','users.email','users.phone_number','users.image', 'users.country_code', 'transactions.created_at')
                    ->where('users.country_code', 1)
                    ->where('transactions.payment_type', 1) // money sent
                    ->where('transactions.sender_id', $user_id)
                    ->orderBy('transactions.created_at', 'DESC')
                    ->distinct()
                    ->get()->toArray();
        }
        return $transactions;
    }
}