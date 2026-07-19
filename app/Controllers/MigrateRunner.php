<?php

namespace App\Controllers;

/**
 * MigrateRunner — web-accessible migration runner.
 *
 * Visit /migrate/run while logged in as an admin to apply pending migrations.
 * Responds with plain-text output so results are immediately visible in the browser.
 *
 * This controller intentionally does NOT extend BaseController so it can operate
 * before the auth helpers are fully bootstrapped.
 */
class MigrateRunner extends \CodeIgniter\Controller
{
    public function run(): string
    {
        // Only allow logged-in users
        if (! auth()->loggedIn()) {
            return 'Not authenticated. Log in first.';
        }

        $runner = \Config\Services::migrations();

        try {
            $runner->latest('App');
            $messages = ['✔ App migrations applied (or already up-to-date).'];
        } catch (\Throwable $e) {
            $messages = ['✘ Migration error: ' . $e->getMessage()];
        }

        // Also try running Shield's own migrations
        try {
            $runner->latest('CodeIgniter\\Shield');
            $messages[] = '✔ Shield migrations applied (or already up-to-date).';
        } catch (\Throwable $e) {
            $messages[] = '✘ Shield migration error: ' . $e->getMessage();
        }

        return implode("\n", $messages);
    }
}
