<?php
/**
 * API Controller for AI Module Maker
 * @author Ljustema Sverige AB
 */

namespace PrestaShop\Module\ArtAimodulemaker\Controller\Front;

use PrestaShop\Module\ArtAimodulemaker\AiHandler\ClaudeHandler;
use PrestaShop\Module\ArtAimodulemaker\AiHandler\OpenAiHandler;
use PrestaShop\Module\ArtAimodulemaker\Database\ApiKeyRepository;
use PrestaShop\Module\ArtAimodulemaker\Database\ModuleRepository;

class ArtAimodulemakerApiModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ajax = true;
    
    /** @var bool */
    protected $useSSL = true;

    public function init()
    {
        parent::init();
        
        // Säkerställ att anropet är autentiserat
        if (!$this->isAuthenticated()) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'error' => 'Unauthorized access'
            ]));
        }
    }

    public function initContent()
    {
        parent::initContent();

        $action = Tools::getValue('action');
        $response = [];

        try {
            switch ($action) {
                case 'generate_code':
                    $response = $this->handleGenerateCode();
                    break;

                case 'analyze_module':
                    $response = $this->handleModuleAnalysis();
                    break;

                case 'git_operation':
                    $response = $this->handleGitOperation();
                    break;

                case 'save_module':
                    $response = $this->handleSaveModule();
                    break;

                case 'ai_chat':
                    $response = $this->handleAiChat();
                    break;

                default:
                    throw new Exception('Invalid action specified');
            }

            $this->ajaxDie(json_encode([
                'success' => true,
                'data' => $response
            ]));

        } catch (Exception $e) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }

    protected function isAuthenticated()
    {
        // Kontrollera API-nyckel och admin-token
        $apiKey = Tools::getValue('api_key');
        $token = Tools::getValue('token');

        if (!$apiKey || !$token) {
            return false;
        }

        // Verifiera admin token
        if (!Tools::isPrestashopToken($token)) {
            return false;
        }

        // Verifiera API nyckel mot databasen
        $apiRepository = new ApiKeyRepository();
        return $apiRepository->validateApiKey($apiKey);
    }

    protected function handleGenerateCode()
    {
        $prompt = Tools::getValue('prompt');
        $context = Tools::getValue('context');
        $model = Tools::getValue('model', 'gpt-4');

        // Validera input
        if (!$prompt) {
            throw new Exception('Prompt is required');
        }

        // Välj AI handler baserat på model
        $aiHandler = $this->getAiHandler($model);
        
        // Generera kod
        $generatedCode = $aiHandler->generateCode($prompt, $context);

        return [
            'code' => $generatedCode,
            'model' => $model
        ];
    }

    protected function handleModuleAnalysis()
    {
        $moduleId = (int)Tools::getValue('module_id');
        $analysisType = Tools::getValue('type', 'full');

        if (!$moduleId) {
            throw new Exception('Module ID is required');
        }

        $moduleRepo = new ModuleRepository();
        $module = $moduleRepo->getById($moduleId);

        if (!$module) {
            throw new Exception('Module not found');
        }

        // Utför analys baserat på typ
        switch ($analysisType) {
            case 'code_quality':
                return $this->analyzeCodeQuality($module);
                
            case 'security':
                return $this->analyzeModuleSecurity($module);
                
            case 'performance':
                return $this->analyzeModulePerformance($module);
                
            case 'full':
            default:
                return [
                    'code_quality' => $this->analyzeCodeQuality($module),
                    'security' => $this->analyzeModuleSecurity($module),
                    'performance' => $this->analyzeModulePerformance($module)
                ];
        }
    }

    protected function handleGitOperation()
    {
        $operation = Tools::getValue('git_operation');
        $moduleId = (int)Tools::getValue('module_id');
        $message = Tools::getValue('message');

        if (!$moduleId || !$operation) {
            throw new Exception('Module ID and operation are required');
        }

        $gitHandler = new GitHubHandler();
        
        switch ($operation) {
            case 'commit':
                return $gitHandler->commit($moduleId, $message);
                
            case 'push':
                return $gitHandler->push($moduleId);
                
            case 'pull':
                return $gitHandler->pull($moduleId);
                
            case 'create_branch':
                $branchName = Tools::getValue('branch_name');
                return $gitHandler->createBranch($moduleId, $branchName);
                
            default:
                throw new Exception('Invalid Git operation');
        }
    }

    protected function handleSaveModule()
    {
        $moduleData = Tools::getValue('module_data');
        
        if (!$moduleData || !is_array($moduleData)) {
            throw new Exception('Invalid module data');
        }

        $moduleRepo = new ModuleRepository();
        
        // Om det är en uppdatering
        if (isset($moduleData['id'])) {
            $module = $moduleRepo->update($moduleData['id'], $moduleData);
        } 
        // Om det är en ny modul
        else {
            $module = $moduleRepo->create($moduleData);
        }

        return [
            'module_id' => $module->id,
            'status' => 'saved'
        ];
    }

    protected function handleAiChat()
    {
        $message = Tools::getValue('message');
        $conversation = Tools::getValue('conversation', []);
        $model = Tools::getValue('model', 'gpt-4');
        $moduleId = (int)Tools::getValue('module_id');

        if (!$message) {
            throw new Exception('Message is required');
        }

        $aiHandler = $this->getAiHandler($model);
        
        // Om ett modul-id finns, lägg till modulkontext
        if ($moduleId) {
            $moduleRepo = new ModuleRepository();
            $module = $moduleRepo->getById($moduleId);
            if ($module) {
                $conversation = array_merge($this->getModuleContext($module), $conversation);
            }
        }

        $response = $aiHandler->chat($message, $conversation);

        return [
            'response' => $response,
            'model' => $model
        ];
    }

    protected function getAiHandler($model)
    {
        switch ($model) {
            case 'gpt-4':
            case 'gpt-3.5-turbo':
                return new OpenAiHandler();
                
            case 'claude-3-opus-20240229':
            case 'claude-3-sonnet-20240229':
                return new ClaudeHandler();
                
            default:
                throw new Exception('Invalid AI model specified');
        }
    }

    protected function analyzeCodeQuality($module)
    {
        // Implementera kodkvalitetsanalys
        $analyzer = new CodeQualityAnalyzer();
        return $analyzer->analyze($module->getFiles());
    }

    protected function analyzeModuleSecurity($module)
    {
        // Implementera säkerhetsanalys
        $analyzer = new SecurityAnalyzer();
        return $analyzer->analyze($module->getFiles());
    }

    protected function analyzeModulePerformance($module)
    {
        // Implementera prestanda-analys
        $analyzer = new PerformanceAnalyzer();
        return $analyzer->analyze($module->getFiles());
    }

    protected function getModuleContext($module)
    {
        return [
            'module_name' => $module->name,
            'file_structure' => $module->getFileStructure(),
            'dependencies' => $module->getDependencies(),
            'hooks' => $module->getHooks()
        ];
    }
}