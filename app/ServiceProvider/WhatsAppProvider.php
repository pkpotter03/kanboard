<?php

namespace Kanboard\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Kanboard\Core\WhatsApp\WhatsAppClient;

/**
 * WhatsApp Provider
 *
 * @package Kanboard\ServiceProvider
 * @author  Frederic Guillot
 */
class WhatsAppProvider implements ServiceProviderInterface
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
        $container['whatsappClient'] = function ($container) {
            return new WhatsAppClient($container);
        };

        return $container;
    }
} 