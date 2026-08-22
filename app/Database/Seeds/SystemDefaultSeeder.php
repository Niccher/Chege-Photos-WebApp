<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SystemDefaultSeeder extends Seeder
{
    public function run()
    {
        // ── ML Configurations ──────────────────────────────────────
        if (setting('ML.faceModelPack') === null) {
            setting()->set('ML.faceModelPack', 'buffalo_l');
        }
        if (setting('ML.faceDetThresh') === null) {
            setting()->set('ML.faceDetThresh', 0.5);
        }
        if (setting('ML.clipModelName') === null) {
            setting()->set('ML.clipModelName', 'openai/clip-vit-base-patch32');
        }
        if (setting('ML.objectDetThresh') === null) {
            setting()->set('ML.objectDetThresh', 0.5);
        }
        if (setting('ML.hdbscanMinCluster') === null) {
            setting()->set('ML.hdbscanMinCluster', 2);
        }
        if (setting('ML.hdbscanMinSamples') === null) {
            setting()->set('ML.hdbscanMinSamples', 1);
        }
        if (setting('ML.apiKey') === null) {
            setting()->set('ML.apiKey', 'my_super_secret_shared_token_key_123!');
        }

        // ── Cron Task Schedules ────────────────────────────────────
        if (setting('Cron.trashPurge') === null) {
            setting()->set('Cron.trashPurge', '0 2 * * *');
        }
        if (setting('Cron.mlCluster') === null) {
            setting()->set('Cron.mlCluster', '0 * * * *');
        }
        if (setting('Cron.mlSweep') === null) {
            setting()->set('Cron.mlSweep', '*/5 * * * *');
        }
        if (setting('Cron.cleanTemp') === null) {
            setting()->set('Cron.cleanTemp', '30 1 * * *');
        }

        // ── Email / SMTP Settings ──────────────────────────────────
        if (setting('Email.protocol') === null) {
            setting()->set('Email.protocol', 'smtp');
        }
        if (setting('Email.SMTPHost') === null) {
            setting()->set('Email.SMTPHost', 'mail.chegecache.co.ke');
        }
        if (setting('Email.SMTPUser') === null) {
            setting()->set('Email.SMTPUser', 'chegeos@chegecache.co.ke');
        }
        if (setting('Email.SMTPPass') === null) {
            setting()->set('Email.SMTPPass', 'wzj1Vvk]7p9l');
        }
        if (setting('Email.SMTPPort') === null) {
            setting()->set('Email.SMTPPort', 465);
        }
        if (setting('Email.SMTPCrypto') === null) {
            setting()->set('Email.SMTPCrypto', 'ssl');
        }
        if (setting('Email.fromEmail') === null) {
            setting()->set('Email.fromEmail', 'chegeos@chegecache.co.ke');
        }
        if (setting('Email.fromName') === null) {
            setting()->set('Email.fromName', 'Chege Photos Administration');
        }

        echo "System default settings (ML, Crons, SMTP) successfully seeded!\n";
    }
}
