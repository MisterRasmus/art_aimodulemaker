<?php
/**
 * Controller for managing AI Module Maker settings
 * @author Ljustema Sverige AB
 */
namespace PrestaShop\Module\RlAimodulemaker\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class AdminRlAiSettingsController extends FrameworkBundleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $this->toolbar_title = $this->l('AI Module Maker Settings');
    }

    public function initContent()
    {
        parent::initContent();

        // Hämta aktuella inställningar
        $apiRepository = new ApiKeyRepository();
        $currentSettings = $apiRepository->getAllApiKeys();

        $this->context->smarty->assign([
            'settingsForm' => $this->renderSettingsForm(),
            'currentSettings' => $currentSettings,
            'apiTestResults' => $this->getApiTestResults(),
            'gitHubRepoPath' => $this->getDefaultGitHubRepoPath(),
            'baseModulePath' => _PS_MODULE_DIR_
        ]);

        $this->setTemplate('settings.tpl');
    }

    protected function renderSettingsForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRlAiSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminRlAiSettings');
        $helper->token = Tools::getAdminTokenLite('AdminRlAiSettings');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'tabs' => [
                    'api' => $this->l('API Settings'),
                    'github' => $this->l('GitHub Settings'),
                    'general' => $this->l('General Settings'),
                ],
                'input' => [
                    // API Settings Tab
                    [
                        'tab' => 'api',
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'RLAIMODULEMAKER_OPENAI_API_KEY',
                        'label' => $this->l('OpenAI API Key'),
                        'desc' => $this->l('Enter your OpenAI API key'),
                        'class' => 'fixed-width-xxl',
                        'hint' => $this->l('Get your API key from OpenAI dashboard'),
                    ],
                    [
                        'tab' => 'api',
                        'type' => 'select',
                        'name' => 'RLAIMODULEMAKER_OPENAI_MODEL',
                        'label' => $this->l('OpenAI Model'),
                        'options' => [
                            'query' => [
                                ['id' => 'gpt-4', 'name' => 'GPT-4'],
                                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'tab' => 'api',
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'RLAIMODULEMAKER_CLAUDE_API_KEY',
                        'label' => $this->l('Claude API Key'),
                        'desc' => $this->l('Enter your Anthropic Claude API key'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'tab' => 'api',
                        'type' => 'select',
                        'name' => 'RLAIMODULEMAKER_CLAUDE_MODEL',
                        'label' => $this->l('Claude Model'),
                        'options' => [
                            'query' => [
                                ['id' => 'claude-3-opus-20240229', 'name' => 'Claude-3 Opus'],
                                ['id' => 'claude-3-sonnet-20240229', 'name' => 'Claude-3 Sonnet'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    
                    // GitHub Settings Tab
                    [
                        'tab' => 'github',
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-github"></i>',
                        'name' => 'RLAIMODULEMAKER_GITHUB_TOKEN',
                        'label' => $this->l('GitHub Personal Access Token'),
                        'desc' => $this->l('Enter your GitHub personal access token'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'tab' => 'github',
                        'type' => 'text',
                        'name' => 'RLAIMODULEMAKER_GITHUB_USERNAME',
                        'label' => $this->l('GitHub Username'),
                        'desc' => $this->l('Your GitHub username for repository creation'),
                    ],
                    [
                        'tab' => 'github',
                        'type' => 'text',
                        'name' => 'RLAIMODULEMAKER_GITHUB_ORG',
                        'label' => $this->l('GitHub Organization'),
                        'desc' => $this->l('Optional: GitHub organization name'),
                    ],
                    
                    // General Settings Tab
                    [
                        'tab' => 'general',
                        'type' => 'switch',
                        'name' => 'RLAIMODULEMAKER_AUTO_COMMIT',
                        'label' => $this->l('Auto Commit Changes'),
                        'desc' => $this->l('Automatically commit changes to GitHub'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ]
                    ],
                    [
                        'tab' => 'general',
                        'type' => 'text',
                        'name' => 'RLAIMODULEMAKER_DEFAULT_AUTHOR',
                        'label' => $this->l('Default Module Author'),
                        'desc' => $this->l('Default author name for new modules'),
                        'value' => 'Ljustema Sverige AB'
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [
            'RLAIMODULEMAKER_OPENAI_API_KEY' => Configuration::get('RLAIMODULEMAKER_OPENAI_API_KEY'),
            'RLAIMODULEMAKER_OPENAI_MODEL' => Configuration::get('RLAIMODULEMAKER_OPENAI_MODEL', 'gpt-4'),
            'RLAIMODULEMAKER_CLAUDE_API_KEY' => Configuration::get('RLAIMODULEMAKER_CLAUDE_API_KEY'),
            'RLAIMODULEMAKER_CLAUDE_MODEL' => Configuration::get('RLAIMODULEMAKER_CLAUDE_MODEL', 'claude-3-opus-20240229'),
            'RLAIMODULEMAKER_GITHUB_TOKEN' => Configuration::get('RLAIMODULEMAKER_GITHUB_TOKEN'),
            'RLAIMODULEMAKER_GITHUB_USERNAME' => Configuration::get('RLAIMODULEMAKER_GITHUB_USERNAME'),
            'RLAIMODULEMAKER_GITHUB_ORG' => Configuration::get('RLAIMODULEMAKER_GITHUB_ORG'),
            'RLAIMODULEMAKER_AUTO_COMMIT' => Configuration::get('RLAIMODULEMAKER_AUTO_COMMIT', 1),
            'RLAIMODULEMAKER_DEFAULT_AUTHOR' => Configuration::get('RLAIMODULEMAKER_DEFAULT_AUTHOR', 'Ljustema Sverige AB'),
        ];

        return $fields;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitRlAiSettings')) {
            $this->processConfiguration();
        }

        if (Tools::isSubmit('testApi')) {
            $this->processApiTest();
        }

        parent::postProcess();
    }

    protected function processConfiguration()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        // Uppdatera API nycklar säkert
        $apiRepository = new ApiKeyRepository();
        
        if ($openaiKey = Tools::getValue('RLAIMODULEMAKER_OPENAI_API_KEY')) {
            $apiRepository->updateApiKey('openai', $openaiKey);
        }
        
        if ($claudeKey = Tools::getValue('RLAIMODULEMAKER_CLAUDE_API_KEY')) {
            $apiRepository->updateApiKey('claude', $claudeKey);
        }
        
        if ($githubToken = Tools::getValue('RLAIMODULEMAKER_GITHUB_TOKEN')) {
            $apiRepository->updateApiKey('github', $githubToken);
        }

        $this->confirmations[] = $this->l('Settings updated successfully');
    }

    protected function processApiTest()
    {
        $apiType = Tools::getValue('api_type');
        $results = [];

        try {
            switch ($apiType) {
                case 'openai':
                    $handler = new OpenAiHandler();
                    $results = $handler->testConnection();
                    break;
                    
                case 'claude':
                    $handler = new ClaudeHandler();
                    $results = $handler->testConnection();
                    break;
                    
                case 'github':
                    $handler = new GitHubHandler();
                    $results = $handler->testConnection();
                    break;
            }

            $this->confirmations[] = sprintf($this->l('Successfully tested %s API connection'), $apiType);
            
        } catch (Exception $e) {
            $this->errors[] = sprintf($this->l('Failed to test %s API: %s'), $apiType, $e->getMessage());
        }

        return $results;
    }

    protected function getApiTestResults()
    {
        $results = [];
        $apiRepository = new ApiKeyRepository();

        foreach (['openai', 'claude', 'github'] as $api) {
            $results[$api] = [
                'configured' => $apiRepository->isConfigured($api),
                'lastTest' => Configuration::get('RLAIMODULEMAKER_' . strtoupper($api) . '_LAST_TEST'),
                'status' => Configuration::get('RLAIMODULEMAKER_' . strtoupper($api) . '_STATUS')
            ];
        }

        return $results;
    }

    protected function getDefaultGitHubRepoPath()
    {
        $username = Configuration::get('RLAIMODULEMAKER_GITHUB_USERNAME');
        $org = Configuration::get('RLAIMODULEMAKER_GITHUB_ORG');
        
        return ($org ? $org : $username) . '/prestashop-modules';
    }
}