<?php
class AuthorizeNet {
    private $apiLoginId;
    private $transactionKey;
    private $environment;
    private $apiUrl;
    
    public function __construct() {
        $this->apiLoginId = Env::get('AUTHORIZENET_API_LOGIN_ID');
        $this->transactionKey = Env::get('AUTHORIZENET_TRANSACTION_KEY');
        $this->environment = Env::get('AUTHORIZENET_ENVIRONMENT', 'production');
        
        $this->apiUrl = $this->environment === 'production' 
            ? 'https://api.authorize.net/xml/v1/request.api'
            : 'https://apitest.authorize.net/xml/v1/request.api';
    }
    
    public function getSubscriptionDetails($subscriptionId) {
        if (empty($subscriptionId)) {
            return null;
        }
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <ARBGetSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
            <merchantAuthentication>
                <name>' . htmlspecialchars($this->apiLoginId) . '</name>
                <transactionKey>' . htmlspecialchars($this->transactionKey) . '</transactionKey>
            </merchantAuthentication>
            <subscriptionId>' . htmlspecialchars($subscriptionId) . '</subscriptionId>
        </ARBGetSubscriptionRequest>';
        
        $response = $this->makeXmlRequest($xml);
        
        if ($response) {
            return $this->parseSubscriptionResponse($response);
        }
        
        return null;
    }
    
    public function getSubscriptionStatus($subscriptionId) {
        if (empty($subscriptionId)) {
            return null;
        }
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <ARBGetSubscriptionStatusRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
            <merchantAuthentication>
                <name>' . htmlspecialchars($this->apiLoginId) . '</name>
                <transactionKey>' . htmlspecialchars($this->transactionKey) . '</transactionKey>
            </merchantAuthentication>
            <subscriptionId>' . htmlspecialchars($subscriptionId) . '</subscriptionId>
        </ARBGetSubscriptionStatusRequest>';
        
        $response = $this->makeXmlRequest($xml);
        
        if ($response) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            libxml_clear_errors();
            
            if ($xml && isset($xml->status)) {
                return (string)$xml->status;
            }
        }
        
        return null;
    }
    
    private function makeXmlRequest($xmlData) {
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Authorize.net CURL Error: " . $curlError);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("Authorize.net HTTP Error: " . $httpCode);
            return null;
        }
        
        if (empty($response)) {
            error_log("Authorize.net Empty Response");
            return null;
        }
        
        return $response;
    }
    
    private function parseSubscriptionResponse($xmlResponse) {
        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlResponse);
            libxml_clear_errors();
            
            if (!$xml) {
                error_log("Failed to parse Authorize.net XML response");
                return null;
            }
            
            $namespaces = $xml->getNamespaces(true);
            if (isset($namespaces[''])) {
                $xml->registerXPathNamespace('ns', $namespaces['']);
            }
            
            if (!isset($xml->messages->resultCode) || (string)$xml->messages->resultCode !== 'Ok') {
                if (isset($xml->messages->message->text)) {
                    error_log("Authorize.net Error: " . (string)$xml->messages->message->text);
                }
                return null;
            }
            
            if (!isset($xml->subscription)) {
                error_log("No subscription data in response");
                return null;
            }
            
            $sub = $xml->subscription;
            
            $data = [
                'subscription_id' => isset($sub->subscriptionId) ? (string)$sub->subscriptionId : 'N/A',
                'name' => isset($sub->name) ? (string)$sub->name : 'N/A',
                'status' => isset($sub->status) ? (string)$sub->status : 'N/A',
                'amount' => isset($sub->amount) ? number_format((float)$sub->amount, 2) : '0.00',
                'interval_length' => 'N/A',
                'interval_unit' => 'N/A',
                'start_date' => 'N/A',
                'total_occurrences' => 'N/A',
                'trial_occurrences' => '0',
                'card_number' => 'N/A',
                'card_type' => 'N/A',
                'expiration_date' => 'N/A',
                'customer_profile_id' => 'N/A',
                'payment_profile_id' => 'N/A',
                'billing_first_name' => 'N/A',
                'billing_last_name' => 'N/A',
                'billing_address' => 'N/A',
                'billing_city' => 'N/A',
                'billing_state' => 'N/A',
                'billing_zip' => 'N/A',
            ];
            
            if (isset($sub->paymentSchedule)) {
                if (isset($sub->paymentSchedule->interval->length)) {
                    $data['interval_length'] = (string)$sub->paymentSchedule->interval->length;
                }
                if (isset($sub->paymentSchedule->interval->unit)) {
                    $data['interval_unit'] = (string)$sub->paymentSchedule->interval->unit;
                }
                if (isset($sub->paymentSchedule->startDate)) {
                    $data['start_date'] = (string)$sub->paymentSchedule->startDate;
                }
                if (isset($sub->paymentSchedule->totalOccurrences)) {
                    $data['total_occurrences'] = (string)$sub->paymentSchedule->totalOccurrences;
                }
                if (isset($sub->paymentSchedule->trialOccurrences)) {
                    $data['trial_occurrences'] = (string)$sub->paymentSchedule->trialOccurrences;
                }
            }
            
            if (isset($sub->payment->creditCard)) {
                if (isset($sub->payment->creditCard->cardNumber)) {
                    $data['card_number'] = (string)$sub->payment->creditCard->cardNumber;
                }
                if (isset($sub->payment->creditCard->cardType)) {
                    $data['card_type'] = (string)$sub->payment->creditCard->cardType;
                }
                if (isset($sub->payment->creditCard->expirationDate)) {
                    $data['expiration_date'] = (string)$sub->payment->creditCard->expirationDate;
                }
            }
            
            if (isset($sub->profile)) {
                if (isset($sub->profile->customerProfileId)) {
                    $data['customer_profile_id'] = (string)$sub->profile->customerProfileId;
                }
                if (isset($sub->profile->customerPaymentProfileId)) {
                    $data['payment_profile_id'] = (string)$sub->profile->customerPaymentProfileId;
                }
            }
            
            if (isset($sub->billTo)) {
                if (isset($sub->billTo->firstName)) {
                    $data['billing_first_name'] = (string)$sub->billTo->firstName;
                }
                if (isset($sub->billTo->lastName)) {
                    $data['billing_last_name'] = (string)$sub->billTo->lastName;
                }
                if (isset($sub->billTo->address)) {
                    $data['billing_address'] = (string)$sub->billTo->address;
                }
                if (isset($sub->billTo->city)) {
                    $data['billing_city'] = (string)$sub->billTo->city;
                }
                if (isset($sub->billTo->state)) {
                    $data['billing_state'] = (string)$sub->billTo->state;
                }
                if (isset($sub->billTo->zip)) {
                    $data['billing_zip'] = (string)$sub->billTo->zip;
                }
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Error parsing Authorize.net response: " . $e->getMessage());
            return null;
        }
    }
    
    public function isConfigured() {
        return !empty($this->apiLoginId) && !empty($this->transactionKey);
    }
}