<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Art_aimodulemaker extends Module
{
    public function __construct()
    {
        $this->name = 'art_aimodulemaker';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Ljustema Sverige AB';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('AI Module Maker');
        $this->description = $this->l('Create and manage PrestaShop modules with AI assistance');
    }

    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        
        return parent::install() &&
            $this->installTab() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->createTables();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        return parent::uninstall() &&
            $this->uninstallTab() &&
            $this->dropTables();
    }

    private function installTab()
    {
        $tabs = [
            [
                'class_name' => 'AdminArtAiModuleMaker',
                'visible' => true,
                'name' => 'AI Module Maker',
                'parent_class_name' => 'IMPROVE',
            ],
            [
                'class_name' => 'AdminArtAiModuleList',
                'visible' => true,
                'name' => 'Module List',
                'parent_class_name' => 'AdminArtAiModuleMaker',
            ],
            [
                'class_name' => 'AdminArtAiSettings',
                'visible' => true,
                'name' => 'Settings',
                'parent_class_name' => 'AdminArtAiModuleMaker',
            ],
        ];

        foreach ($tabs as $tab) {
            $adminTab = new Tab();
            $adminTab->active = $tab['visible'];
            $adminTab->class_name = $tab['class_name'];
            $adminTab->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $adminTab->name[$lang['id_lang']] = $tab['name'];
            }
            $adminTab->id_parent = (int)Tab::getIdFromClassName($tab['parent_class_name']);
            $adminTab->module = $this->name;
            $adminTab->add();
        }

        return true;
    }

    private function uninstallTab()
    {
        $tabs = ['AdminArtAiModuleMaker', 'AdminArtAiModuleList', 'AdminArtAiSettings'];
        foreach ($tabs as $class_name) {
            $id_tab = (int)Tab::getIdFromClassName($class_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }
        return true;
    }

    private function createTables()
    {
        $sql = [];
        
        // API Keys table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_api_keys` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `api_type` VARCHAR(50) NOT NULL,
            `api_key` TEXT NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        // Modules table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `github_repo` VARCHAR(255),
            `local_path` VARCHAR(255),
            `version` VARCHAR(50),
            `status` VARCHAR(50),
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        // Version history table
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_version_history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `module_id` int(11) NOT NULL,
            `version` VARCHAR(50) NOT NULL,
            `commit_hash` VARCHAR(255),
            `changes` TEXT,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`module_id`) REFERENCES `' . _DB_PREFIX_ . 'art_aimodulemaker_modules` (`id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function dropTables()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_version_history`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_modules`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_aimodulemaker_api_keys`';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function hookActionAdminControllerSetMedia()
    {
        if ($this->context->controller->controller_name === 'AdminArtAiModuleMaker' ||
            $this->context->controller->controller_name === 'AdminArtAiModuleList' ||
            $this->context->controller->controller_name === 'AdminArtAiSettings') {
            
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addJS($this->_path.'views/js/aiChat.js');
            $this->context->controller->addJS($this->_path.'views/js/moduleBuilder.js');
            
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        }
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminArtAiModuleMaker'));
    }
}