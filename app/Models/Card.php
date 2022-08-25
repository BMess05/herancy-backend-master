<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Card extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "user_cards";
    protected $fillable = ['user_id', 'card_number', 'expiry_date','cvv','card_holder_name', 'card_type', 'default_card'];
}
