<?php
/**
 * GitHub integration handler
 * @author Ljustema Sverige AB
 */

namespace PrestaShop\Module\ArtAimodulemaker\GitHandler;
// Lägg till överst i filen
use PrestaShop\Module\ArtAimodulemaker\Database\ModuleRepository;

class GitHubHandler implements GitHandlerInterface
{
    /** @var string */
    private $token;

    /** @var string */
    private $username;

    /** @var string */
    private $organization;

    /** @var array */
    private $defaultHeaders;

    /** @var ModuleRepository */
    private $moduleRepository;

    public function __construct()
    {
        $this->token = Configuration::get('ARTAIMODULEMAKER_GITHUB_TOKEN');
        $this->username = Configuration::get('ARTAIMODULEMAKER_GITHUB_USERNAME');
        $this->organization = Configuration::get('ARTAIMODULEMAKER_GITHUB_ORG');
        
        $this->defaultHeaders = [
            'Authorization: token ' . $this->token,
            'Accept: application/vnd.github.v3+json',
            'User-Agent: AI Module Maker',
        ];

        $this->moduleRepository = new ModuleRepository();
    }

    public function initRepository(string $moduleName, string $localPath): bool
    {
        // Skapa repo på GitHub först
        $repoData = [
            'name' => $moduleName,
            'description' => 'PrestaShop module created with AI Module Maker',
            'private' => true,
            'auto_init' => true,
        ];

        $owner = $this->organization ?: $this->username;
        $response = $this->makeApiRequest("https://api.github.com/user/repos", $repoData);

        if (!isset($response['clone_url'])) {
            throw new Exception('Failed to create GitHub repository');
        }

        // Initiera lokalt repo
        $commands = [
            'git init',
            'git remote add origin ' . $response['clone_url'],
            'git add .',
            'git commit -m "Initial commit"',
            'git push -u origin main'
        ];

        foreach ($commands as $command) {
            $this->executeGitCommand($command, $localPath);
        }

        return true;
    }

    public function cloneRepository(string $repoUrl, string $localPath): bool
    {
        $command = "git clone {$repoUrl} {$localPath}";
        $this->executeGitCommand($command);
        return true;
    }

    public function commit(int $moduleId, string $message): string
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        // Add all changes
        $this->executeGitCommand('git add .', $module->local_path);

        // Create commit
        $commitCommand = sprintf('git commit -m "%s"', addslashes($message));
        $output = $this->executeGitCommand($commitCommand, $module->local_path);

        // Extract commit hash
        if (preg_match('/\[([^\]]+)\s+([a-f0-9]+)\]/', $output, $matches)) {
            return $matches[2];
        }

        throw new Exception('Failed to extract commit hash');
    }

    public function push(int $moduleId, string $branch = ''): bool
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        $command = 'git push';
        if ($branch) {
            $command .= ' origin ' . escapeshellarg($branch);
        }

        $this->executeGitCommand($command, $module->local_path);
        return true;
    }

    public function pull(int $moduleId, string $branch = ''): bool
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        $command = 'git pull';
        if ($branch) {
            $command .= ' origin ' . escapeshellarg($branch);
        }

        $this->executeGitCommand($command, $module->local_path);
        return true;
    }

    public function createBranch(int $moduleId, string $branchName, bool $checkout = true): bool
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        // Create branch
        $command = 'git branch ' . escapeshellarg($branchName);
        $this->executeGitCommand($command, $module->local_path);

        if ($checkout) {
            return $this->checkout($moduleId, $branchName);
        }

        return true;
    }

    public function checkout(int $moduleId, string $branchName): bool
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        $command = 'git checkout ' . escapeshellarg($branchName);
        $this->executeGitCommand($command, $module->local_path);
        return true;
    }

    public function getStatus(int $moduleId): array
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        $output = $this->executeGitCommand('git status --porcelain -b', $module->local_path);
        $lines = explode("\n", trim($output));

        $status = [
            'branch' => '',
            'modified' => [],
            'untracked' => [],
            'deleted' => []
        ];

        foreach ($lines as $line) {
            if (strpos($line, '## ') === 0) {
                // Branch info
                preg_match('/## ([^\.]+)/', $line, $matches);
                $status['branch'] = $matches[1] ?? 'unknown';
            } else {
                // File status
                $statusCode = substr($line, 0, 2);
                $file = substr($line, 3);

                switch (trim($statusCode)) {
                    case 'M':
                        $status['modified'][] = $file;
                        break;
                    case '??':
                        $status['untracked'][] = $file;
                        break;
                    case 'D':
                        $status['deleted'][] = $file;
                        break;
                }
            }
        }

        return $status;
    }

    public function getHistory(int $moduleId, int $limit = 10): array
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        $command = sprintf(
            'git log -%d --pretty=format:"%%H|%%an|%%ae|%%at|%%s"',
            (int)$limit
        );

        $output = $this->executeGitCommand($command, $module->local_path);
        $commits = [];

        foreach (explode("\n", trim($output)) as $line) {
            list($hash, $author, $email, $timestamp, $message) = explode('|', $line);
            $commits[] = [
                'hash' => $hash,
                'author' => $author,
                'email' => $email,
                'date' => date('Y-m-d H:i:s', (int)$timestamp),
                'message' => $message
            ];
        }

        return $commits;
    }

    public function createTag(int $moduleId, string $tagName, string $message = ''): bool
    {
        $module = $this->moduleRepository->getById($moduleId);
        if (!$module) {
            throw new Exception('Module not found');
        }

        $command = 'git tag';
        if ($message) {
            $command .= ' -a ' . escapeshellarg($tagName) . ' -m ' . escapeshellarg($message);
        } else {
            $command .= ' ' . escapeshellarg($tagName);
        }

        $this->executeGitCommand($command, $module->local_path);
        return true;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeApiRequest('https://api.github.com/user');
            return isset($response['login']);
        } catch (Exception $e) {
            throw new Exception('GitHub connection test failed: ' . $e->getMessage());
        }
    }

    private function makeApiRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        if (!$this->token) {
            throw new Exception('GitHub token not configured');
        }

        $ch = curl_init($endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('GitHub API request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new Exception('GitHub API returned error: ' . $response);
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode GitHub response');
        }

        return $decodedResponse;
    }

    private function executeGitCommand(string $command, string $workingDir = null): string
    {
        $descriptorspec = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $pipes = [];
        $process = proc_open(
            $command,
            $descriptorspec,
            $pipes,
            $workingDir
        );

        if (!is_resource($process)) {
            throw new Exception('Failed to execute git command: ' . $command);
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnValue = proc_close($process);

        if ($returnValue !== 0) {
            throw new Exception('Git command failed: ' . $error);
        }

        return trim($output);
    }

    private function getRepoUrl(string $moduleName): string
    {
        $owner = $this->organization ?: $this->username;
        return "https://github.com/{$owner}/{$moduleName}";
    }
}