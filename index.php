<?php
require_once __DIR__ . '/src/AmoCrmV4Client.php';
require_once __DIR__ . '/src/functions.php';

define('SUB_DOMAIN', '');
define('CLIENT_ID', '');
define('CLIENT_SECRET', '');
define('CODE', '');
define('REDIRECT_URL', '');

echo "<pre>";

try {

    $amoV4Client = new AmoCrmV4Client(SUB_DOMAIN, CLIENT_ID, CLIENT_SECRET, CODE, REDIRECT_URL);

    // 1. Sorting and changing data
    task1($amoV4Client);

    // 2. Advanced work with data
    task2($amoV4Client);

    echo 'done';
}

catch (Exception $ex) {
    var_dump($ex);
    file_put_contents("ERROR_LOG.txt", 'Error: ' . $ex->getMessage() . PHP_EOL . 'Code:' . $ex->getCode());
}
