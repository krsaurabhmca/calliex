<?php
/**
 * WhatsApp Helper for Green-API
 * This class handles server-side messaging through Green-API.com
 */

class WhatsAppHelper {
    private $conn;
    private $org_id;
    private $enabled;
    private $idInstance;
    private $apiToken;
    private $apiHost;

    public function __construct($conn, $org_id) {
        $this->conn = $conn;
        $this->org_id = $org_id;
        
        // Fetch settings from system_settings
        $this->enabled = getOrgSetting($conn, $org_id, 'whatsapp_enabled', '0') === '1';
        $this->idInstance = getOrgSetting($conn, $org_id, 'whatsapp_id_instance', '');
        $this->apiToken = getOrgSetting($conn, $org_id, 'whatsapp_api_token', '');
        $this->apiHost = getOrgSetting($conn, $org_id, 'whatsapp_api_host', 'https://api.green-api.com');
    }

    /**
     * Send a text message to a phone number
     * @param string $mobile Phone number (with country code, but no + sign)
     * @param string $message Message content
     * @return array ['success' => bool, 'message' => string, 'response' => optional_array]
     */
    public function sendMessage($mobile, $message) {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp API is disabled in settings.'];
        }

        if (empty($this->idInstance) || empty($this->apiToken)) {
            return ['success' => false, 'message' => 'Green-API credentials (ID Instance / API Token) are missing.'];
        }

        // Clean mobile number
        $mobile = preg_replace('/\D/', '', $mobile);
        if (strlen($mobile) === 10) {
            $mobile = '91' . $mobile; // Default to India if 10 digits
        }

        $url = rtrim($this->apiHost, '/') . "/waInstance" . $this->idInstance . "/sendMessage/" . $this->apiToken;
        
        $data = [
            'chatId' => $mobile . "@c.us",
            'message' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'CURL Error: ' . $error];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['idMessage'])) {
            return [
                'success' => true, 
                'message' => 'Message sent successfully', 
                'msgId' => $result['idMessage']
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Green-API Error (Code ' . $httpCode . ')', 
                'details' => $result ?: $response
            ];
        }
    }

    /**
     * Check instance status
     */
    public function getStateInstance() {
        $url = rtrim($this->apiHost, '/') . "/waInstance" . $this->idInstance . "/getStateInstance/" . $this->apiToken;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
