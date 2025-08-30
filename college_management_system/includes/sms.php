<?php
/**
 * SMS Sending Functionality
 * Integrates with TextSMS API for sending OTPs
 */

define('TEXTSMS_API_URL', 'https://api.textsms.c.ke/send');
define('TEXTSMS_API_KEY', 'your_api_key_here'); // Replace with your actual API key

/**
 * Send SMS using TextSMS API
 *
 * @param string $phoneNumber The recipient's phone number
 * @param string $message The message to send
 * @return array The response from the API
 */
function sendSMS($phoneNumber, $message) {
    $data = [
        'to' => $phoneNumber,
        'message' => $message,
        'api_key' => TEXTSMS_API_KEY
    ];

    $ch = curl_init(TEXTSMS_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
