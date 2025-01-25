<?php
/**
 * Repository för hantering av API-nycklar
 * @author Ljustema Sverige AB
 */

class ApiKeyRepository
{
    private const ENCRYPTION_KEY = _COOKIE_KEY_; // Använder PrestaShops cookie-nyckel för kryptering
    private const TABLE_NAME = 'rl_aimodulemaker_api_keys';

    /**
     * Hämta en API-nyckel
     *
     * @param string $apiType Typ av API (openai, claude, github)
     * @return string|null
     */
    public function getApiKey(string $apiType): ?string
    {
        $query = new DbQuery();
        $query->select('api_key')
              ->from(self::TABLE_NAME)
              ->where('api_type = "' . pSQL($apiType) . '"')
              ->where('active = 1');

        $result = Db::getInstance()->getValue($query);
        
        return $result ? $this->decrypt($result) : null;
    }

    /**
     * Uppdatera eller skapa en API-nyckel
     *
     * @param string $apiType Typ av API
     * @param string $apiKey API-nyckel
     * @return bool
     */
    public function updateApiKey(string $apiType, string $apiKey): bool
    {
        $encryptedKey = $this->encrypt($apiKey);
        $data = [
            'api_type' => pSQL($apiType),
            'api_key' => pSQL($encryptedKey),
            'active' => 1,
            'date_upd' => date('Y-m-d H:i:s')
        ];

        // Kontrollera om nyckeln redan finns
        if ($this->exists($apiType)) {
            return Db::getInstance()->update(
                self::TABLE_NAME,
                $data,
                'api_type = "' . pSQL($apiType) . '"'
            );
        }

        $data['date_add'] = date('Y-m-d H:i:s');
        return Db::getInstance()->insert(self::TABLE_NAME, $data);
    }

    /**
     * Kontrollera om en API-nyckel är konfigurerad
     *
     * @param string $apiType Typ av API
     * @return bool
     */
    public function isConfigured(string $apiType): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
              ->from(self::TABLE_NAME)
              ->where('api_type = "' . pSQL($apiType) . '"')
              ->where('active = 1');

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Hämta alla aktiva API-nycklar
     *
     * @return array
     */
    public function getAllApiKeys(): array
    {
        $query = new DbQuery();
        $query->select('*')
              ->from(self::TABLE_NAME)
              ->where('active = 1');

        $results = Db::getInstance()->executeS($query);
        $keys = [];

        foreach ($results as $result) {
            $keys[$result['api_type']] = [
                'key' => $this->decrypt($result['api_key']),
                'date_add' => $result['date_add'],
                'date_upd' => $result['date_upd']
            ];
        }

        return $keys;
    }

    /**
     * Inaktivera en API-nyckel
     *
     * @param string $apiType Typ av API
     * @return bool
     */
    public function deactivateApiKey(string $apiType): bool
    {
        return Db::getInstance()->update(
            self::TABLE_NAME,
            ['active' => 0],
            'api_type = "' . pSQL($apiType) . '"'
        );
    }

    /**
     * Kontrollera om en API-nyckel är giltig
     *
     * @param string $apiKey API-nyckel att validera
     * @return bool
     */
    public function validateApiKey(string $apiKey): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
              ->from(self::TABLE_NAME)
              ->where('api_key = "' . pSQL($this->encrypt($apiKey)) . '"')
              ->where('active = 1');

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Kontrollera om en API-typ redan finns
     *
     * @param string $apiType Typ av API
     * @return bool
     */
    private function exists(string $apiType): bool
    {
        $query = new DbQuery();
        $query->select('COUNT(*)')
              ->from(self::TABLE_NAME)
              ->where('api_type = "' . pSQL($apiType) . '"');

        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Kryptera en API-nyckel
     *
     * @param string $value Värde att kryptera
     * @return string
     */
    private function encrypt(string $value): string
    {
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt(
            $value,
            'AES-256-CBC',
            self::ENCRYPTION_KEY,
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    /**
     * Dekryptera en API-nyckel
     *
     * @param string $value Värde att dekryptera
     * @return string
     */
    private function decrypt(string $value): string
    {
        $data = base64_decode($value);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            self::ENCRYPTION_KEY,
            0,
            $iv
        );
    }
}