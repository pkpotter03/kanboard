<?php

namespace Kanboard\Core\WhatsApp;

use Kanboard\Core\Base;

/**
 * WhatsApp Client
 *
 * @package  Kanboard\Core\WhatsApp
 * @author   Frederic Guillot
 */
class WhatsAppClient extends Base
{
    /**
     * Send a message to a WhatsApp number
     *
     * @access public
     * @param  string    $number
     * @param  string    $message
     * @return boolean
     */
    public function sendMessage($number, $message)
    {
        $this->logger->debug('WhatsAppClient::sendMessage - Sending message to: ' . $number);
        
        try {
            $account_sid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
            $auth_token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
            $whatsapp_number = defined('TWILIO_WHATSAPP_NUMBER') ? TWILIO_WHATSAPP_NUMBER : '';
            
            if (empty($account_sid) || empty($auth_token) || empty($whatsapp_number)) {
                $this->logger->error('WhatsAppClient::sendMessage - Twilio configuration missing');
                return false;
            }
            
            $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $account_sid . '/Messages.json';
            
            $data = array(
                'From' => 'whatsapp:' . $whatsapp_number,
                'To' => 'whatsapp:' . $number,
                'Body' => $message
            );
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            $this->logger->debug('WhatsAppClient::sendMessage - Response: ' . $response);
            $this->logger->debug('WhatsAppClient::sendMessage - HTTP Code: ' . $http_code);
            
            if ($curl_error) {
                $this->logger->error('WhatsAppClient::sendMessage - Curl Error: ' . $curl_error);
                return false;
            }
            
            return $http_code >= 200 && $http_code < 300;
        } catch (\Exception $e) {
            $this->logger->error('WhatsAppClient::sendMessage - Error: ' . $e->getMessage());
            return false;
        }
    }
} 