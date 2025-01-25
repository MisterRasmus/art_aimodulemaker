<?php
/**
 * SQL uninstallation script
 * @author Ljustema Sverige AB
 */

$sql = array();

// Drop tables in correct order due to foreign key constraints
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_chat_history`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_activity_log`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_files`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_version_history`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_modules`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_api_keys`';