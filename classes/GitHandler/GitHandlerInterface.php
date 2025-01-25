<?php
/**
 * Interface for Git handlers
 * @author Ljustema Sverige AB
 */

interface GitHandlerInterface
{
    /**
     * Initialize a new repository
     *
     * @param string $moduleName Name of the module
     * @param string $localPath Local path to the module
     * @return bool Success status
     * @throws Exception If initialization fails
     */
    public function initRepository(string $moduleName, string $localPath): bool;

    /**
     * Clone an existing repository
     *
     * @param string $repoUrl Repository URL
     * @param string $localPath Local path to clone to
     * @return bool Success status
     * @throws Exception If clone fails
     */
    public function cloneRepository(string $repoUrl, string $localPath): bool;

    /**
     * Commit changes
     *
     * @param int $moduleId Module ID
     * @param string $message Commit message
     * @return string Commit hash
     * @throws Exception If commit fails
     */
    public function commit(int $moduleId, string $message): string;

    /**
     * Push changes to remote
     *
     * @param int $moduleId Module ID
     * @param string $branch Branch name (optional)
     * @return bool Success status
     * @throws Exception If push fails
     */
    public function push(int $moduleId, string $branch = ''): bool;

    /**
     * Pull changes from remote
     *
     * @param int $moduleId Module ID
     * @param string $branch Branch name (optional)
     * @return bool Success status
     * @throws Exception If pull fails
     */
    public function pull(int $moduleId, string $branch = ''): bool;

    /**
     * Create a new branch
     *
     * @param int $moduleId Module ID
     * @param string $branchName Name of the new branch
     * @param bool $checkout Whether to checkout the new branch
     * @return bool Success status
     * @throws Exception If branch creation fails
     */
    public function createBranch(int $moduleId, string $branchName, bool $checkout = true): bool;

    /**
     * Switch to a branch
     *
     * @param int $moduleId Module ID
     * @param string $branchName Branch name
     * @return bool Success status
     * @throws Exception If checkout fails
     */
    public function checkout(int $moduleId, string $branchName): bool;

    /**
     * Get repository status
     *
     * @param int $moduleId Module ID
     * @return array Status information
     * @throws Exception If status check fails
     */
    public function getStatus(int $moduleId): array;

    /**
     * Get commit history
     *
     * @param int $moduleId Module ID
     * @param int $limit Number of commits to retrieve (optional)
     * @return array Commit history
     * @throws Exception If history retrieval fails
     */
    public function getHistory(int $moduleId, int $limit = 10): array;

    /**
     * Create a tag
     *
     * @param int $moduleId Module ID
     * @param string $tagName Tag name
     * @param string $message Tag message
     * @return bool Success status
     * @throws Exception If tag creation fails
     */
    public function createTag(int $moduleId, string $tagName, string $message = ''): bool;

    /**
     * Test the connection to the Git service
     *
     * @return bool True if connection is successful
     * @throws Exception If connection test fails
     */
    public function testConnection(): bool;
}