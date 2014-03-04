<?php

$now = date('Y-m-d H:i:s');

// passwords if required should be set within db2.dbResources-local.php (rename db2.dbResources-local.php.dist)

return array(

    1 => array(
        'resourceName'  => 'dbRemote',
        'dbDriver'      => 'Pdo',
        'dbDsn'         => 'mysql:host=localhost;dbname=dbTestRemote2',
        'dbUser'        => 'root',
        'dbPass'        => '',
        'dbPrefix'      => 'example_',
        'dbCharset'     => 'utf8',
        'dbAfterOpen'   => "SET time_zone = 'UTC'",
        'dbClass'       => '\yii\db\Connection',
        'createdAt'     => $now,
        'createdBy'     => 0,
        'modifiedAt'    => $now,
        'modifiedBy'    => 0,
    ),

);
