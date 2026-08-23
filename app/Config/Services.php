<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function email($config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('email', $config);
        }

        if (empty($config)) {
            $config = config('Email');
        }

        // Dynamically override config properties with values from settings (database)
        if (function_exists('setting')) {
            $config->fromEmail   = setting('Email.fromEmail') ?? $config->fromEmail;
            $config->fromName    = setting('Email.fromName') ?? $config->fromName;
            $config->protocol    = setting('Email.protocol') ?? $config->protocol;
            $config->SMTPHost    = setting('Email.SMTPHost') ?? $config->SMTPHost;
            $config->SMTPUser    = setting('Email.SMTPUser') ?? $config->SMTPUser;
            $config->SMTPPass    = setting('Email.SMTPPass') ?? $config->SMTPPass;
            $config->SMTPPort    = setting('Email.SMTPPort') ?? $config->SMTPPort;
            $config->SMTPCrypto  = setting('Email.SMTPCrypto') ?? $config->SMTPCrypto;
        }

        return new \CodeIgniter\Email\Email($config);
    }
}
