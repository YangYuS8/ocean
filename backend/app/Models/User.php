<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'display_name',
        'email',
        'status',
    ];

    protected $hidden = [];

    public $timestamps = true;
}
