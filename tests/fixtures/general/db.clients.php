<?php

$now = date('Y-m-d H:i:s');

// passwords if required should be set within db.clients-local.php (rename db.clients-local.php.dist)

return array(

    1 => array(
        'clientCode'        => "CLIENT1",
        'clientName'        => "Test Company 1",
        'dbDriver'          => 'Pdo',
        'dbDsn'             => 'mysql:host=localhost;dbname=dbTestClient1',
        'dbUser'            => 'root',
        'dbPass'            => '',
        'dbPrefix'          => '',
        'dbCharset'         => 'utf8',
        'dbAfterOpen'       => "SET NAMES utf8; SET time_zone = 'UTC';",
        'dbClass'           => '\yii\db\Connection',
        'createdAt'         => $now,
        'createdBy'         => 0,
        'modifiedAt'        => $now,
        'modifiedBy'        => 0,
    ),

    2 => array(
        'clientCode'        => "CLIENT2",
        'clientName'        => "Test Company 2",
        'dbDriver'          => 'Pdo',
        'dbDsn'             => 'mysql:host=localhost;dbname=dbTestClient2',
        'dbUser'            => 'root',
        'dbPass'            => '',
        'dbPrefix'          => 'example_',
        'dbCharset'         => 'utf8',
        'dbAfterOpen'       => "SET time_zone = 'UTC'",
        'dbClass'           => '\yii\db\Connection',
        'createdAt'         => $now,
        'createdBy'         => 0,
        'modifiedAt'        => $now,
        'modifiedBy'        => 0,
    ),

);
