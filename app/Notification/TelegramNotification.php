<?php

namespace Kanboard\Notification;

use Kanboard\Core\Base;
use Kanboard\Core\Notification\NotificationInterface;

/**
 * Telegram Notification
 *
 * @package  Kanboard\Notification
 * @author   Frederic Guillot
 */
class TelegramNotification extends Base implements NotificationInterface
{
    /**
     * Notification type
     *
     * @var string
     */
    const TYPE = 'telegram';

    /**
     * Send notification to a user
     *
     * @access public
     * @param  array     $user
     * @param  string    $event_name
     * @param  array     $event_data
     */
    public function notifyUser(array $user, $event_name, array $event_data)
    {
        $this->logger->debug('TelegramNotification::notifyUser - Starting notification for user: ' . $user['username']);
        $this->logger->debug('TelegramNotification::notifyUser - User data: ' . json_encode($user));
        $this->logger->debug('TelegramNotification::notifyUser - Event name: ' . $event_name);
        $this->logger->debug('TelegramNotification::notifyUser - Event data: ' . json_encode($event_data));
        
        if (! empty($user['telegram_id'])) {
            $this->logger->debug('TelegramNotification::notifyUser - Sending to Telegram ID: ' . $user['telegram_id']);
            
            $message = $this->getMessageContent($event_name, $event_data);
            $this->logger->debug('TelegramNotification::notifyUser - Message content: ' . $message);
            
            $result = $this->telegramClient->sendMessage(
                $user['telegram_id'],
                $message
            );
            
            $this->logger->debug('TelegramNotification::notifyUser - Send result: ' . ($result ? 'success' : 'failed'));
        } else {
            $this->logger->error('TelegramNotification::notifyUser - No Telegram ID found for user: ' . $user['username']);
        }
    }

    /**
     * Send notification to a project
     *
     * @access public
     * @param  array     $project
     * @param  string    $event_name
     * @param  array     $event_data
     */
    public function notifyProject(array $project, $event_name, array $event_data)
    {
        $this->logger->debug('TelegramNotification::notifyProject - Project notification not implemented');
    }

    /**
     * Get the message content for a given template name
     *
     * @access public
     * @param  string    $event_name  Event name
     * @param  array     $event_data  Event data
     * @return string
     */
    public function getMessageContent($event_name, array $event_data)
    {
        $this->logger->debug('TelegramNotification::getMessageContent - Formatting message for event: ' . $event_name);
        
        $project_name = isset($event_data['project_name']) ? $event_data['project_name'] : $event_data['task']['project_name'];
        $title = $this->notificationModel->getTitleWithoutAuthor($event_name, $event_data);
        
        $message = sprintf(
            "*[%s]*\n%s",
            $project_name,
            $title
        );
        
        $this->logger->debug('TelegramNotification::getMessageContent - Formatted message: ' . $message);
        
        return $message;
    }
} 