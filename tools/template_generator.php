<?php
/**
 * Template generator tool
 * @author Ljustema Sverige AB
 */

class TemplateGenerator 
{
    private $moduleData;
    private $templates;

    public function __construct($moduleData) 
    {
        $this->moduleData = $moduleData;
        $this->templates = include dirname(__FILE__) . '/../config/templates.php';
    }

    public function generateFiles() 
    {
        $files = [];
        $moduleType = $this->moduleData['type'];

        if (!isset($this->templates[$moduleType])) {
            throw new Exception('Invalid module type: ' . $moduleType);
        }

        foreach ($this->templates[$moduleType]['files'] as $file) {
            $files[$file] = $this->generateFile($file);
        }

        return $files;
    }

    private function generateFile($path) 
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $method = 'generate' . ucfirst($extension) . 'File';

        if (method_exists($this, $method)) {
            return $this->$method($path);
        }

        throw new Exception('Unsupported file type: ' . $extension);
    }

    private function generatePhpFile($path) 
    {
        $filename = basename($path);
        $template = file_get_contents(dirname(__FILE__) . '/../templates/' . $filename . '.tpl');

        return strtr($template, [
            '{{MODULE_NAME}}' => $this->moduleData['name'],
            '{{MODULE_DISPLAY_NAME}}' => $this->moduleData['display_name'],
            '{{MODULE_VERSION}}' => $this->moduleData['version'],
            '{{MODULE_AUTHOR}}' => $this->moduleData['author'],
            '{{MODULE_DESCRIPTION}}' => $this->moduleData['description'],
            '{{CURRENT_YEAR}}' => date('Y'),
            '{{GENERATED_DATE}}' => date('Y-m-d H:i:s')
        ]);
    }

    private function generateTplFile($path) 
    {
        $filename = basename($path);
        $template = file_get_contents(dirname(__FILE__) . '/../templates/' . $filename . '.tpl');

        return strtr($template, [
            '{{MODULE_NAME}}' => $this->moduleData['name'],
            '{{MODULE_DISPLAY_NAME}}' => $this->moduleData['display_name']
        ]);
    }

    private function generateJsFile($path) 
    {
        $filename = basename($path);
        $template = file_get_contents(dirname(__FILE__) . '/../templates/' . $filename . '.tpl');

        return strtr($template, [
            '{{MODULE_NAME}}' => $this->moduleData['name'],
            '{{MODULE_VERSION}}' => $this->moduleData['version']
        ]);
    }

    private function generateCssFile($path) 
    {
        $filename = basename($path);
        $template = file_get_contents(dirname(__FILE__) . '/../templates/' . $filename . '.tpl');

        return strtr($template, [
            '{{MODULE_NAME}}' => $this->moduleData['name']
        ]);
    }

    public function generateReadme() 
    {
        $template = file_get_contents(dirname(__FILE__) . '/../templates/README.md.tpl');

        return strtr($template, [
            '{{MODULE_NAME}}' => $this->moduleData['name'],
            '{{MODULE_DISPLAY_NAME}}' => $this->moduleData['display_name'],
            '{{MODULE_DESCRIPTION}}' => $this->moduleData['description'],
            '{{MODULE_VERSION}}' => $this->moduleData['version'],
            '{{MODULE_AUTHOR}}' => $this->moduleData['author'],
            '{{CURRENT_YEAR}}' => date('Y'),
            '{{REQUIREMENTS}}' => $this->generateRequirements(),
            '{{FEATURES}}' => $this->generateFeaturesList(),
            '{{INSTALLATION}}' => $this->generateInstallationGuide()
        ]);
    }

    private function generateRequirements() 
    {
        $requirements = [
            'PrestaShop ' . $this->moduleData['ps_version'] . ' or later',
            'PHP ' . $this->moduleData['php_version'] . ' or later'
        ];

        if (!empty($this->moduleData['requirements'])) {
            $requirements = array_merge($requirements, $this->moduleData['requirements']);
        }

        return implode("\n", array_map(function($req) {
            return '* ' . $req;
        }, $requirements));
    }

    private function generateFeaturesList() 
    {
        if (empty($this->moduleData['features'])) {
            return '* Basic module functionality';
        }

        return implode("\n", array_map(function($feature) {
            return '* ' . $feature;
        }, $this->moduleData['features']));
    }

    private function generateInstallationGuide() 
    {
        $steps = [
            'Upload the module folder to your PrestaShop modules directory',
            'Go to the Modules page in your PrestaShop admin panel',
            'Find "' . $this->moduleData['display_name'] . '" in the modules list',
            'Click "Install" button'
        ];

        if (!empty($this->moduleData['installation_steps'])) {
            $steps = array_merge($steps, $this->moduleData['installation_steps']);
        }

        return implode("\n", array_map(function($step) {
            return '1. ' . $step;
        }, $steps));
    }
}