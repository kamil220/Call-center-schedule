<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env.test');
}

// Przygotowanie testowej bazy danych
if (isset($_SERVER['BOOTSTRAP_CLEAR_DATABASE_URL']) || isset($_ENV['BOOTSTRAP_CLEAR_DATABASE_URL'])) {
    // Czyszczenie/resetowanie bazy danych przed testami
    passthru(sprintf(
        'APP_ENV=test php "%s/../bin/console" doctrine:schema:drop --force --quiet',
        __DIR__
    ));
    
    passthru(sprintf(
        'APP_ENV=test php "%s/../bin/console" doctrine:schema:create --quiet',
        __DIR__
    ));
    
    passthru(sprintf(
        'APP_ENV=test php "%s/../bin/console" doctrine:fixtures:load --no-interaction --quiet',
        __DIR__
    ));
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
