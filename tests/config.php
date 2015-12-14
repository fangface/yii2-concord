<?php

if (getenv('TRAVIS') && getenv('TRAVIS') == 'true') {
    // running tests on travis
    if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
        $cacheConfig = [
            'class' => 'yii\caching\MemCache',
            'useMemcached' => true,
            'keyPrefix' => 'concord_prefix',
        ];
    } else {
        $cacheConfig = [
            'class' => 'yii\caching\ApcCache',
            'keyPrefix' => 'concord_prefix',
        ];
    }
} else {
    // default local testing to dummy cache and let config-local.php override
    $cacheConfig = [
        'class' => 'yii\caching\DummyCache',
        'keyPrefix' => 'concord_prefix',
    ];
}

print 'Cache config: ';
print_r($cacheConfig);
print "\n";

return [

    'app' => [

        'id' => 'app-test',

        'basePath' => APPLICATION_PATH,
        'vendorPath' => APPLICATION_PATH . '/vendor',

        'components' => [

            'db' => [
                'class' => \yii\db\Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=dbTestMain',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
                'enableSchemaCache' => true,
                'schemaCache' => 'dbCache',
                'tablePrefix' => '',
            ],

            'db2' => [
                'class' => \yii\db\Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=dbTestClient1',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
                'enableSchemaCache' => true,
                'schemaCache' => 'dbCache',
                'tablePrefix' => '',
            ],

            'db3' => [
                'class' => \yii\db\Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=dbTestClient2',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
                'enableSchemaCache' => true,
                'schemaCache' => 'dbCache',
                'tablePrefix' => 'example_',
            ],

            'db4' => [
                'class' => \yii\db\Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=dbTestRemote1',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
                'enableSchemaCache' => true,
                'schemaCache' => 'dbCache',
                'tablePrefix' => '',
            ],

            'db5' => [
                'class' => \yii\db\Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=dbTestRemote2',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
                'enableSchemaCache' => true,
                'schemaCache' => 'dbCache',
                'tablePrefix' => 'example_',
            ],

            'dbFactory' => [
                'class' => \fangface\db\ConnectionManager::className(),
            ],

            'dbCache' => $cacheConfig,

        ],

        'params' => [
            'db.defaultTableNameType' => 'plural', // testing an alternative to yii built in convention
        ],

    ],
];

