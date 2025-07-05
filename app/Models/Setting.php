
<?php
namespace VidSocial\Models;

use VidSocial\Core\Database;

/**
 * Setting Model
 * Handles application settings storage and retrieval
 */
class Setting
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all settings as key-value pairs
     */
    public function getAll(): array
    {
        $settings = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        return $result;
    }
    
    /**
     * Get specific setting value
     */
    public function get(string $key, $default = null)
    {
        $setting = $this->db->fetch("SELECT `value` FROM settings WHERE `key` = :key", ['key' => $key]);
        return $setting ? $setting['value'] : $default;
    }
    
    /**
     * Set setting value
     */
    public function set(string $key, $value, string $description = null): void
    {
        $existing = $this->db->fetch("SELECT id FROM settings WHERE `key` = :key", ['key' => $key]);
        
        if ($existing) {
            $this->db->update('settings', ['value' => $value], ['key' => $key]);
        } else {
            $this->db->insert('settings', [
                'key' => $key,
                'value' => $value,
                'description' => $description
            ]);
        }
    }
    
    /**
     * Update multiple settings
     */
    public function updateMultiple(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }
}
