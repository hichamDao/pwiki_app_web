<?php
namespace Pkiwi\Modules;

use Pkiwi\Database\Connection;

class Installer
{
    public static function ensureInstalled(Connection $db = null)
    {
        if ($db === null) {
            return false;
        }

        $db->execute("CREATE TABLE IF NOT EXISTS `pkiwi_users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(50) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `last_login` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS `pkiwi_user_sites` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `matomo_site_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `url` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_url` (`user_id`,`url`(191)),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return true;
    }
}
