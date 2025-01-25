<?php
/**
 * File Generator class
 * @author Ljustema Sverige AB
 */

class FileGenerator
{
    /**
     * Skapa en fil med innehåll
     *
     * @param string $path Sökväg till filen
     * @param string $content Filens innehåll
     * @return bool
     * @throws Exception
     */
    public function createFile(string $path, string $content): bool
    {
        if (!$this->ensureDirectoryExists(dirname($path))) {
            throw new Exception("Could not create directory: " . dirname($path));
        }

        if (file_put_contents($path, $content) === false) {
            throw new Exception("Could not write to file: $path");
        }

        return true;
    }

    /**
     * Skapa index.php filer i alla undermappar
     *
     * @param string $baseDir Basmapp
     * @param string $content Innehåll för index.php
     * @return void
     */
    public function createIndexFiles(string $baseDir, string $content): void
    {
        $directory = new RecursiveDirectoryIterator($baseDir);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if ($file->isDir() && !$file->isDot()) {
                $indexPath = $file->getPathname() . '/index.php';
                if (!file_exists($indexPath)) {
                    $this->createFile($indexPath, $content);
                }
            }
        }
    }

    /**
     * Ta bort en mapp och allt dess innehåll
     *
     * @param string $dir Mapp att ta bort
     * @return bool
     */
    public function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Skapa en mapp om den inte finns
     *
     * @param string $dir Mapp att skapa
     * @return bool
     */
    private function ensureDirectoryExists(string $dir): bool
    {
        return is_dir($dir) || mkdir($dir, 0755, true);
    }

    /**
     * Kopiera en mapp rekursivt
     *
     * @param string $source Källmapp
     * @param string $dest Målmapp
     * @return bool
     */
    public function copyDirectory(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest, 0755, true)) {
                return false;
            }
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $newDir = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!is_dir($newDir)) {
                    mkdir($newDir);
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }

        return true;
    }

    /**
     * Läs in en mall-fil
     *
     * @param string $templateName Mall att läsa
     * @return string
     * @throws Exception
     */
    public function loadTemplate(string $templateName): string
    {
        $templatePath = _PS_MODULE_DIR_ . 'rl_aimodulemaker/templates/' . $templateName;
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: $templateName");
        }

        return file_get_contents($templatePath);
    }

    /**
     * Ersätt platshållare i en mall
     *
     * @param string $template Mall
     * @param array $variables Variabler att ersätta
     * @return string
     */
    public function replaceTemplateVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * Kontrollera om en fil är skrivbar
     *
     * @param string $path Sökväg till fil
     * @return bool
     */
    public function isWritable(string $path): bool
    {
        if (file_exists($path)) {
            return is_writable($path);
        }

        return is_writable(dirname($path));
    }

    /**
     * Skapa en backup av en fil
     *
     * @param string $path Sökväg till fil
     * @return string Sökväg till backup
     * @throws Exception
     */
    public function createBackup(string $path): string
    {
        if (!file_exists($path)) {
            throw new Exception("File does not exist: $path");
        }

        $backupPath = $path . '.' . date('Y-m-d-His') . '.bak';
        
        if (!copy($path, $backupPath)) {
            throw new Exception("Failed to create backup of: $path");
        }

        return $backupPath;
    }

    /**
     * Validera att en fil har korrekt syntax
     *
     * @param string $path Sökväg till fil
     * @param string $type Filtyp (php, js, css, etc)
     * @return bool
     */
    public function validateFileSyntax(string $path, string $type = 'php'): bool
    {
        switch ($type) {
            case 'php':
                exec("php -l $path 2>&1", $output, $returnVar);
                return $returnVar === 0;

            case 'js':
                if (file_exists(NODE_MODULES_PATH . '/eslint/bin/eslint.js')) {
                    exec("node " . NODE_MODULES_PATH . "/eslint/bin/eslint.js $path 2>&1", $output, $returnVar);
                    return $returnVar === 0;
                }
                return true;

            case 'css':
                if (file_exists(NODE_MODULES_PATH . '/stylelint/bin/stylelint.js')) {
                    exec("node " . NODE_MODULES_PATH . "/stylelint/bin/stylelint.js $path 2>&1", $output, $returnVar);
                    return $returnVar === 0;
                }
                return true;

            case 'json':
                $content = file_get_contents($path);
                json_decode($content);
                return json_last_error() === JSON_ERROR_NONE;

            case 'xml':
                $content = file_get_contents($path);
                libxml_use_internal_errors(true);
                simplexml_load_string($content);
                $errors = libxml_get_errors();
                libxml_clear_errors();
                return empty($errors);

            default:
                return true;
        }
    }

    /**
     * Generera en unik filnamn
     *
     * @param string $baseDir Basmapp
     * @param string $prefix Prefix för filnamn
     * @param string $extension Filändelse
     * @return string
     */
    public function generateUniqueFilename(string $baseDir, string $prefix, string $extension): string
    {
        $counter = 0;
        do {
            $filename = $prefix . ($counter > 0 ? '_' . $counter : '') . '.' . $extension;
            $path = $baseDir . '/' . $filename;
            $counter++;
        } while (file_exists($path));

        return $path;
    }

    /**
     * Lägg till innehåll i början av en fil
     *
     * @param string $path Sökväg till fil
     * @param string $content Innehåll att lägga till
     * @return bool
     */
    public function prependToFile(string $path, string $content): bool
    {
        if (!file_exists($path)) {
            return $this->createFile($path, $content);
        }

        $existingContent = file_get_contents($path);
        return $this->createFile($path, $content . $existingContent);
    }

    /**
     * Lägg till innehåll i slutet av en fil
     *
     * @param string $path Sökväg till fil
     * @param string $content Innehåll att lägga till
     * @return bool
     */
    public function appendToFile(string $path, string $content): bool
    {
        if (!file_exists($path)) {
            return $this->createFile($path, $content);
        }

        return file_put_contents($path, $content, FILE_APPEND) !== false;
    }

    /**
     * Ersätt innehåll i en fil
     *
     * @param string $path Sökväg till fil
     * @param string $search Sök efter
     * @param string $replace Ersätt med
     * @return bool
     */
    public function replaceInFile(string $path, string $search, string $replace): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);
        $content = str_replace($search, $replace, $content);
        return $this->createFile($path, $content);
    }

    /**
     * Skapa en temporär fil
     *
     * @param string $content Innehåll
     * @param string $prefix Prefix för filnamn
     * @return string Sökväg till temporär fil
     */
    public function createTempFile(string $content, string $prefix = 'tmp_'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), $prefix);
        $this->createFile($tempFile, $content);
        return $tempFile;
    }

    /**
     * Ta bort tomma mappar rekursivt
     *
     * @param string $dir Mapp att rensa
     * @return bool
     */
    public function removeEmptyDirectories(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeEmptyDirectories($path);
            }
        }

        if (count(array_diff(scandir($dir), ['.', '..'])) === 0) {
            rmdir($dir);
        }

        return true;
    }
}