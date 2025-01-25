<?php
/**
 * Repository för hantering av modulversioner
 * @author Ljustema Sverige AB
 */

namespace PrestaShop\Module\ArtAimodulemaker\Database;

class VersionRepository
{
    private const TABLE_NAME = 'art_aimodulemaker_version_history';

    /**
     * Lägg till en ny version
     *
     * @param array $versionData Versionsdata
     * @return bool
     */
    public function addVersion(array $versionData): bool
    {
        $data = [
            'module_id' => (int)$versionData['module_id'],
            'version' => pSQL($versionData['version']),
            'commit_hash' => pSQL($versionData['commit_hash'] ?? ''),
            'changes' => pSQL($versionData['changes']),
            'date_add' => date('Y-m-d H:i:s')
        ];

        return Db::getInstance()->insert(self::TABLE_NAME, $data);
    }

    /**
     * Hämta versionshistorik för en modul
     *
     * @param int $moduleId Modul-ID
     * @param int $limit Antal versioner att hämta
     * @return array
     */
    public function getVersionHistory(int $moduleId, int $limit = 10): array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('module_id = ' . (int)$moduleId)
              ->orderBy('date_add DESC')
              ->limit($limit);

        return Db::getInstance()->executeS($query);
    }

    /**
     * Hämta senaste versionen för en modul
     *
     * @param int $moduleId Modul-ID
     * @return array|null
     */
    public function getLatestVersion(int $moduleId): ?array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('module_id = ' . (int)$moduleId)
              ->orderBy('date_add DESC')
              ->limit(1);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Kontrollera om en version existerar
     *
     * @param int $moduleId Modul-ID
     * @param string $version Versionsnummer
     * @return bool
     */
    public function versionExists(int $moduleId, string $version): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
              ->from(self::TABLE_NAME)
              ->where('module_id = ' . (int)$moduleId)
              ->where('version = "' . pSQL($version) . '"');

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Ta bort en specifik version
     *
     * @param int $versionId Versions-ID
     * @return bool
     */
    public function deleteVersion(int $versionId): bool
    {
        return Db::getInstance()->delete(
            self::TABLE_NAME,
            'id = ' . (int)$versionId
        );
    }

    /**
     * Ta bort alla versioner för en modul
     *
     * @param int $moduleId Modul-ID
     * @return bool
     */
    public function deleteAllVersions(int $moduleId): bool
    {
        return Db::getInstance()->delete(
            self::TABLE_NAME,
            'module_id = ' . (int)$moduleId
        );
    }

    /**
     * Hämta version via commit hash
     *
     * @param string $commitHash Git commit hash
     * @return array|null
     */
    public function getByCommitHash(string $commitHash): ?array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('commit_hash = "' . pSQL($commitHash) . '"');

        return Db::getInstance()->getRow($query);
    }

    /**
     * Hämta antal versioner för en modul
     *
     * @param int $moduleId Modul-ID
     * @return int
     */
    public function getVersionCount(int $moduleId): int
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
              ->from(self::TABLE_NAME)
              ->where('module_id = ' . (int)$moduleId);

        return (int)Db::getInstance()->getValue($query);
    }

    /**
     * Jämför två versioner
     *
     * @param int $moduleId Modul-ID
     * @param string $version1 Första versionen
     * @param string $version2 Andra versionen
     * @return array
     */
    public function compareVersions(int $moduleId, string $version1, string $version2): array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('module_id = ' . (int)$moduleId)
              ->where('version IN ("' . pSQL($version1) . '", "' . pSQL($version2) . '")')
              ->orderBy('date_add ASC');

        $versions = Db::getInstance()->executeS($query);
        
        if (count($versions) !== 2) {
            throw new Exception('One or both versions not found');
        }

        return [
            'older' => $versions[0],
            'newer' => $versions[1],
            'time_difference' => strtotime($versions[1]['date_add']) - strtotime($versions[0]['date_add']),
            'changes' => $versions[1]['changes']
        ];
    }

    /**
     * Säkerhetskopiera versionshistorik
     *
     * @param int $moduleId Modul-ID
     * @return string JSON-sträng med backup
     */
    public function backupVersionHistory(int $moduleId): string
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('module_id = ' . (int)$moduleId)
              ->orderBy('date_add ASC');

        $versions = Db::getInstance()->executeS($query);
        
        return json_encode([
            'module_id' => $moduleId,
            'backup_date' => date('Y-m-d H:i:s'),
            'versions' => $versions
        ]);
    }

    /**
     * Återställ versionshistorik från backup
     *
     * @param string $backupData JSON-sträng med backup-data
     * @return bool
     */
    public function restoreVersionHistory(string $backupData): bool
    {
        $backup = json_decode($backupData, true);
        if (!$backup || !isset($backup['versions'])) {
            throw new Exception('Invalid backup data');
        }

        // Ta bort existerande versioner
        $this->deleteAllVersions($backup['module_id']);

        // Återställ versioner
        foreach ($backup['versions'] as $version) {
            unset($version['id']); // Låt databasen hantera ID
            if (!Db::getInstance()->insert(self::TABLE_NAME, $version)) {
                return false;
            }
        }

        return true;
    }
}