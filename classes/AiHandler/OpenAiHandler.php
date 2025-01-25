<?php
/**
 * OpenAI integration handler
 * @author Ljustema Sverige AB
 */

class OpenAiHandler implements AiHandlerInterface
{
    /** @var string */
    private $apiKey;

    /** @var string */
    private $model;

    /** @var array */
    private $defaultHeaders;

    public function __construct()
    {
        $this->apiKey = Configuration::get('ARTAIMODULEMAKER_OPENAI_API_KEY');
        $this->model = Configuration::get('ARTAIMODULEMAKER_OPENAI_MODEL', 'gpt-4');
        
        $this->defaultHeaders = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];
    }

    public function generateCode(string $prompt, array $context = []): string
    {
        $systemPrompt = $this->buildSystemPrompt($context);
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->makeApiRequest('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI');
        }

        return $response['choices'][0]['message']['content'];
    }

    public function chat(string $message, array $conversation = []): string
    {
        $messages = [];
        
        // Add system message if conversation is new
        if (empty($conversation)) {
            $messages[] = [
                'role' => 'system',
                'content' => 'You are an expert PrestaShop developer assistant. Help the user with module development, following best practices and security guidelines.'
            ];
        }

        // Convert conversation history to OpenAI format
        foreach ($conversation as $entry) {
            $messages[] = [
                'role' => isset($entry['isUser']) && $entry['isUser'] ? 'user' : 'assistant',
                'content' => $entry['message']
            ];
        }

        // Add new message
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = $this->makeApiRequest('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 1,
        ]);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI');
        }

        return $response['choices'][0]['message']['content'];
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

Additional context:
" . json_encode($context);

        $response = json_decode($this->generateCode($prompt, [
            'task' => 'code_analysis',
            'format' => 'json'
        ]), true);

        if (!$response) {
            throw new Exception('Failed to parse analysis response');
        }

        return $response;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeApiRequest('https://api.openai.com/v1/models', [], 'GET');
            return isset($response['data']) && is_array($response['data']);
        } catch (Exception $e) {
            throw new Exception('OpenAI connection test failed: ' . $e->getMessage());
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4' => 'GPT-4 (Recommended)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Faster)',
        ];
    }

    public function getApiUsage(): array
    {
        $response = $this->makeApiRequest('https://api.openai.com/v1/usage', [], 'GET');
        
        return [
            'total_tokens' => $response['total_tokens'] ?? 0,
            'total_requests' => $response['total_requests'] ?? 0,
            'remaining_credits' => $response['remaining_credits'] ?? 0,
        ];
    }

    private function buildSystemPrompt(array $context): string
    {
        $basePrompt = "You are an expert PrestaShop developer assistant. Your task is to generate high-quality, secure, and efficient code for PrestaShop modules.";

        if (isset($context['task'])) {
            $basePrompt .= "\nSpecific task: {$context['task']}";
        }

        if (isset($context['format'])) {
            $basePrompt .= "\nRequired format: {$context['format']}";
        }

        return $basePrompt;
    }

    private function makeApiRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
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
            throw new Exception('OpenAI API request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('OpenAI API returned error: ' . $response);
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode OpenAI response');
        }

        return $decodedResponse;
    }
}