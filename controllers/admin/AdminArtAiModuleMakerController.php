<?php
/**
 * Main controller for AI Module Maker
 * @author Ljustema Sverige AB
 */
namespace PrestaShop\Module\ArtAimodulemaker\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class AdminArtAiModuleMakerController extends FrameworkBundleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $this->toolbar_title = $this->l('AI Module Maker');
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'bootstrap' => true,
            'moduleImgUri' => $this->module->getPathUri().'views/img/',
            'moduleActions' => $this->getModuleActions(),
            'apiStatus' => $this->checkApiStatus(),
        ]);

        $this->setTemplate('module_dashboard.tpl');
    }

    protected function getModuleActions()
    {
        return [
            [
                'title' => $this->l('Create New Module'),
                'description' => $this->l('Start creating a new PrestaShop module with AI assistance'),
                'link' => $this->context->link->getAdminLink('AdminArtAiModuleMaker').'&action=createModule',
                'icon' => 'add_circle',
            ],
            [
                'title' => $this->l('Import Existing Module'),
                'description' => $this->l('Import and manage an existing module with AI and Git'),
                'link' => $this->context->link->getAdminLink('AdminArtAiModuleMaker').'&action=importModule',
                'icon' => 'cloud_upload',
            ],
            [
                'title' => $this->l('Module List'),
                'description' => $this->l('View and manage your AI-powered modules'),
                'link' => $this->context->link->getAdminLink('AdminArtAiModuleList'),
                'icon' => 'list',
            ],
            [
                'title' => $this->l('Settings'),
                'description' => $this->l('Configure API keys and module preferences'),
                'link' => $this->context->link->getAdminLink('AdminArtAiSettings'),
                'icon' => 'settings',
            ],
        ];
    }

    protected function checkApiStatus()
    {
        $apiRepository = new ApiKeyRepository();
        return [
            'openai' => $apiRepository->isConfigured('openai'),
            'claude' => $apiRepository->isConfigured('claude'),
            'github' => $apiRepository->isConfigured('github'),
        ];
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('action')) {
            switch (Tools::getValue('action')) {
                case 'createModule':
                    $this->processCreateModule();
                    break;
                    
                case 'importModule':
                    $this->processImportModule();
                    break;

                case 'generateCode':
                    $this->ajaxProcessGenerateCode();
                    break;

                case 'aiChat':
                    $this->ajaxProcessAiChat();
                    break;
            }
        }
    }

    protected function processCreateModule()
    {
        $this->context->smarty->assign([
            'aiModels' => $this->getAvailableAiModels(),
            'moduleTypes' => $this->getModuleTypes(),
            'createModuleToken' => $this->generateCreateModuleToken(),
        ]);

        $this->setTemplate('create_module.tpl');
    }

    protected function processImportModule()
    {
        $this->context->smarty->assign([
            'maxUploadSize' => $this->getMaxUploadSize(),
            'importModuleToken' => $this->generateImportModuleToken(),
        ]);

        $this->setTemplate('import_module.tpl');
    }

    public function ajaxProcessGenerateCode()
    {
        header('Content-Type: application/json');

        try {
            $prompt = Tools::getValue('prompt');
            $aiModel = Tools::getValue('model');
            $moduleContext = Tools::getValue('context');

            $aiHandler = $this->getAiHandler($aiModel);
            $generatedCode = $aiHandler->generateCode($prompt, $moduleContext);

            die(json_encode([
                'success' => true,
                'code' => $generatedCode,
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    public function ajaxProcessAiChat()
    {
        header('Content-Type: application/json');

        try {
            $message = Tools::getValue('message');
            $aiModel = Tools::getValue('model');
            $conversation = Tools::getValue('conversation', []);

            $aiHandler = $this->getAiHandler($aiModel);
            $response = $aiHandler->chat($message, $conversation);

            die(json_encode([
                'success' => true,
                'response' => $response,
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    protected function getAiHandler($model)
    {
        switch ($model) {
            case 'openai':
                return new OpenAiHandler();
            case 'claude':
                return new ClaudeHandler();
            default:
                throw new Exception('Invalid AI model specified');
        }
    }

    protected function getAvailableAiModels()
    {
        $apiRepository = new ApiKeyRepository();
        $models = [];

        if ($apiRepository->isConfigured('openai')) {
            $models[] = ['id' => 'openai', 'name' => 'OpenAI GPT-4'];
        }
        if ($apiRepository->isConfigured('claude')) {
            $models[] = ['id' => 'claude', 'name' => 'Anthropic Claude'];
        }

        return $models;
    }

    protected function getModuleTypes()
    {
        return [
            ['id' => 'payment', 'name' => $this->l('Payment Module')],
            ['id' => 'shipping', 'name' => $this->l('Shipping Module')],
            ['id' => 'analytics', 'name' => $this->l('Analytics Module')],
            ['id' => 'marketplace', 'name' => $this->l('Marketplace Module')],
            ['id' => 'seo', 'name' => $this->l('SEO Module')],
            ['id' => 'custom', 'name' => $this->l('Custom Module')],
        ];
    }

    protected function getMaxUploadSize()
    {
        return min(
            $this->convertPHPSizeToBytes(ini_get('upload_max_filesize')),
            $this->convertPHPSizeToBytes(ini_get('post_max_size'))
        );
    }

    protected function convertPHPSizeToBytes($sSize)
    {
        $sSuffix = strtoupper(substr($sSize, -1));
        if (!in_array($sSuffix, ['P','T','G','M','K'])) {
            return (int)$sSize;
        }
        $iValue = substr($sSize, 0, -1);
        switch ($sSuffix) {
            case 'P':
                $iValue *= 1024;
            case 'T':
                $iValue *= 1024;
            case 'G':
                $iValue *= 1024;
            case 'M':
                $iValue *= 1024;
            case 'K':
                $iValue *= 1024;
                break;
        }
        return (int)$iValue;
    }

    protected function generateCreateModuleToken()
    {
        return Tools::encrypt('create_module_'.time());
    }

    protected function generateImportModuleToken()
    {
        return Tools::encrypt('import_module_'.time());
    }
}