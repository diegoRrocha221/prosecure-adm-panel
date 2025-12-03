<?php
/**
 * Environment Configuration Loader
 */
class Env {
    private static $variables = [];
    private static $loaded = false;
    
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            throw new Exception('.env file not found at: ' . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                self::$variables[$key] = $value;
                
                // Also set as environment variable
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    public static function getArray($prefix) {
        if (!self::$loaded) {
            self::load();
        }
        
        $result = [];
        foreach (self::$variables as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    public static function getRemoteServers($type = 'plans') {
        $servers = [];
        $serverCount = 1;
        
        while (true) {
            $host = self::get("REMOTE_SERVER_{$serverCount}_HOST");
            if (!$host) {
                break;
            }
            
            if ($type === 'blog') {
                $path = self::get("REMOTE_SERVER_{$serverCount}_BLOG_PATH");
            } else {
                $path = self::get("REMOTE_SERVER_{$serverCount}_PATH");
            }
            
            $servers[] = [
                'host' => $host,
                'user' => self::get("REMOTE_SERVER_{$serverCount}_USER"),
                'pass' => self::get("REMOTE_SERVER_{$serverCount}_PASS"),
                'path' => $path
            ];
            
            $serverCount++;
        }
        
        return $servers;
    }
    
    public static function hasLocalStorage() {
        return !empty(self::get('LOCAL_STORAGE_ENABLED')) && 
               self::get('LOCAL_STORAGE_ENABLED') === 'true';
    }
    
    public static function getLocalStoragePath() {
        return self::get('LOCAL_STORAGE_PATH', '/var/www/storage/');
    }
}