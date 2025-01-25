<?php
/**
 * Module Generator class
 * @author Ljustema Sverige AB
 */

class ModuleGenerator
{
    /** @var array */
    private $moduleData;

    /** @var string */
    private $outputPath;

    /** @var FileGenerator */
    private $fileGenerator;

    /** @var ValidationHandler */
    private $validator;

    /** @var AiHandlerInterface */
    private $aiHandler;

    public function __construct(array $moduleData, string $outputPath)
    {
        $this->moduleData = $moduleData;
        $this->outputPath = $outputPath;
        $this->fileGenerator = new FileGenerator();
        $this->validator = new ValidationHandler();
        
        // Välj AI handler baserat på konfiguration
        $aiModel = Configuration::get('ARTAIMODULEMAKER_DEFAULT_AI', 'openai');
        $this->aiHandler = $aiModel === 'claude' ? new ClaudeHandler() : new OpenAiHandler();
    }

    /**
     * Generera en ny modul
     *
     * @return bool
     * @throws Exception
     */
    public function generateModule(): bool
    {
        try {
            // Validera moduldata
            $this->validator->validateModuleData($this->moduleData);

            // Skapa modulstruktur
            $this->createModuleStructure();

            // Generera grundfiler
            $this->generateBaseFiles();

            // Generera specifika filer baserat på modultyp
            $this->generateSpecificFiles();

            // Skapa GitHub repository om konfigurerat
            if ($this->shouldCreateGitRepo()) {
                $this->initializeGitRepository();
            }

            // Generera VS Code workspace om det behövs
            if ($this->moduleData['create_workspace'] ?? false) {
                $this->createVSCodeWorkspace();
            }

            return true;

        } catch (Exception $e) {
            // Ta bort ofullständig modul vid fel
            $this->cleanup();
            throw $e;
        }
    }

    /**
     * Skapa modulens mappstruktur
     */
    private function createModuleStructure(): void
    {
        $directories = [
            $this->outputPath,
            $this->outputPath . '/controllers',
            $this->outputPath . '/controllers/admin',
            $this->outputPath . '/controllers/front',
            $this->outputPath . '/classes',
            $this->outputPath . '/views',
            $this->outputPath . '/views/templates',
            $this->outputPath . '/views/templates/admin',
            $this->outputPath . '/views/templates/front',
            $this->outputPath . '/views/css',
            $this->outputPath . '/views/js',
            $this->outputPath . '/sql',
            $this->outputPath . '/translations',
            $this->outputPath . '/config',
            $this->outputPath . '/upgrade'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
    }

    /**
     * Generera modulens grundfiler
     */
    private function generateBaseFiles(): void
    {
        // Huvudmodulfil
        $mainModuleContent = $this->generateMainModuleFile();
        $this->fileGenerator->createFile(
            $this->outputPath . '/' . $this->moduleData['name'] . '.php',
            $mainModuleContent
        );

        // Konfigurationsfil
        $configXmlContent = $this->generateConfigXml();
        $this->fileGenerator->createFile(
            $this->outputPath . '/config.xml',
            $configXmlContent
        );

        // SQL-filer
        $this->fileGenerator->createFile(
            $this->outputPath . '/sql/install.php',
            $this->generateInstallSQL()
        );
        
        $this->fileGenerator->createFile(
            $this->outputPath . '/sql/uninstall.php',
            $this->generateUninstallSQL()
        );

        // Index-filer för säkerhet
        $indexContent = $this->generateIndexFile();
        $this->fileGenerator->createIndexFiles($this->outputPath, $indexContent);

        // License och README
        $this->fileGenerator->createFile(
            $this->outputPath . '/license.txt',
            $this->generateLicense()
        );
        
        $this->fileGenerator->createFile(
            $this->outputPath . '/README.md',
            $this->generateReadme()
        );
    }

    /**
     * Generera specifika filer baserat på modultyp
     */
    private function generateSpecificFiles(): void
    {
        switch ($this->moduleData['type']) {
            case 'payment':
                $this->generatePaymentModuleFiles();
                break;
            
            case 'shipping':
                $this->generateShippingModuleFiles();
                break;
            
            case 'analytics':
                $this->generateAnalyticsModuleFiles();
                break;
            
            case 'marketplace':
                $this->generateMarketplaceModuleFiles();
                break;
            
            case 'seo':
                $this->generateSeoModuleFiles();
                break;
        }
    }

    /**
     * Initiera Git repository
     */
    private function initializeGitRepository(): void
    {
        $gitHandler = new GitHubHandler();
        $gitHandler->initRepository(
            $this->moduleData['name'],
            $this->outputPath
        );
    }

    /**
     * Skapa VS Code workspace
     */
    private function createVSCodeWorkspace(): void
    {
        $workspace = [
            'folders' => [
                [
                    'path' => '.'
                ]
            ],
            'settings' => [
                'files.exclude' => [
                    '**/.git' => true,
                    '**/.DS_Store' => true
                ],
                'php.suggest.basic' => true,
                'php.validate.enable' => true
            ],
            'extensions' => [
                'recommendations' => [
                    'bmewburn.vscode-intelephense-client',
                    'neilbrayfield.php-docblocker',
                    'mrmlnc.vscode-duplicate'
                ]
            ]
        ];

        $this->fileGenerator->createFile(
            $this->outputPath . '/' . $this->moduleData['name'] . '.code-workspace',
            json_encode($workspace, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Generera huvudmodulfilen med AI-assistans
     */
    private function generateMainModuleFile(): string
    {
        $prompt = "Create a PrestaShop module main file with the following specifications:\n";
        $prompt .= "Module Name: " . $this->moduleData['name'] . "\n";
        $prompt .= "Description: " . $this->moduleData['description'] . "\n";
        $prompt .= "Type: " . $this->moduleData['type'] . "\n";
        $prompt .= "Version: " . $this->moduleData['version'] . "\n";
        
        $code = $this->aiHandler->generateCode($prompt, [
            'task' => 'module_main_file',
            'format' => 'php'
        ]);

        return $this->validator->validatePhpCode($code);
    }

    /**
     * Städa upp vid fel
     */
    private function cleanup(): void
    {
        if (is_dir($this->outputPath)) {
            $this->fileGenerator->deleteDirectory($this->outputPath);
        }
    }

    private function shouldCreateGitRepo(): bool
    {
        return isset($this->moduleData['create_git_repo']) && 
               $this->moduleData['create_git_repo'] === true &&
               Configuration::get('ARTAIMODULEMAKER_GITHUB_TOKEN');
    }

    /**
     * Generera config.xml
     */
    private function generateConfigXml(): string
    {
        $template = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<module>
    <name>{$this->moduleData['name']}</name>
    <displayName><![CDATA[{$this->moduleData['display_name']}]]></displayName>
    <version><![CDATA[{$this->moduleData['version']}]]></version>
    <description><![CDATA[{$this->moduleData['description']}]]></description>
    <author><![CDATA[Ljustema Sverige AB]]></author>
    <tab><![CDATA[{$this->moduleData['tab']}]]></tab>
    <is_configurable>1</is_configurable>
    <need_instance>1</need_instance>
    <limited_countries></limited_countries>
</module>
XML;
        return $template;
    }

    /**
     * Generera install.php för SQL
     */
    private function generateInstallSQL(): string
    {
        $tables = $this->moduleData['tables'] ?? [];
        $sql = "<?php\n\$sql = array();\n\n";

        foreach ($tables as $table) {
            $sql .= "\$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . '{$table['name']}` (\n";
            foreach ($table['fields'] as $field) {
                $sql .= "    `{$field['name']}` {$field['type']},\n";
            }
            $sql .= "    PRIMARY KEY (`{$table['primary_key']}`)\n";
            $sql .= ") ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';\n\n";
        }

        return $sql;
    }

    /**
     * Generera uninstall.php för SQL
     */
    private function generateUninstallSQL(): string
    {
        $tables = $this->moduleData['tables'] ?? [];
        $sql = "<?php\n\$sql = array();\n\n";

        foreach ($tables as $table) {
            $sql .= "\$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . '{$table['name']}`;';\n";
        }

        return $sql;
    }

    /**
     * Generera index.php säkerhetsfil
     */
    private function generateIndexFile(): string
    {
        return <<<PHP
<?php
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Location: ../');
exit;
PHP;
    }

    /**
     * Generera README.md
     */
    private function generateReadme(): string
    {
        return <<<MARKDOWN
# {$this->moduleData['display_name']}

## Description
{$this->moduleData['description']}

## Features
{$this->generateFeaturesList()}

## Installation
1. Upload the module to your PrestaShop modules directory
2. Install the module through PrestaShop admin panel
3. Configure the module settings as needed

## Configuration
{$this->generateConfigurationGuide()}

## Requirements
- PrestaShop {$this->moduleData['ps_version']} or later
- PHP {$this->moduleData['php_version']} or later

## Author
Ljustema Sverige AB

## License
Commercial License - All rights reserved
MARKDOWN;
    }

    /**
     * Generera licensfil
     */
    private function generateLicense(): string
    {
        return <<<LICENSE
Commercial License

Copyright (c) 2024 Ljustema Sverige AB
All rights reserved.

This module is part of a commercial product by Ljustema Sverige AB.
Unauthorized copying, modification, distribution, or use of this software is strictly prohibited.
LICENSE;
    }

    /**
     * Generera lista över funktioner för README
     */
    private function generateFeaturesList(): string
    {
        $features = $this->moduleData['features'] ?? [];
        if (empty($features)) {
            return "- Basic module functionality\n- PrestaShop compatibility\n- Easy configuration";
        }

        return implode("\n", array_map(function($feature) {
            return "- " . $feature;
        }, $features));
    }

    /**
     * Generera konfigurationsguide för README
     */
    private function generateConfigurationGuide(): string
    {
        $settings = $this->moduleData['settings'] ?? [];
        if (empty($settings)) {
            return "No specific configuration needed.";
        }

        $guide = "Configure the following settings in the module configuration page:\n\n";
        foreach ($settings as $setting) {
            $guide .= "- {$setting['name']}: {$setting['description']}\n";
        }
        return $guide;
    }

    /**
     * Generera betalningsmodulfiler
     */
    private function generatePaymentModuleFiles(): void
    {
        // Generera controllers
        $this->fileGenerator->createFile(
            $this->outputPath . '/controllers/front/payment.php',
            $this->generatePaymentController()
        );

        $this->fileGenerator->createFile(
            $this->outputPath . '/controllers/front/validation.php',
            $this->generateValidationController()
        );

        // Generera templates
        $this->fileGenerator->createFile(
            $this->outputPath . '/views/templates/front/payment.tpl',
            $this->generatePaymentTemplate()
        );

        $this->fileGenerator->createFile(
            $this->outputPath . '/views/templates/hook/payment.tpl',
            $this->generatePaymentHookTemplate()
        );
    }

    /**
     * Generera fraktmodulfiler
     */
    private function generateShippingModuleFiles(): void
    {
        $this->fileGenerator->createFile(
            $this->outputPath . '/controllers/front/carrier.php',
            $this->generateCarrierController()
        );

        $this->fileGenerator->createFile(
            $this->outputPath . '/classes/ShippingCalculator.php',
            $this->generateShippingCalculator()
        );
    }

    /**
     * Generera analysfiler
     */
    private function generateAnalyticsModuleFiles(): void
    {
        $this->fileGenerator->createFile(
            $this->outputPath . '/classes/AnalyticsTracker.php',
            $this->generateAnalyticsTracker()
        );

        $this->fileGenerator->createFile(
            $this->outputPath . '/views/js/tracking.js',
            $this->generateTrackingScript()
        );
    }

    /**
     * Generera marketplace-filer
     */
    private function generateMarketplaceModuleFiles(): void
    {
        $this->fileGenerator->createFile(
            $this->outputPath . '/controllers/front/seller.php',
            $this->generateSellerController()
        );

        $this->fileGenerator->createFile(
            $this->outputPath . '/classes/MarketplaceManager.php',
            $this->generateMarketplaceManager()
        );
    }

    /**
     * Generera SEO-filer
     */
    private function generateSeoModuleFiles(): void
    {
        $this->fileGenerator->createFile(
            $this->outputPath . '/classes/SeoOptimizer.php',
            $this->generateSeoOptimizer()
        );

        $this->fileGenerator->createFile(
            $this->outputPath . '/views/js/seo.js',
            $this->generateSeoScript()
        );
    }
}