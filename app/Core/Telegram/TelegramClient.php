<?php

namespace Kanboard\Core\Telegram;

use Kanboard\Core\Base;

/**
 * Telegram Client
 *
 * @package  Kanboard\Core\Telegram
 * @author   Frederic Guillot
 */
class TelegramClient extends Base
{
    /**
     * Send a message to a Telegram chat
     *
     * @access public
     * @param  string    $chat_id
     * @param  string    $message
     * @return boolean
     */
    public function sendMessage($chat_id, $message)
    {
        $this->logger->debug('TelegramClient::sendMessage - Starting message send to chat_id: ' . $chat_id);
        
        $bot_token = $this->configModel->get('telegram_bot_token');
        if (empty($bot_token) && defined('TELEGRAM_BOT_TOKEN')) {
            $bot_token = TELEGRAM_BOT_TOKEN;
        }
        if (empty($bot_token)) {
            $this->logger->error('TelegramClient::sendMessage - Bot token not configured');
            return false;
        }

        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $bot_token);
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        );

        $this->logger->debug('TelegramClient::sendMessage - Sending request to URL: ' . $url);
        $this->logger->debug('TelegramClient::sendMessage - Request data: ' . json_encode($data));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $this->logger->debug('TelegramClient::sendMessage - HTTP Response Code: ' . $http_code);
        $this->logger->debug('TelegramClient::sendMessage - API Response: ' . $response);
        
        if (!empty($curl_error)) {
            $this->logger->error('TelegramClient::sendMessage - Curl Error: ' . $curl_error);
            return false;
        }

        if ($http_code !== 200) {
            $this->logger->error('TelegramClient::sendMessage - API error: ' . $response);
            return false;
        }

        $this->logger->debug('TelegramClient::sendMessage - Message sent successfully');
        return true;
    }
} 