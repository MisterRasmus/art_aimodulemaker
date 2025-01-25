<?php
/**
 * Interface for AI handlers
 * @author Ljustema Sverige AB
 */

interface AiHandlerInterface
{
    /**
     * Generate code based on prompt and context
     *
     * @param string $prompt The user's prompt/request
     * @param array $context Additional context about the module
     * @return string Generated code
     * @throws Exception If code generation fails
     */
    public function generateCode(string $prompt, array $context = []): string;

    /**
     * Get a response from the AI chat
     *
     * @param string $message User's message
     * @param array $conversation Previous conversation history
     * @return string AI response
     * @throws Exception If chat fails
     */
    public function chat(string $message, array $conversation = []): string;

    /**
     * Analyze existing code and provide suggestions
     *
     * @param string $code Code to analyze
     * @param array $context Additional context about the code
     * @return array Analysis results and suggestions
     * @throws Exception If analysis fails
     */
    public function analyzeCode(string $code, array $context = []): array;

    /**
     * Test the API connection
     *
     * @return bool True if connection is successful
     * @throws Exception If connection test fails
     */
    public function testConnection(): bool;

    /**
     * Get available models for this AI service
     *
     * @return array List of available models
     */
    public function getAvailableModels(): array;

    /**
     * Get the current API usage/limits
     *
     * @return array Usage statistics and limits
     */
    public function getApiUsage(): array;
}