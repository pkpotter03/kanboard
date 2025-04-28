<?php

namespace Kanboard\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Kanboard\Core\Telegram\TelegramClient;

/**
 * Telegram Provider
 *
 * @package Kanboard\ServiceProvider
 * @author  Frederic Guillot
 */
class TelegramProvider implements ServiceProviderInterface
{
    /**
     * Register providers
     *
     * @access public
     * @param  \Pimple\Container $container
     * @return \Pimple\Container
     */
    public function register(Container $container)
    {
        $container['telegramClient'] = function ($container) {
            return new TelegramClient($container);
        };

        return $container;
    }
} 