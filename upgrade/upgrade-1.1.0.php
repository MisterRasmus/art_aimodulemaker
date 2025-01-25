<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    $sql = [];

    // Add new columns to version history table
    $sql[] = "ALTER TABLE `" . _DB_PREFIX_ . "art_aimodulemaker_version_history`
              ADD COLUMN `commit_message` TEXT AFTER `commit_hash`,
              ADD COLUMN `branch_name` VARCHAR(255) AFTER `commit_message`";

    // Add new indexes for better performance
    $sql[] = "ALTER TABLE `" . _DB_PREFIX_ . "art_aimodulemaker_modules`
              ADD INDEX `status_index` (`status`),
              ADD INDEX `date_add_index` (`date_add`)";

    // Add new table for module templates
    $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "art_aimodulemaker_templates` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text,
        `type` varchar(50) NOT NULL,
        `content` longtext NOT NULL,
        `is_default` tinyint(1) NOT NULL DEFAULT 0,
        `date_add` datetime NOT NULL,
        `date_upd` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";

    // Add new configuration values
    $result = true;
    foreach ($sql as $query) {
        $result &= Db::getInstance()->execute($query);
    }

    // Update configuration values
    $result &= Configuration::updateValue('ARTAIMODULEMAKER_DEFAULT_AUTHOR', 'Ljustema Sverige AB');
    $result &= Configuration::updateValue('ARTAIMODULEMAKER_AUTO_COMMIT', true);
    $result &= Configuration::updateValue('ARTAIMODULEMAKER_ENABLE_CACHING', true);

    // Register new hooks
    $result &= $module->registerHook('actionModuleRegisterHookAfter');
    $result &= $module->registerHook('actionModuleUnRegisterHookAfter');

    // Create new directories if needed
    $dirs = [
        'templates',
        'cache',
        'exports'
    ];

    foreach ($dirs as $dir) {
        $path = _PS_MODULE_DIR_ . $module->name . '/' . $dir;
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    // Create .htaccess files for security
    $htaccess = "deny from all\n";
    foreach ($dirs as $dir) {
        $path = _PS_MODULE_DIR_ . $module->name . '/' . $dir . '/.htaccess';
        file_put_contents($path, $htaccess);
    }

    // Clear cache
    if (file_exists(_PS_MODULE_DIR_ . $module->name . '/cache')) {
        Tools::deleteDirectory(_PS_MODULE_DIR_ . $module->name . '/cache', true);
    }

    return $result;
}