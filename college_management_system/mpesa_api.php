<?php
/**
 * M-Pesa API Integration
 * Handles M-Pesa STK Push and payment processing
 */

// Define access constant
define('CMS_ACCESS', true);

// Include configuration
require_once __DIR__ . '/config.php';

/**
 * M-Pesa API Class
 */
class MpesaAPI {
    
    private $consumer_key;
    private $consumer_secret;
    private $shortcode;
    private $passkey;
    private $callback_url;
    private $environment;
    
    public function __construct() {
        $this->consumer_key = MPESA_CONSUMER_KEY;
        $this->consumer_secret = MPESA_CONSUMER_SECRET;
        $this->shortcode = MPESA_SHORTCODE;
        $this->passkey = MPESA_PASSKEY;
        $this->callback_url = MPESA_CALLBACK_URL;
        $this->environment = MPESA_ENVIRONMENT;
    }
    
    /**
     * Get M-Pesa access token
     */
    private function getAccessToken() {
        $url = $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            error_log("M-Pesa Access Token cURL Error: " . $error);
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }
        
        if ($http_code !== 200) {
            error_log("M-Pesa Access Token HTTP Error: " . $http_code . " - " . $response);
            return ['success' => false, 'message' => 'HTTP Error: ' . $http_code];
        }
        
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['access_token'])) {
            error_log("M-Pesa Access Token Invalid Response: " . $response);
            return ['success' => false, 'message' => 'Invalid response from M-Pesa API'];
        }
        
        return ['success' => true, 'access_token' => $result['access_token']];
    }
    
    /**
     * Generate password for STK Push
     */
    private function generatePassword() {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        return ['password' => $password, 'timestamp' => $timestamp];
    }
    
    /**
     * Initiate STK Push payment
     */
    public function stkPush($phone, $amount, $account_reference, $transaction_desc = 'Fee Payment') {
        try {
            // Get access token
            $token_result = $this->getAccessToken();
            if (!$token_result['success']) {
                return $token_result;
            }
            
            $access_token = $token_result['access_token'];
            
            // Format phone number
            $phone = $this->formatPhoneNumber($phone);
            if (!$phone) {
                return ['success' => false, 'message' => 'Invalid phone number format'];
            }
            
            // Generate password
            $password_data = $this->generatePassword();
            
            // STK Push URL
            $url = $this->environment === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            
            // Request payload
            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password_data['password'],
                'Timestamp' => $password_data['timestamp'],
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)$amount,
                'PartyA' => $phone,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $this->callback_url,
                'AccountReference' => $account_reference,
                'TransactionDesc' => $transaction_desc
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            if ($error) {
                error_log("M-Pesa STK Push cURL Error: " . $error);
                return ['success' => false, 'message' => 'Connection error: ' . $error];
            }
            
            $result = json_decode($response, true);
            
            // Log the request and response for debugging
            error_log("M-Pesa STK Push Request: " . json_encode($payload));
            error_log("M-Pesa STK Push Response: " . $response);
            
            if ($http_code === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
                return [
                    'success' => true,
                    'message' => 'STK Push sent successfully',
                    'checkout_request_id' => $result['CheckoutRequestID'],
                    'merchant_request_id' => $result['MerchantRequestID'],
                    'response_description' => $result['ResponseDescription']
                ];
            } else {
                $error_message = isset($result['ResponseDescription']) 
                    ? $result['ResponseDescription'] 
                    : 'STK Push failed';
                
                return [
                    'success' => false,
                    'message' => $error_message,
                    'response_code' => $result['ResponseCode'] ?? $http_code
                ];
            }
            
        } catch (Exception $e) {
            error_log("M-Pesa STK Push Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Query STK Push status
     */
    public function stkQuery($checkout_request_id) {
        try {
            // Get access token
            $token_result = $this->getAccessToken();
            if (!$token_result['success']) {
                return $token_result;
            }
            
            $access_token = $token_result['access_token'];
            
            // Generate password
            $password_data = $this->generatePassword();
            
            // STK Query URL
            $url = $this->environment === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
            
            // Request payload
            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password_data['password'],
                'Timestamp' => $password_data['timestamp'],
                'CheckoutRequestID' => $checkout_request_id
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            if ($error) {
                return ['success' => false, 'message' => 'Connection error: ' . $error];
            }
            
            $result = json_decode($response, true);
            
            if ($http_code === 200 && isset($result['ResponseCode'])) {
                return [
                    'success' => true,
                    'response_code' => $result['ResponseCode'],
                    'response_description' => $result['ResponseDescription'],
                    'result_code' => $result['ResultCode'] ?? null,
                    'result_desc' => $result['ResultDesc'] ?? null
                ];
            } else {
                return ['success' => false, 'message' => 'Query failed'];
            }
            
        } catch (Exception $e) {
            error_log("M-Pesa STK Query Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Format phone number to M-Pesa format
     */
    private function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^\d]/', '', $phone);
        
        // Convert to 254 format
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            // Already in correct format
        } else {
            return false; // Invalid format
        }
        
        // Validate Kenyan mobile number format
        if (!preg_match('/^254[17]\d{8}$/', $phone)) {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Process M-Pesa callback
     */
    public function processCallback($callback_data) {
        try {
            // Log callback for debugging
            error_log("M-Pesa Callback Received: " . json_encode($callback_data));
            
            if (!isset($callback_data['Body']['stkCallback'])) {
                return ['success' => false, 'message' => 'Invalid callback format'];
            }
            
            $callback = $callback_data['Body']['stkCallback'];
            
            $result = [
                'merchant_request_id' => $callback['MerchantRequestID'],
                'checkout_request_id' => $callback['CheckoutRequestID'],
                'result_code' => $callback['ResultCode'],
                'result_desc' => $callback['ResultDesc']
            ];
            
            // If payment was successful, extract additional details
            if ($callback['ResultCode'] === 0 && isset($callback['CallbackMetadata']['Item'])) {
                $metadata = [];
                foreach ($callback['CallbackMetadata']['Item'] as $item) {
                    $metadata[$item['Name']] = $item['Value'] ?? null;
                }
                
                $result['amount'] = $metadata['Amount'] ?? null;
                $result['mpesa_receipt'] = $metadata['MpesaReceiptNumber'] ?? null;
                $result['transaction_date'] = $metadata['TransactionDate'] ?? null;
                $result['phone_number'] = $metadata['PhoneNumber'] ?? null;
            }
            
            return ['success' => true, 'data' => $result];
            
        } catch (Exception $e) {
            error_log("M-Pesa Callback Processing Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Callback processing failed'];
        }
    }
}

/**
 * Helper function to initiate M-Pesa payment
 */
function initiateMpesaPayment($amount, $phone, $account_reference, $description = 'Fee Payment') {
    try {
        $mpesa = new MpesaAPI();
        return $mpesa->stkPush($phone, $amount, $account_reference, $description);
    } catch (Exception $e) {
        error_log("M-Pesa Payment Initiation Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Payment initiation failed'];
    }
}

/**
 * Helper function to query M-Pesa payment status
 */
function queryMpesaPayment($checkout_request_id) {
    try {
        $mpesa = new MpesaAPI();
        return $mpesa->stkQuery($checkout_request_id);
    } catch (Exception $e) {
        error_log("M-Pesa Payment Query Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Payment query failed'];
    }
}

/**
 * Helper function to process M-Pesa callback
 */
function processMpesaCallback($callback_data) {
    try {
        $mpesa = new MpesaAPI();
        return $mpesa->processCallback($callback_data);
    } catch (Exception $e) {
        error_log("M-Pesa Callback Processing Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Callback processing failed'];
    }
}
?>
