<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Commands extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Commands
     * --------------------------------------------------------------------------
     * Available CLI commands.
     *
     * @var array<string, class-string|string>
     */
    public $commands = [
        'trash:purge' => 'App\Commands\TrashPurge',
    ];
}
