<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Card extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "payments";
    protected $fillable = ['sender_id', 'receiver_id', 'notes','amount','card_id', 'payment_type', 'transaction_id','payment_status'];
}
