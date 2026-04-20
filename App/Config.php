<?php

namespace App;

/**
 * Application configuration
 *
 * PHP version 7.0
 */
class Config
{

    /**
     * Database host
     * @var string
     */
    // Default updated for Docker compose environment. Can be overridden by environment variables.
    const DB_HOST = 'db';

    /**
     * Database name
     * @var string
     */
    const DB_NAME = 'vide_grenier';

    /**
     * Database user
     * @var string
     */
    const DB_USER = 'app';

    /**
     * Database password
     * @var string
     */
    const DB_PASSWORD = 'app';

    /**
     * Show or hide error messages on screen
     * @var boolean
     */
    const SHOW_ERRORS = true;
}
