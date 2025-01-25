<?php
/**
 * SQL installation script
 * @author Ljustema Sverige AB
 */

$sql = array();

// API Keys table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_api_keys` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_type` varchar(50) NOT NULL,
    `api_key` text NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `api_type` (`api_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Modules table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `github_repo` varchar(255) DEFAULT NULL,
    `local_path` varchar(255) NOT NULL,
    `version` varchar(50) NOT NULL,
    `status` varchar(50) NOT NULL DEFAULT "development",
    `author` varchar(255) NOT NULL,
    `description` text,
    `is_payment` tinyint(1) NOT NULL DEFAULT 0,
    `is_shipping` tinyint(1) NOT NULL DEFAULT 0,
    `hooks` text,
    `dependencies` text,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Version history table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_version_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `module_id` int(11) NOT NULL,
    `version` varchar(50) NOT NULL,
    `commit_hash` varchar(255) DEFAULT NULL,
    `changes` text,
    `author` varchar(255) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `module_id` (`module_id`),
    CONSTRAINT `art_aimodulemaker_version_history_ibfk_1` 
        FOREIGN KEY (`module_id`) 
        REFERENCES `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (`id`) 
        ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Module files table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `module_id` int(11) NOT NULL,
    `path` varchar(255) NOT NULL,
    `type` varchar(50) NOT NULL,
    `checksum` varchar(32) NOT NULL,
    `last_modified` datetime NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `module_path` (`module_id`, `path`),
    KEY `module_id` (`module_id`),
    CONSTRAINT `art_aimodulemaker_files_ibfk_1` 
        FOREIGN KEY (`module_id`) 
        REFERENCES `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (`id`) 
        ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Module activity log
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_activity_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `module_id` int(11) NOT NULL,
    `action` varchar(50) NOT NULL,
    `details` text,
    `user_id` int(11) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `module_id` (`module_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `art_aimodulemaker_activity_log_ibfk_1` 
        FOREIGN KEY (`module_id`) 
        REFERENCES `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (`id`) 
        ON DELETE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// AI Chat history
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_chat_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `module_id` int(11) DEFAULT NULL,
    `user_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `response` text NOT NULL,
    `model` varchar(50) NOT NULL,
    `context` text,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `module_id` (`module_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `art_aimodulemaker_chat_history_ibfk_1` 
        FOREIGN KEY (`module_id`) 
        REFERENCES `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (`id`) 
        ON DELETE SET NULL
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';