<?php

namespace Kanboard\Notification;

use Kanboard\Core\Base;
use Kanboard\Core\Notification\NotificationInterface;

/**
 * WhatsApp Notification
 *
 * @package  Kanboard\Notification
 * @author   Frederic Guillot
 */
class WhatsAppNotification extends Base implements NotificationInterface
{
    /**
     * Notification type
     *
     * @var string
     */
    const TYPE = 'whatsapp';

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
        $this->logger->debug('WhatsAppNotification::notifyUser - Starting notification for user: ' . $user['username']);
        
        // Load complete user data
        $user = $this->userModel->getById($user['id']);
        $this->logger->debug('WhatsAppNotification::notifyUser - Complete user data: ' . json_encode($user));
        
        if (!empty($user['whatsapp_number'])) {
            $this->logger->debug('WhatsAppNotification::notifyUser - Sending to WhatsApp number: ' . $user['whatsapp_number']);
            
            $message = $this->getMessageContent($event_name, $event_data);
            $this->logger->debug('WhatsAppNotification::notifyUser - Message content: ' . $message);
            
            $result = $this->whatsappClient->sendMessage(
                $user['whatsapp_number'],
                $message
            );
            
            $this->logger->debug('WhatsAppNotification::notifyUser - Send result: ' . ($result ? 'success' : 'failed'));
            
            if (empty($user['whatsapp_number'])) {
                $this->logger->error('WhatsAppNotification::notifyUser - No WhatsApp number found for user: ' . $user['username']);
            }
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
        $this->logger->debug('WhatsAppNotification::notifyProject - Project notification not implemented');
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
        $this->logger->debug('WhatsAppNotification::getMessageContent - Formatting message for event: ' . $event_name);
        
        $project_name = isset($event_data['project_name']) ? $event_data['project_name'] : $event_data['task']['project_name'];
        $title = $this->notificationModel->getTitleWithoutAuthor($event_name, $event_data);
        
        $message = sprintf(
            "*[%s]*\n%s",
            $project_name,
            $title
        );
        
        $this->logger->debug('WhatsAppNotification::getMessageContent - Formatted message: ' . $message);
        
        return $message;
    }
} 