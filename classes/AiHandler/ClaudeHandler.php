<?php
/**
 * Anthropic Claude integration handler
 * @author Ljustema Sverige AB
 */

class ClaudeHandler implements AiHandlerInterface
{
    /** @var string */
    private $apiKey;

    /** @var string */
    private $model;

    /** @var array */
    private $defaultHeaders;

    public function __construct()
    {
        $this->apiKey = Configuration::get('ARTAIMODULEMAKER_CLAUDE_API_KEY');
        $this->model = Configuration::get('ARTAIMODULEMAKER_CLAUDE_MODEL', 'claude-3-opus-20240229');
        
        $this->defaultHeaders = [
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ];
    }

    public function generateCode(string $prompt, array $context = []): string
    {
        $systemPrompt = $this->buildSystemPrompt($context);
        
        $response = $this->makeApiRequest('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'system' => $systemPrompt
        ]);

        if (!isset($response['content'][0]['text'])) {
            throw new Exception('Invalid response from Claude');
        }

        return $response['content'][0]['text'];
    }

    public function chat(string $message, array $conversation = []): string
    {
        $messages = [];

        // Convert conversation history to Claude format
        foreach ($conversation as $entry) {
            $messages[] = [
                'role' => isset($entry['isUser']) && $entry['isUser'] ? 'user' : 'assistant',
                'content' => $entry['message']
            ];
        }

        // Add new message
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = $this->makeApiRequest('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 1000,
            'messages' => $messages,
            'system' => 'You are an expert PrestaShop developer assistant. Help the user with module development, following best practices and security guidelines.'
        ]);

        if (!isset($response['content'][0]['text'])) {
            throw new Exception('Invalid response from Claude');
        }

        return $response['content'][0]['text'];
    }

    public function analyzeCode(string $code, array $context = []): array
    {
        $prompt = "Analyze the following PrestaShop module code and provide feedback on:
1. Code quality and best practices
2. Security concerns
3. Performance optimization opportunities
4. Compatibility issues
5. Suggested improvements

Code to analyze:
```php
{$code}
```

Please provide your analysis in JSON format with the following structure:
{
    \"quality\": {\"issues\": [], \"suggestions\": []},
    \"security\": {\"vulnerabilities\": [], \"recommendations\": []},
    \"performance\": {\"issues\": [], \"optimizations\": []},
    \"compatibility\": {\"issues\": [], \"suggestions\": []},
    \"improvements\": []
}";

        $response = $this->generateCode($prompt, [
            'task' => 'code_analysis',
            'format' => 'json'
        ]);

        $analysis = json_decode($response, true);
        
        if (!$analysis) {
            throw new Exception('Failed to parse analysis response');
        }

        return $analysis;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeApiRequest('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 10,
                'messages' => [['role' => 'user', 'content' => 'test']]
            ]);
            
            return isset($response['content']) && is_array($response['content']);
        } catch (Exception $e) {
            throw new Exception('Claude connection test failed: ' . $e->getMessage());
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-3-opus-20240229' => 'Claude-3 Opus (Most Capable)',
            'claude-3-sonnet-20240229' => 'Claude-3 Sonnet (Balanced)',
        ];
    }

    public function getApiUsage(): array
    {
        // Claude API doesn't currently provide usage statistics
        // Return empty structure for compatibility
        return [
            'total_tokens' => 0,
            'total_requests' => 0,
            'remaining_credits' => 0,
        ];
    }

    private function buildSystemPrompt(array $context): string
    {
        $basePrompt = "You are an expert PrestaShop developer assistant. You specialize in creating secure, efficient, and maintainable code for PrestaShop modules.";

        if (isset($context['task'])) {
            $basePrompt .= "\nCurrent task: {$context['task']}";
        }

        if (isset($context['format'])) {
            $basePrompt .= "\nRequired output format: {$context['format']}";
        }

        if (isset($context['constraints'])) {
            $basePrompt .= "\nConstraints: {$context['constraints']}";
        }

        return $basePrompt;
    }

    private function makeApiRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        if (!$this->apiKey) {
            throw new Exception('Claude API key not configured');
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
            throw new Exception('Claude API request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Claude API returned error: ' . $response);
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode Claude response');
        }

        return $decodedResponse;
    }

    private function handleRateLimit($response)
    {
        // Implementera rate limiting hantering här om det behövs
        if (isset($response['error']) && strpos($response['error'], 'rate_limit') !== false) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }
    }

    private function logApiUsage($tokens)
    {
        // Implementera loggning av API-användning här om det behövs
        // Detta kan vara användbart för framtida fakturering eller övervakning
    }
}