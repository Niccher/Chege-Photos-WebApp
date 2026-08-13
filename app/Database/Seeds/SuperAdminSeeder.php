<?php

namespace App\Database\Seeds;

/**
 * Creates the super admin account.
 *
 * Run with: php spark db:seed SuperAdminSeeder
 */
class SuperAdminSeeder extends BaseUserSeeder
{
    protected string $group           = 'superadmin';
    protected string $emailEnv        = 'SUPERADMIN_EMAIL';
    protected string $passwordEnv     = 'SUPERADMIN_PASSWORD';
    protected string $usernameEnv     = 'SUPERADMIN_USERNAME';
    protected string $emailDefault    = 'superadmin@eavesdroid.com';
    protected string $passwordDefault = 'SuperAdmin@2024!';
    protected string $usernameDefault = 'superadmin';
}
