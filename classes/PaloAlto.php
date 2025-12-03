<?php
class PaloAlto {
    private $firewalls = [];
    private $apiKey;
    
    public function __construct() {
        $this->apiKey = Env::get('PALO_ALTO_API_KEY');
        $this->firewalls = [
            'fw1' => Env::get('PALO_ALTO_FW1', '5.0.0.1'),
            'fw2' => Env::get('PALO_ALTO_FW2', '5.0.0.3')
        ];
    }
    
    public function getSystemInfo($fwKey = 'fw1') {
        $fw = $this->firewalls[$fwKey] ?? null;
        if (!$fw) {
            return ['success' => false, 'message' => 'Firewall not found'];
        }
        
        $cmd = '<show><system><info></info></system></show>';
        return $this->makeAPIRequest($fw, $cmd);
    }
    
    public function getIPUserMapping($fwKey = 'fw1') {
        $fw = $this->firewalls[$fwKey] ?? null;
        if (!$fw) {
            return ['success' => false, 'message' => 'Firewall not found'];
        }
        
        $cmd = '<show><user><ip-user-mapping><all></all></ip-user-mapping></user></show>';
        return $this->makeAPIRequest($fw, $cmd);
    }
    
    public function getSessionInfo($fwKey = 'fw1') {
        $fw = $this->firewalls[$fwKey] ?? null;
        if (!$fw) {
            return ['success' => false, 'message' => 'Firewall not found'];
        }
        
        $cmd = '<show><session><info></info></session></show>';
        return $this->makeAPIRequest($fw, $cmd);
    }
    
    public function getInterfaceStatus($fwKey = 'fw1') {
        $fw = $this->firewalls[$fwKey] ?? null;
        if (!$fw) {
            return ['success' => false, 'message' => 'Firewall not found'];
        }
        
        $cmd = '<show><interface>all</interface></show>';
        return $this->makeAPIRequest($fw, $cmd);
    }
    
    public function getHighAvailability($fwKey = 'fw1') {
        $fw = $this->firewalls[$fwKey] ?? null;
        if (!$fw) {
            return ['success' => false, 'message' => 'Firewall not found'];
        }
        
        $cmd = '<show><high-availability><state></state></high-availability></show>';
        return $this->makeAPIRequest($fw, $cmd);
    }
    
    private function makeAPIRequest($host, $command) {
        $url = sprintf(
            'https://%s/api/?type=op&cmd=%s&key=%s',
            $host,
            urlencode($command),
            $this->apiKey
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => 'CURL Error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'HTTP Error: ' . $httpCode];
        }
        
        try {
            $xml = simplexml_load_string($response);
            if ($xml === false) {
                return ['success' => false, 'message' => 'Failed to parse XML response'];
            }
            
            if (isset($xml['status']) && (string)$xml['status'] === 'success') {
                return ['success' => true, 'data' => $xml];
            } else {
                return ['success' => false, 'message' => 'API request failed', 'data' => $xml];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
    
    public function parseUserMapping($xmlData) {
        $users = [];
        
        if (isset($xmlData->result->entry)) {
            foreach ($xmlData->result->entry as $entry) {
                $users[] = [
                    'ip' => (string)$entry->ip,
                    'user' => (string)$entry->user,
                    'timeout' => (string)$entry->timeout ?? 'N/A'
                ];
            }
        }
        
        return $users;
    }
}