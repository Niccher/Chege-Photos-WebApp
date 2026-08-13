<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * Shared logic for seeding Shield users with a given group.
 *
 * Override the protected properties in a concrete seeder to define the
 * group, environment variable names, and fallback credentials.
 */
abstract class BaseUserSeeder extends Seeder
{
    protected string $group           = 'user';
    protected string $emailEnv        = 'ADMIN_EMAIL';
    protected string $passwordEnv     = 'ADMIN_PASSWORD';
    protected string $usernameEnv     = 'ADMIN_USERNAME';
    protected string $emailDefault    = 'admin@eavesdroid.com';
    protected string $passwordDefault = 'Admin@2024!';
    protected string $usernameDefault = 'admin';

    public function run(): void
    {
        $username = env($this->usernameEnv, $this->usernameDefault);
        $email    = env($this->emailEnv, $this->emailDefault);
        $password = env($this->passwordEnv, $this->passwordDefault);

        if ($username === '' || $email === '' || $password === '') {
            echo "  Skipping group '{$this->group}': a credential env var is empty.\n";

            return;
        }

        /** @var UserModel $users */
        $users = model(UserModel::class);

        // Skip if the username already exists.
        $existing = $users->where('username', $username)->first();
        if ($existing !== null) {
            $existing->addGroup($this->group);
            $existing->activate();
            echo "  User '{$username}' already exists — ensured group '{$this->group}'.\n";

            return;
        }

        // Skip if the email is already registered to another account.
        $identityModel = model(UserIdentityModel::class);
        $identity      = $identityModel
            ->where('type', 'email_password')
            ->where('secret', $email)
            ->first();
        if ($identity !== null) {
            echo "  Email '{$email}' is already registered — skipped group '{$this->group}'.\n";

            return;
        }

        $user = $users->createNewUser([
            'username' => $username,
            'name'     => ucfirst($username),
            'email'    => $email,
            'password' => $password,
            'active'   => true,
        ]);

        $users->save($user);

        // Re-fetch so the entity carries the generated ID.
        $user = $users->findById($users->getInsertID());

        $user->addGroup($this->group);
        $user->activate();

        echo "  User '{$username}' created with group '{$this->group}'.\n";
    }
}
