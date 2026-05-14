<?php

namespace App\Models;

/**
 * Extends Shield UserModel to allow profile fields stored on `users`.
 */
class UserModel extends \CodeIgniter\Shield\Models\UserModel
{
    protected $allowedFields = [
        'username',
        'name',
        'avatar',
        'status',
        'status_message',
        'active',
        'last_active',
    ];
}
