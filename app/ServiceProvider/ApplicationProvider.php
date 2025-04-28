<?php

namespace App\ServiceProvider;

use Kanboard\ServiceProvider\WhatsAppProvider;

class ApplicationProvider
{
    public function register(Container $container)
    {
        $container->register(new WhatsAppProvider());
    }
} 