<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tafseer extends Model
{
    use HasFactory;
    protected $table = 'tafseers';
    const DEFAULT_TAFSEER_ID = 1;
}
