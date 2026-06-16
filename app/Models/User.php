<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'username',
        'full_name',
        'hashed_password',
        'is_active',
        'is_super_admin',
    ];

    protected $hidden = [
        'hashed_password',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_super_admin' => 'boolean',
        'last_login'     => 'datetime',
    ];

    // Laravel auth uses 'password' - map it to our column
    public function getAuthPassword()
    {
        $hash = $this->hashed_password;
    // Convert Python bcrypt $2b$ to PHP bcrypt $2y$
    if (str_starts_with($hash, '$2b$')) {
        $hash = '$2y$' . substr($hash, 4);
    }
    return $hash;
    }

    // Allow login by username or email
    public function getAuthIdentifierName()
    {
        return 'username';
    }

}