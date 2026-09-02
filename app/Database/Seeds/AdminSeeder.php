<?php

namespace App\Database\Seeds;

/**
 * Creates the site admin account.
 *
 * Run with: php spark db:seed AdminSeeder
 */
class AdminSeeder extends BaseUserSeeder
{
    protected string $group           = 'admin';
    protected string $emailEnv        = 'ADMIN_EMAIL';
    protected string $passwordEnv     = 'ADMIN_PASSWORD';
    protected string $usernameEnv     = 'ADMIN_USERNAME';
    protected string $emailDefault    = 'admin@chegecache.co.ke';
    protected string $passwordDefault = 'Admin@2024!';
    protected string $usernameDefault = 'admin';
}
