<?php
// Migration for whatsapp_notifications_enabled is now obsolete and can be ignored.

namespace Kanboard\Schema;

use PDO;

const VERSION = 1;

function version_1(PDO $pdo)
{
    $pdo->exec("ALTER TABLE users ADD COLUMN whatsapp_notifications_enabled INTEGER DEFAULT 0");
} 