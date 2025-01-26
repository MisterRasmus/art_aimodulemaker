<?php
/**
 * Validation Handler class
 * @author Ljustema Sverige AB
 */

 namespace PrestaShop\Module\ArtAimodulemaker\ModuleBuilder;

 use Exception;
 use Validate;
 use Configuration;
 use Tools;
 
 class ValidationHandler
 {
    /**
     * Validera moduldata innan generering
     *
     * @param array $moduleData Data att validera
     * @return bool
     * @throws Exception
     */
    public function validateModuleData(array $moduleData): bool
    {
        // Kontrollera obligatoriska fält
        $requiredFields = ['name', 'version', 'description'];
        foreach ($requiredFields as $field) {
            if (empty($moduleData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validera modulnamn
        if (!$this->isValidModuleName($moduleData['name'])) {
            throw new Exception('Invalid module name. Module names must be lowercase, contain only letters, numbers and underscores, and start with a letter.');
        }

        // Validera version
        if (!$this->isValidVersion($moduleData['version'])) {
            throw new Exception('Invalid version format. Use semantic versioning (e.g., 1.0.0)');
        }

        // Validera beskrivning
        if (strlen($moduleData['description']) > 255) {
            throw new Exception('Description is too long. Maximum 255 characters.');
        }

        return true;
    }

    /**
     * Validera PHP-kod
     *
     * @param string $code Kod att validera
     * @return string Validerad kod
     * @throws Exception
     */
    public function validatePhpCode(string $code): string
    {
        // Skapa temporär fil för validering
        $tempFile = tempnam(sys_get_temp_dir(), 'php_validate_');
        file_put_contents($tempFile, $code);

        // Kontrollera syntax
        exec("php -l $tempFile 2>&1", $output, $returnVar);
        unlink($tempFile);

        if ($returnVar !== 0) {
            throw new Exception('PHP syntax error: ' . implode("\n", $output));
        }

        // Kontrollera säkerhetsproblem
        $this->checkSecurityIssues($code);

        // Kontrollera PrestaShop-kompatibilitet
        $this->checkPrestashopCompatibility($code);

        return $code;
    }

    /**
     * Validera modulstruktur
     *
     * @param string $modulePath Sökväg till modul
     * @return bool
     * @throws Exception
     */
    public function validateModuleStructure(string $modulePath): bool
    {
        // Kontrollera nödvändiga filer
        $requiredFiles = [
            'config.xml',
            'index.php',
            'LICENSE'
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($modulePath . '/' . $file)) {
                throw new Exception("Missing required file: $file");
            }
        }

        // Kontrollera nödvändiga mappar
        $requiredDirs = [
            'controllers',
            'views',
            'translations'
        ];

        foreach ($requiredDirs as $dir) {
            if (!is_dir($modulePath . '/' . $dir)) {
                throw new Exception("Missing required directory: $dir");
            }
        }

        // Validera alla PHP-filer
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->validatePhpCode(file_get_contents($file->getPathname()));
            }
        }

        return true;
    }

    /**
     * Kontrollera om ett modulnamn är giltigt
     *
     * @param string $name Modulnamn att kontrollera
     * @return bool
     */
    private function isValidModuleName(string $name): bool
    {
        return (bool)preg_match('/^[a-z][a-z0-9_]+$/', $name);
    }

    /**
     * Kontrollera om en version är giltig
     *
     * @param string $version Version att kontrollera
     * @return bool
     */
    private function isValidVersion(string $version): bool
    {
        return (bool)preg_match('/^\d+\.\d+\.\d+$/', $version);
    }

    /**
     * Kontrollera säkerhetsproblem i kod
     *
     * @param string $code Kod att kontrollera
     * @throws Exception
     */
    private function checkSecurityIssues(string $code): void
    {
        // Lista över osäkra funktioner
        $unsafeFunctions = [
            'eval',
            'exec',
            'passthru',
            'shell_exec',
            'system',
            'proc_open',
            'popen',
            'curl_exec',
            'curl_multi_exec',
            'parse_str',
            'extract'
        ];

        foreach ($unsafeFunctions as $function) {
            if (stripos($code, $function . '(') !== false) {
                throw new Exception("Potentially unsafe function used: $function");
            }
        }

        // Kontrollera direkt användning av superglobals
        $unsafeGlobals = ['$_GET', '$_POST', '$_REQUEST'];
        foreach ($unsafeGlobals as $global) {
            if (strpos($code, $global) !== false) {
                throw new Exception("Direct superglobal usage detected. Use Tools::getValue() instead.");
            }
        }

        // Kontrollera SQL-injektion risker
        if (strpos($code, 'INSERT INTO') !== false || 
            strpos($code, 'UPDATE') !== false || 
            strpos($code, 'DELETE FROM') !== false) {
            if (strpos($code, 'pSQL') === false && 
                strpos($code, 'bqSQL') === false) {
                throw new Exception("SQL queries must use pSQL() or bqSQL() for security.");
            }
        }
    }

    /**
     * Kontrollera PrestaShop-kompatibilitet
     *
     * @param string $code Kod att kontrollera
     * @throws Exception
     */
    private function checkPrestashopCompatibility(string $code): void
    {
        // Kontrollera att _PS_VERSION_ kontroll finns
        if (strpos($code, '!defined(\'_PS_VERSION_\')') === false) {
            throw new Exception('Missing PrestaShop version check.');
        }

        // Kontrollera deprecated funktioner
        $deprecatedFunctions = [
            'mysql_',
            'split(',
            'Tools::displayError(',
            'Tools::p(',
        ];

        foreach ($deprecatedFunctions as $function) {
            if (strpos($code, $function) !== false) {
                throw new Exception("Deprecated function or method used: $function");
            }
        }

        // Kontrollera korrekt användning av hooks
        if (strpos($code, 'extends Module') !== false &&
            strpos($code, 'public function install') !== false &&
            strpos($code, 'registerHook') === false) {
            throw new Exception('Module should register at least one hook.');
        }
    }
}