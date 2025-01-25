<?php
/**
 * Repository för hantering av moduler
 * @author Ljustema Sverige AB
 */

class ModuleRepository
{
    private const TABLE_NAME = 'rl_aimodulemaker_modules';

    /**
     * Skapa en ny modul
     *
     * @param array $moduleData Moduldata
     * @return array
     */
    public function create(array $moduleData): array
    {
        $data = [
            'name' => pSQL($moduleData['name']),
            'github_repo' => pSQL($moduleData['github_repo'] ?? ''),
            'local_path' => pSQL($moduleData['local_path']),
            'version' => pSQL($moduleData['version']),
            'status' => pSQL($moduleData['status'] ?? 'development'),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ];

        if (!Db::getInstance()->insert(self::TABLE_NAME, $data)) {
            throw new Exception('Failed to create module in database');
        }

        $moduleData['id'] = Db::getInstance()->Insert_ID();
        return $moduleData;
    }

    /**
     * Uppdatera en modul
     *
     * @param int $moduleId Modul-ID
     * @param array $moduleData Ny moduldata
     * @return bool
     */
    public function update(int $moduleId, array $moduleData): bool
    {
        $data = [
            'name' => pSQL($moduleData['name']),
            'github_repo' => pSQL($moduleData['github_repo'] ?? ''),
            'local_path' => pSQL($moduleData['local_path']),
            'version' => pSQL($moduleData['version']),
            'status' => pSQL($moduleData['status']),
            'date_upd' => date('Y-m-d H:i:s')
        ];

        return Db::getInstance()->update(
            self::TABLE_NAME,
            $data,
            'id = ' . (int)$moduleId
        );
    }

    /**
     * Hämta en modul via ID
     *
     * @param int $moduleId Modul-ID
     * @return array|null
     */
    public function getById(int $moduleId): ?array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('id = ' . (int)$moduleId);

        return Db::getInstance()->getRow($query);
    }

    /**
     * Hämta alla moduler
     *
     * @param array $filters Valfria filter
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME);

        if (isset($filters['status'])) {
            $query->where('status = "' . pSQL($filters['status']) . '"');
        }

        if (isset($filters['name'])) {
            $query->where('name LIKE "%' . pSQL($filters['name']) . '%"');
        }

        $query->orderBy('date_upd DESC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Ta bort en modul
     *
     * @param int $moduleId Modul-ID
     * @return bool
     */
    public function delete(int $moduleId): bool
    {
        return Db::getInstance()->delete(
            self::TABLE_NAME,
            'id = ' . (int)$moduleId
        );
    }

    /**
     * Uppdatera modulstatus
     *
     * @param int $moduleId Modul-ID
     * @param string $status Ny status
     * @return bool
     */
    public function updateStatus(int $moduleId, string $status): bool
    {
        return Db::getInstance()->update(
            self::TABLE_NAME,
            [
                'status' => pSQL($status),
                'date_upd' => date('Y-m-d H:i:s')
            ],
            'id = ' . (int)$moduleId
        );
    }

    /**
     * Uppdatera modulversion
     *
     * @param int $moduleId Modul-ID
     * @param string $version Ny version
     * @return bool
     */
    public function updateVersion(int $moduleId, string $version): bool
    {
        return Db::getInstance()->update(
            self::TABLE_NAME,
            [
                'version' => pSQL($version),
                'date_upd' => date('Y-m-d H:i:s')
            ],
            'id = ' . (int)$moduleId
        );
    }

    /**
     * Hämta modul via namn
     *
     * @param string $name Modulnamn
     * @return array|null
     */
    public function getByName(string $name): ?array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('name = "' . pSQL($name) . '"');

        return Db::getInstance()->getRow($query);
    }

    /**
     * Kontrollera om en modul existerar
     *
     * @param string $name Modulnamn
     * @return bool
     */
    public function exists(string $name): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
              ->from(self::TABLE_NAME)
              ->where('name = "' . pSQL($name) . '"');

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Hämta modulstatistik
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => 0,
            'development' => 0,
            'testing' => 0,
            'production' => 0,
            'archived' => 0
        ];

        $query = new DbQuery();
        $query->select('status, COUNT(*) as count')
              ->from(self::TABLE_NAME)
              ->groupBy('status');

        $results = Db::getInstance()->executeS($query);

        foreach ($results as $result) {
            $stats[$result['status']] = (int)$result['count'];
            $stats['total'] += (int)$result['count'];
        }

        return $stats;
    }
}