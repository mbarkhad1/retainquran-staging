<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_setting extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'tbl_user_settings';

    protected $primaryKey = 'us_id';

    protected $fillable = array('usr_id', 'reciter_id', 'trans_id', 'rtype_id', 'translation_id', 'language_id', 'is_notify', 'mushaf_id', 'tafseer_id');
}
