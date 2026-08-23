<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TrashPurge extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'trash:purge';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Permanently deletes photos that have been in the trash for more than 60 days.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'trash:purge';

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $startTime = microtime(true);
        $db     = \Config\Database::connect();
        $cutoff = date('Y-m-d H:i:s', strtotime('-60 days'));

        $photos = $db->table('photos')
            ->where('deleted_at IS NOT NULL')
            ->where('deleted_at <', $cutoff)
            ->get()
            ->getResultArray();

        $purged = 0;
        foreach ($photos as $photo) {
            $id = (int) $photo['id'];

            // Clean related rows
            $db->table('album_photos')->where('photo_id', $id)->delete();
            $db->table('photo_shares')->where('photo_id', $id)->delete();
            $db->table('shared_links')->where('photo_id', $id)->delete();

            // Delete physical files
            foreach (['path', 'thumbnail_path'] as $field) {
                if (! empty($photo[$field])) {
                    $full = FCPATH . ltrim($photo[$field], '/');
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
            }

            // Hard delete the row
            $db->table('photos')->where('id', $id)->delete();
            $purged++;
        }

        $duration = microtime(true) - $startTime;
        $outputMsg = sprintf('Purged %d photo(s) older than 60 days from the trash.', $purged);

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'trash:purge',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');

        return EXIT_SUCCESS;
    }
}
