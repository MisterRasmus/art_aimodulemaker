<?php
/**
 * Controller for managing AI-powered modules
 * @author Ljustema Sverige AB
 */

 namespace PrestaShop\Module\RlAimodulemaker\Controller\Admin;

 use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
 
 class AdminRlAiModuleListController extends FrameworkBundleAdminController
 {
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'rl_aimodulemaker_modules';
        $this->className = 'ModuleRepository';
        $this->lang = false;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowAction('export');
        $this->addRowAction('github');
        $this->addRowAction('vscode');

        parent::__construct();

        $this->fields_list = [
            'id' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Module Name'),
                'filter_key' => 'a!name'
            ],
            'version' => [
                'title' => $this->l('Version'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'status' => [
                'title' => $this->l('Status'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'callback' => 'getStatusDisplay'
            ],
            'github_repo' => [
                'title' => $this->l('GitHub Repository'),
                'callback' => 'getGitHubLink'
            ],
            'date_add' => [
                'title' => $this->l('Created'),
                'type' => 'datetime'
            ],
            'date_upd' => [
                'title' => $this->l('Last Update'),
                'type' => 'datetime'
            ]
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            ],
            'export' => [
                'text' => $this->l('Export selected'),
                'icon' => 'icon-cloud-download'
            ]
        ];

        $this->_select = 'a.*';
        $this->_orderBy = 'date_upd';
        $this->_orderWay = 'DESC';
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_module'] = [
                'href' => self::$currentIndex.'&addmodule&token='.$this->token,
                'desc' => $this->l('Add New Module'),
                'icon' => 'process-icon-new'
            ];
            $this->page_header_toolbar_btn['import_module'] = [
                'href' => self::$currentIndex.'&importmodule&token='.$this->token,
                'desc' => $this->l('Import Module'),
                'icon' => 'process-icon-import'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        // Add custom CSS and JS for the list
        $this->addCSS($this->module->getPathUri().'views/css/admin.css');
        $this->addJS($this->module->getPathUri().'views/js/moduleList.js');

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Module Information'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Module Name'),
                    'name' => 'name',
                    'required' => true,
                    'desc' => $this->l('The technical name of your module')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Version'),
                    'name' => 'version',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('GitHub Repository'),
                    'name' => 'github_repo',
                    'desc' => $this->l('GitHub repository URL')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Status'),
                    'name' => 'status',
                    'options' => [
                        'query' => $this->getStatusOptions(),
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        return parent::renderForm();
    }

    protected function getStatusOptions()
    {
        return [
            ['id' => 'development', 'name' => $this->l('In Development')],
            ['id' => 'testing', 'name' => $this->l('Testing')],
            ['id' => 'production', 'name' => $this->l('Production')],
            ['id' => 'archived', 'name' => $this->l('Archived')]
        ];
    }

    public function getStatusDisplay($status, $row)
    {
        $statusClasses = [
            'development' => 'info',
            'testing' => 'warning',
            'production' => 'success',
            'archived' => 'secondary'
        ];

        $class = isset($statusClasses[$status]) ? $statusClasses[$status] : 'default';
        return '<span class="badge badge-'.$class.'">'.$status.'</span>';
    }

    public function getGitHubLink($url, $row)
    {
        if (empty($url)) {
            return '-';
        }
        return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url.'</a>';
    }

    public function processSave()
    {
        $object = parent::processSave();

        if (Validate::isLoadedObject($object)) {
            // Update GitHub repository if needed
            if (Tools::getValue('github_repo')) {
                $gitHandler = new GitHubHandler();
                $gitHandler->updateRepository($object);
            }

            // Generate VS Code workspace if needed
            if (Tools::getValue('generate_workspace')) {
                $this->generateVSCodeWorkspace($object);
            }
        }

        return $object;
    }

    protected function generateVSCodeWorkspace($module)
    {
        try {
            $workspace = [
                'folders' => [
                    [
                        'path' => $module->local_path
                    ]
                ],
                'settings' => [
                    'files.exclude' => [
                        '**/.git' => true,
                        '**/.DS_Store' => true
                    ],
                    'php.suggest.basic' => true
                ]
            ];
    
            $workspaceFile = $module->local_path . '/' . $module->name . '.code-workspace';
            file_put_contents($workspaceFile, json_encode($workspace, JSON_PRETTY_PRINT));
    
            return true;
        } catch (Exception $e) {
            $this->errors[] = $this->l('Failed to generate VS Code workspace: ') . $e->getMessage();
            return false;
        }
    }

    public function processExport()
    {
        $moduleId = Tools::getValue('id_module');
        
        if (!$moduleId) {
            $this->errors[] = $this->l('No module selected for export');
            return false;
        }

        try {
            $module = new ModuleRepository($moduleId);
            $zipFile = $this->module->getLocalPath() . 'export/' . $module->name . '.zip';
            
            // Create export directory if it doesn't exist
            if (!file_exists(dirname($zipFile))) {
                mkdir(dirname($zipFile), 0777, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
                throw new Exception($this->l('Unable to create ZIP file'));
            }

            // Add module files to ZIP
            $this->addFolderToZip($zip, $module->local_path, '');
            $zip->close();

            // Send file to browser
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$module->name.'.zip"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
            unlink($zipFile);
            exit;

        } catch (Exception $e) {
            $this->errors[] = $this->l('Export failed: ') . $e->getMessage();
            return false;
        }
    }

    protected function addFolderToZip($zip, $folder, $zipFolder)
    {
        $handle = opendir($folder);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                $filePath = $folder . '/' . $entry;
                $zipPath = $zipFolder . ($zipFolder ? '/' : '') . $entry;

                if (is_file($filePath)) {
                    $zip->addFile($filePath, $zipPath);
                } elseif (is_dir($filePath)) {
                    $zip->addEmptyDir($zipPath);
                    $this->addFolderToZip($zip, $filePath, $zipPath);
                }
            }
        }
        closedir($handle);
    }
}