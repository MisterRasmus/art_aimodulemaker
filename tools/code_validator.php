<?php
/**
 * Code validator tool
 * @author Ljustema Sverige AB
 */

class CodeValidator 
{
    private $errors = [];
    private $warnings = [];

    public function validateFile($filePath) 
    {
        if (!file_exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'php':
                return $this->validatePhpFile($filePath);
            case 'js':
                return $this->validateJsFile($filePath);
            case 'css':
                return $this->validateCssFile($filePath);
            case 'tpl':
                return $this->validateTplFile($filePath);
            default:
                throw new Exception('Unsupported file type: ' . $extension);
        }
    }

    private function validatePhpFile($filePath) 
    {
        // Syntax check
        $output = [];
        exec('php -l ' . escapeshellarg($filePath), $output, $return);
        if ($return !== 0) {
            $this->errors[] = 'PHP Syntax Error: ' . implode("\n", $output);
            return false;
        }

        $content = file_get_contents($filePath);

        // PSR-2 validation
        if (strpos($content, '<?=') !== false) {
            $this->warnings[] = 'Short echo tags are not recommended';
        }

        // Security checks
        $securityIssues = $this->checkSecurityIssues($content);
        if (!empty($securityIssues)) {
            $this->errors = array_merge($this->errors, $securityIssues);
        }

        // PrestaShop specific checks
        $this->validatePrestashopCode($content);

        return empty($this->errors);
    }

    private function validateJsFile($filePath) 
    {
        if (file_exists('node_modules/.bin/eslint')) {
            exec('node_modules/.bin/eslint ' . escapeshellarg($filePath), $output, $return);
            if ($return !== 0) {
                $this->errors[] = 'JavaScript validation failed: ' . implode("\n", $output);
                return false;
            }
        }

        $content = file_get_contents($filePath);
        
        // Basic security checks
        if (strpos($content, 'eval(') !== false) {
            $this->errors[] = 'Avoid using eval() in JavaScript';
        }

        return empty($this->errors);
    }

    private function validateCssFile($filePath) 
    {
        if (file_exists('node_modules/.bin/stylelint')) {
            exec('node_modules/.bin/stylelint ' . escapeshellarg($filePath), $output, $return);
            if ($return !== 0) {
                $this->errors[] = 'CSS validation failed: ' . implode("\n", $output);
                return false;
            }
        }

        return true;
    }

    private function validateTplFile($filePath) 
    {
        $content = file_get_contents($filePath);

        // Check for unclosed Smarty tags
        preg_match_all('/{[^}]*$/', $content, $matches);
        if (!empty($matches[0])) {
            $this->errors[] = 'Unclosed Smarty tags found';
            return false;
        }

        // Check for unescaped variables
        preg_match_all('/{[$][^|]*}/', $content, $matches);
        foreach ($matches[0] as $match) {
            if (strpos($match, '|escape') === false) {
                $this->warnings[] = 'Unescaped variable found: ' . $match;
            }
        }

        return empty($this->errors);
    }

    private function checkSecurityIssues($content) 
    {
        $issues = [];
        
        // Check for direct SQL queries without proper escaping
        if (preg_match('/\$sql\s*=\s*["\']SELECT|INSERT|UPDATE|DELETE/i', $content)) {
            if (!preg_match('/pSQL|bqSQL|Db::getInstance\(\)->escape/i', $content)) {
                $issues[] = 'Potential SQL injection: Use pSQL() or Db::getInstance()->escape()';
            }
        }

        // Check for direct superglobal usage
        $superglobals = ['$_GET', '$_POST', '$_REQUEST'];
        foreach ($superglobals as $global) {
            if (strpos($content, $global) !== false) {
                $issues[] = 'Direct superglobal usage found. Use Tools::getValue() instead';
            }
        }

        // Check for potentially dangerous functions
        $dangerousFunctions = [
            'eval', 'exec', 'passthru', 'shell_exec', 'system',
            'proc_open', 'popen', 'cuart_exec', 'cuart_multi_exec'
        ];
        foreach ($dangerousFunctions as $func) {
            if (preg_match('/\b' . $func . '\s*\(/i', $content)) {
                $issues[] = "Potentially dangerous function found: $func()";
            }
        }

        return $issues;
    }

    private function validatePrestashopCode($content) 
    {
        // Check for proper module class definition
        if (strpos($content, 'extends Module') !== false) {
            if (strpos($content, '_PS_VERSION_') === false) {
                $this->errors[] = 'Missing PrestaShop version check';
            }
            if (strpos($content, 'public function install()') !== false && 
                strpos($content, 'parent::install()') === false) {
                $this->errors[] = 'Module install() method must call parent::install()';
            }
        }

        // Check for deprecated functions
        $deprecated = [
            'mysql_',
            'Tools::displayError(',
            'Tools::p(',
            'class_exists(\'Mobile_Detect\')'
        ];
        foreach ($deprecated as $func) {
            if (strpos($content, $func) !== false) {
                $this->warnings[] = "Deprecated function/method found: $func";
            }
        }
    }

    public function getErrors() 
    {
        return $this->errors;
    }

    public function getWarnings() 
    {
        return $this->warnings;
    }

    public function hasIssues() 
    {
        return !empty($this->errors) || !empty($this->warnings);
    }
}