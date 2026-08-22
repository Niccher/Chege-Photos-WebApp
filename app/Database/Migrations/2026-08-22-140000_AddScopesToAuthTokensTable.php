<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddScopesToAuthTokensTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('auth_tokens', [
            'scopes' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'description'
            ]
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('auth_tokens', 'scopes');
    }
}
