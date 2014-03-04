<?php
/**
 * This file is part of the fangface/yii2-concord package
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 *
 * @package fangface/yii2-concord
 * @author Fangface <dev@fangface.net>
 * @copyright Copyright (c) 2014 Fangface <dev@fangface.net>
 * @license https://github.com/fangface/yii2-concord/blob/master/LICENSE.md MIT License
 *
 */

namespace Concord\Db;

use Yii;
use yii\base\Component;
use \yii\helpers\Security;

/**
 * ConnectionManager Class
 *
 * Class that provides methods to allow for db connection management, initially
 * designed for use with yii2-concord but can serve as a standalone connection
 * manager as well if useful. Connections can be automatically obtained from
 * component config for the base application and/or can supplment those connections.
 * New connections are by default added to yii components so they can be accessed
 * as typical yii db connections would be accessed e.g.
 *
 *     \Yii::$app->getComponent('db2');
 *     \Yii::$app->getComponent('dbFactory')->getConnection('db2');
 *
 * Ideally suited when multiple database connections are required within an
 * application and especially if those connections dynamically change but models
 * are re-used to point to multiple database connections sharing the same alias
 * at run-time.
 *
 * @author Fangface <dev@fangface.net>
 */
class ConnectionManager extends Component
{
    use \Concord\Base\Traits\ServiceGetter;

    /**
     * Key to use to recover dbResource passwords
     *
     * @var string
     */
    public $secretKey = null;

    /**
     * Array of resources (connections) currently setup
     *
     * @var array
     */
    protected $resources = array();

    /**
     * Default resource name
     *
     * @var string
     */
    protected $defaultResourceName = 'db';


    /**
     * Called by the Object::__construct()
     */
    public function init()
    {
        $this->resources = array(
            'Resources' => array(),
            'Aliases' => array()
        );
    }


    /**
     * Set the default resource name e.g. 'db'
     *
     * @param string $resourceName
     */
    public function setDefaultResourceName($resourceName = 'db')
    {
        $this->defaultResourceName = $resourceName;
    }


    /**
     * Return name of default resource e.g. 'db'
     *
     * @return string
     */
    public function getDefaultResourceName()
    {
        return $this->defaultResourceName;
    }


    /**
     * Return the actual resource name based on an existing resource name or alias
     *
     * @param string $resourceName
     * @return string|false
     */
    public function getResourceNameByAlias($resourceName)
    {
        if (isset($this->resources['Resources'][$resourceName])) {
            return $resourceName;
        } elseif (isset($this->resources['Aliases'][$resourceName]) && isset($this->resources['Resources'][$this->resources['Aliases'][$resourceName]])) {
            return $this->resources['Aliases'][$resourceName];
        }

        return false;
    }


    /**
     * Obtain the connection for the specified resource name
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @param boolean $addResource [OPTIONAL] add resource if it is not already loaded (default false)
     * @param boolean $checkServices [OPTIONAL] check services for db connections that have not been registered via the connection manager (default true)
     * @param boolean $clientResource if $addResource is true default to loading the resource from the clients resources table rather than the master resources table
     * @return \yii\db\Connection
     */
    public function getConnection($resourceName='', $addResource = false, $checkServices = true, $clientResource = false)
    {

        if ($resourceName == 'Default' || $resourceName == '') {
            $resourceName = $this->defaultResourceName;
        }

        $resourceNameIn = $resourceName;

        $resourceName = $this->getResourceNameByAlias($resourceName);

        if ($resourceName && isset($this->resources['Resources'][$resourceName])) {

            $connection = $this->resources['Resources'][$resourceName]['Connection'];

        } else {

            // check to see if an existing component exists for this connection that we
            // can adopt

            if ($checkServices && $this->hasService($resourceNameIn)) {
                $connection = $this->getService($resourceNameIn);
                if ($connection) {
                    $class = get_class($connection);
                    $this->extendResourceArray($resourceNameIn, $connection, $class);
                    return $connection;
                }
            }

            if (!$addResource) {

                return false;

            } else {

                // attempt to add the resourceName and setup the connection
                $success = $this->addResource($resourceNameIn, $clientResource, false);
                if ($success) {
                    $connection = $this->getConnection($resourceNameIn);
                } else {
                    return false;
                }

            }

        }

        return $connection;
    }


    /**
     * Obtain the current client main db connection
     *
     * @param boolean $addResource [OPTIONAL] add resource if it is not already loaded (default false)
     * @return \yii\db\Connection
     */
    public function getClientConnection($addResource = false)
    {
        return $this->getConnection('dbClient', $addResource);
    }

    /**
     * Obtain the client resource connection for the specified client resource name
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @param boolean $addResource [OPTIONAL] add resource if it is not already loaded (default false)
     * @return \yii\db\Connection
     */
    public function getClientResourceConnection($resourceName='', $addResource = false)
    {
        // client resources in a multi client environment will never be defined as a component
        // so third parameter is false
        return $this->getConnection($resourceName, $addResource, false, true);
    }

    /**
     * Add a new resource and setup the DB connection
     *
     * @param string $resourceName
     * @param boolean $clientResource default to loading the resource from the clients resources table rather than the master resources table
     * @param boolean $checkServices check services for db connections that have not been registered via the connection manager (default true)
     * @param array|false $dbParams array of parameters for the connection, similar to what would be found in config (default false)
     * @return boolean success
     */
    public function addResource($resourceName, $clientResource = false, $checkServices = true, $dbParams = false)
    {

        if ($checkServices) {
            if ($this->hasService($resourceNameIn)) {
                $connection = $this->getService($resourceNameIn);
                if ($connection) {
                    $class = get_class($connection);
                    $this->extendResourceArray($resourceNameIn, $connection, $class);
                }
            }
        }

        $resourceNameCheck = $this->getResourceNameByAlias($resourceName);
        if ($resourceName && isset($this->resources['Resources'][$resourceNameCheck])) {

            // resource already exists
            return true;

        } elseif ($resourceName) {

            if ($dbParams && is_array($dbParams)) {

                // use the dbParams provided

            } elseif (isset(Yii::$app->$resourceName) && is_array(Yii::$app->$resourceName)) {

                // connection settings can be taken from the application config
                $dbParams = Yii::$app->$resourceName;

            } elseif ($resourceName == 'dbClient') {

                /*
                 * We need to load up the client DB connection and it is not defined as config
                 * so we will get the details from the db.clients table
                 */

                $client = $this->getService('client');
                if ($client && $client instanceof \Concord\Models\Db\Client) {
                    // take some default values form the defaul connection (which would have come from config
                    $connection = $this->getConnection();

                    $dbParams = array(
                        'class'                => $client->dbClass,
                        'dsn'                  => $client->dbDsn,
                        'username'             => $client->dbUser,
                        'password'             => $client->dbPass,
                        'charset'              => $client->dbCharset,
                        'tablePrefix'          => $client->dbPrefix,
                        'connect'              => false,
                        'enableSchemaCache'    => $connection->enableSchemaCache,
                        'schemaCacheDuration'  => $connection->schemaCacheDuration,
                        'schemaCacheExclude'   => array(), // $connection->schemaCacheExclude,
                        'schemaCache'          => $connection->schemaCache,
                        'enableQueryCache'     => $connection->enableQueryCache,
                        'queryCacheDuration'   => $connection->queryCacheDuration,
                        'queryCacheDependency' => $connection->queryCacheDependency,
                        'queryCache'           => $connection->queryCache,
                        'emulatePrepare'       => NULL, // $connection->emulatePrepare,
                    );
                }

            } elseif ($clientResource) {

                $client = $this->getService('client');
                if ($client && $client instanceof \Concord\Models\Db\Client) {
                    $connection = $this->getClientConnection(true);
                    if ($connection && $connection instanceof \Yii\Db\Connection) {

                        $dbResource = \Concord\Models\Db\Client\DbResource::find()
                            ->where(['resourceName' => $resourceName])
                            ->one();

                        if ($dbResource) {
                            $dbParams = array(
                                'class'                => $dbResource->dbClass,
                                'dsn'                  => $dbResource->dbDsn,
                                'username'             => $dbResource->dbUser,
                                'password'             => $dbResource->dbPass,
                                'charset'              => $dbResource->dbCharset,
                                'tablePrefix'          => $dbResource->dbPrefix,
                                'connect'              => false,
                                'enableSchemaCache'    => $connection->enableSchemaCache,
                                'schemaCacheDuration'  => $connection->schemaCacheDuration,
                                'schemaCacheExclude'   => array(), // $connection->schemaCacheExclude,
                                'schemaCache'          => $connection->schemaCache,
                                'enableQueryCache'     => $connection->enableQueryCache,
                                'queryCacheDuration'   => $connection->queryCacheDuration,
                                'queryCacheDependency' => $connection->queryCacheDependency,
                                'queryCache'           => $connection->queryCache,
                                'emulatePrepare'       => NULL, // $connection->emulatePrepare,
                            );
                        }
                    }
                }

            } else {

                $connection = $this->getConnection();

                if ($connection && $connection instanceof \Yii\Db\Connection) {

                    $dbResource = \Concord\Models\Db\DbResource::find()
                        ->where(['resourceName' => $resourceName])
                        ->one();

                    if ($dbResource) {
                        $dbParams = array(
                            'class'                => $dbResource->dbClass,
                            'dsn'                  => $dbResource->dbDsn,
                            'username'             => $dbResource->dbUser,
                            'password'             => $dbResource->dbPass,
                            'charset'              => $dbResource->dbCharset,
                            'tablePrefix'          => $dbResource->dbPrefix,
                            'connect'              => false,
                            'enableSchemaCache'    => $connection->enableSchemaCache,
                            'schemaCacheDuration'  => $connection->schemaCacheDuration,
                            'schemaCacheExclude'   => array(), // $connection->schemaCacheExclude,
                            'schemaCache'          => $connection->schemaCache,
                            'enableQueryCache'     => $connection->enableQueryCache,
                            'queryCacheDuration'   => $connection->queryCacheDuration,
                            'queryCacheDependency' => $connection->queryCacheDependency,
                            'queryCache'           => $connection->queryCache,
                            'emulatePrepare'       => NULL, // $connection->emulatePrepare,
                        );
                    }
                }
            }

            if ($dbParams) {

                if (isset($dbParams['password']) && substr($dbParams['password'], 0, 1) == '#') {
                    $dbParams['password'] = $this->decrypt($dbParams['password']);
                }

                $connection = $this->setupConnection($resourceName, $dbParams);

                if ($connection) {

                    $class = get_class($connection);
                    $this->extendResourceArray($resourceName, $connection, $class, (isset($dbParams['connect']) ? $dbParams['connect'] : false));

                    // add resourceName to the service manager as well (setup as not shared)
                    if ($this->hasService($resourceName)) {
                        $this->setService($resourceName, null);
                    }
                    $this->setService($resourceName, $connection);

                    return true;
                }

            } else {

                throw new \Concord\Db\Exception('Missing dbParams on addResource');

            }
        }

        return false;

    }


    /**
     * Extend internal resources array
     * @param string $resourceName
     * @param \yii\db\Connection $connection
     * @param string $className
     * @param boolean $connect
     */
    public function extendResourceArray($resourceName, $connection, $className, $connect = false) {
        $this->resources['Resources'][$resourceName]['Connection'] = $connection;
        $this->resources['Resources'][$resourceName]['Connected'] = $connection->getIsActive();
        $this->resources['Resources'][$resourceName]['Aliases'] = array();
        $this->resources['Resources'][$resourceName]['Settings'] = array(
            'class'                => $className,
            'dsn'                  => $connection->dsn,
            'username'             => $connection->username,
            'password'             => $connection->password,
            'charset'              => $connection->charset,
            'enableSchemaCache'    => $connection->enableSchemaCache,
            'schemaCacheDuration'  => $connection->schemaCacheDuration,
            'schemaCacheExclude'   => $connection->schemaCacheExclude,
            'schemaCache'          => $connection->schemaCache,
            'enableQueryCache'     => $connection->enableQueryCache,
            'queryCacheDuration'   => $connection->queryCacheDuration,
            'queryCacheDependency' => $connection->queryCacheDependency,
            'queryCache'           => $connection->queryCache,
            'emulatePrepare'       => $connection->emulatePrepare,
            'tablePrefix'          => $connection->tablePrefix,
            'connect'              => $connect,
        );
    }

    /**
     * Setup and return the connection for the specified resource name
     *
     * @param string $resource
     * @param array $dbParams
     * @return \yii\db\Connection|false
     */
    public function setupConnection($resourceName, $dbParams)
    {
        $connected = false;

        $class = (isset($dbParams['class']) && is_string($dbParams['class']) && $dbParams['class'] != '' ? $dbParams['class'] : 'yii\db\Connection');
        $connect = (isset($dbParams['connect']) && is_string($dbParams['connect']) ? $dbParams['connect'] : false);

        unset($dbParams['class']);
        unset($dbParams['connect']);

        $connection = new $class($dbParams);

        if ($connection instanceof $class) {
            if ($connect) {
                $connection->open();
                $connected = $connection->getIsActive();
            } elseif ($connection) {
                $connected = true;
            }
        }

        return ($connected ? $connection : $connected);
    }


    /**
     * Re-Connect a resources connection to the db if it has been previously closed or just to make sure the
     * connection is really still open
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return boolean success
     */
    public function connectResource($resourceName = '')
    {
        $connected = false;

        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $resourceName = $this->getResourceNameByAlias($resourceName);

        if ($resourceName && isset($this->resources['Resources'][$resourceName])) {

            if ($this->isResourceConnected($resourceName)) {

                $connected = true;

            } else {

                $connection = $this->getConnection($resourceName);

                try {

                    if (!$connection->getIsActive()) {
                        $connection->open();
                    }

                    $connected = $connection->getIsActive();

                } catch (\Exception $ex) {

                    // connection failed but we have caught the error so we can handle it gracefully
                    trigger_error($ex->getMessage() . ' in ' . $ex->getFile() . ' on line ' . $ex->getLine(), E_USER_WARNING);

                }
            }

        }

        return $connected;
    }


    /**
     * Disconnect a resources from the db
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return boolean success
     */
    public function disconnectResource($resourceName = '')
    {
        $closed = false;

        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $resourceName = $this->getResourceNameByAlias($resourceName);

        if ($resourceName && isset($this->resources['Resources'][$resourceName])) {

            if (!$this->isResourceConnected($resourceName)) {

                $closed = true;

            } else {

                $connection = $this->getConnection($resourceName);

                try {

                    if ($connection->getIsActive()) {
                        $connection->close();
                    }

                    $closed = ($connection->getIsActive() ? false : true);

                } catch (\Exception $ex) {

                    // connection failed but we have caught the error so that we can handle it gracefully
                    trigger_error($ex->getMessage() . ' in ' . $ex->getFile() . ' on line ' . $ex->getLine(), E_USER_WARNING);

                }
            }

        }

        return $closed;
    }


    /**
     * Add resource alias and return true/false upon success
     *
     * @param string $resourceName
     * @param string $alias
     * @return boolean
     */
    public function addResourceAlias($resourceName, $alias)
    {
        $aliasName = $this->getResourceNameByAlias($alias);

        if ($aliasName) {

            // new alias already exists as a resource or an alias

        } elseif ($resourceName == $alias) {

            // whoops

        } else {

            // do we have a resource to alias to?
            $resourceNameCheck = $this->getResourceNameByAlias($resourceName);

            if (!isset($this->resources['Resources'][$resourceNameCheck])) {
                // maybe we are setting up an alias to the main database connection before that main
                // database connection has been used - we will attempt to set that up now if there is @author Waine
                // service entry for it
                if ($this->hasService($resourceName)) {
                    $tempConnection = $this->getConnection($resourceName, true);
                    $resourceNameCheck = $this->getResourceNameByAlias($resourceName);
                }
            }

            if (isset($this->resources['Resources'][$resourceNameCheck])) {

                $this->resources['Aliases'][$alias] = $resourceNameCheck;
                $this->resources['Resources'][$resourceNameCheck]['Aliases'][$alias] = true;

                // add alias to the service manager as well (setup as not shared)
                if ($this->hasService($alias)) {
                    $this->setService($alias, null);
                }
                $this->setService($alias, $this->resources['Resources'][$resourceNameCheck]['Connection']);

                return true;
            }
        }

        return false;
    }


    /**
     * Remove resource alias and return true/false upon success
     *
     * @param string $alias
     * @return boolean
     */
    public function removeResourceAlias($alias)
    {
        if (isset($this->resources['Resources'][$alias])) {

            // alias specified is not just an alias but a full resource entry

        } elseif (isset($this->resources['Aliases'][$alias])) {

            if (isset($this->resources['Resources'][$this->resources['Aliases'][$alias]]['Aliases'][$alias])) {
                unset($this->resources['Resources'][$this->resources['Aliases'][$alias]]['Aliases'][$alias]);
            }
            unset($this->resources['Aliases'][$alias]);

            // remove the alias from the service manager
            if ($this->hasService($alias)) {
                $this->setService($alias, null);
            }

            return true;
        }

        return false;
    }


    /**
     * Confirm if a resource has been setup
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return boolean
     */
    public function isResource($resourceName = '')
    {
        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $resourceName = $this->getResourceNameByAlias($resourceName);
        if ($resourceName && isset($this->resources['Resources'][$resourceName])) {
            return true;
        }

        return false;
    }


    /**
     * Confirm if a resouce has been setup and is currently connected
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return boolean
     */
    public function isResourceConnected($resourceName='')
    {
        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $resourceName = $this->getResourceNameByAlias($resourceName);

        if ($resourceName && isset($this->resources['Resources'][$resourceName])) {
            return $this->resources['Resources'][$resourceName]['Connection']->getIsActive();
        }

        return false;
    }


    /**
     * Remove resource and close the existing connection if required
     *
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return boolean
     */
    public function removeResource($resourceName = '')
    {
        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $resourceName = $this->getResourceNameByAlias($resourceName);

        if ($resourceName && isset($this->resources['Resources'][$resourceName])) {

            if ($this->isResourceConnected($resourceName)) {
                $ok = $this->disconnectResource($resourceName);
            }

            $aliases = $this->resources['Resources'][$resourceName]['Aliases'];

            if ($aliases) {
                foreach ($aliases as $alias => $data) {
                    $this->removeResourceAlias($alias);
                }
            }

            unset($this->resources['Resources'][$resourceName]);

            // remove the resource from the service manager
            if ($this->hasService($resourceName)) {
                $this->setService($resourceName, null);
            }

            return true;
        }

        return false;
    }


    /**
     * Remove all DB resources and close the existing connections if required
     * optionally leave the 'db' connection open (default is to leave open so sessions etc can be completed)
     *
     * @param boolean $includeCore default false
     * @return boolean $success
     */
    public function removeAllResources($includeCore = false)
    {
        foreach ($this->resources['Resources'] as $resource => $data) {
            if ($resource == 'db' && !$includeCore) {
                // do not remove
            } else {
                $success = $this->removeResource($resource);
            }
        }
        if ($includeCore) {
            $this->resources['Aliases'] = array();
        }
        return true;
    }


    /**
     * Check components for db connections that have not been registered via the connection manager
     */
    public function checkAllServices()
    {
        $services = $this->getServices();
        foreach ($services as $serviceName => $service) {
            if ($service instanceof yii\db\Connection) {
                if (!isset($this->resources['Resources'][$serviceName])) {
                    $resourceName = $this->getResourceNameByAlias($serviceName);
                    if ($resourceName) {
                        throw new \Concord\Db\Exception('Service manager has db connection instance that is also an alias within ConnectionManager');
                    }
                    $class = get_class($service);
                    $this->extendResourceArray($serviceName, $service, $class);
                }
            }
        }
    }


    /**
     * Get a count of the number of resources defined
     *
     * @return integer:
     */
    public function getResourceCount()
    {
        return count($this->resources['Resources']);
    }


    /**
     * Get a count of the number of aliases
     *
     * @return integer:
     */
    public function getAliasCount()
    {
        return count($this->resources['Aliases']);
    }


    /**
     * Get an array of the setup resources, by default excluding connection objects
     *
     * @param boolean $includeConnections include the connection objects in the response
     * @param boolean $checkServices check services for db connections that have not been registered via the connection manager
     * @return array:
     */
    public function getResourceArray($includeConnections = false, $checkServices = true)
    {

        if ($checkServices) {
            $this->checkAllServices();
        }

        $array = array();
        $array['Resources'] = array();
        $array['Aliases'] = $this->resources['Aliases'];
        foreach ($this->resources['Resources'] as $resource => $data) {
            $settings = $data['Settings'];
            $settings['password'] = '{password}';
            $array['Resources'][$resource] = array(
                'Aliases' => $data['Aliases'],
                'Settings' => $settings,
                'Default' => ($resource == $this->defaultResourceName ? true : false),
                'Connected' => $data['Connection']->getIsActive(),
            );
            if ($includeConnections) {
                $array['Resources'][$resource]['Connection'] = $data['Connection'];
            }
        }
        return $array;
    }


    /**
     * Alias fpr $this->getResourceArray()
     * @return array
     */
    public function toArray() {
        return $this->getResourceArray(true);
    }


    /**
     * Obtain resource settings
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return array|false
     */
    public function getResourceSettings($resourceName = '')
    {
        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $resourceName = $this->getResourceNameByAlias($resourceName);
        if ($resourceName && isset($this->resources['Resources'][$resourceName]['Settings'])) {
            return $this->resources['Resources'][$resourceName]['Settings'];
        }
        return false;
    }


    /**
     * Obtain the last error code info on the specified connection
     * <code>
     *      list($pdoErrorCode, $dbErrorCode, $dbErrorString) = Yii::$app->getComponent('dbFactory')->getLastError('db');
     * </code>
     * @param string $resourceName [OPTIONAL] (default is the current default resource name)
     * @return array|false
     */
    public function getLastError($resourceName = '')
    {
        $resourceName = ($resourceName != '' ? $resourceName : $this->defaultResourceName);
        $connection = $this->getConnection($resourceName);
        if ($connection) {
            return $connection->pdo->errorInfo();
        }
        return false;
    }

    /**
     * Encrypt a string suitable for storing against a db resource for use within
     * this connection manager
     *
     * @param string $unencrypted
     * @return string encrypted
     */
    private function encrypt($unencrypted)
    {
        $td = mcrypt_module_open('blowfish', '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? MCRYPT_RAND : MCRYPT_DEV_RANDOM));
        $key = substr(md5($this->secretKey), 0, mcrypt_enc_get_key_size($td));
        mcrypt_generic_init($td, $key, $iv);
        $enc = mcrypt_generic($td, $unencrypted);
        $encrypted = trim($enc) . "||||" . $iv;
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return '#' . base64_encode($encrypted);
    }

    /**
     * Decrypt a db resource password
     *
     * @param string $encrypted encrypted
     * @return string decrypted
     */
    private function decrypt($encrypted)
    {
        if (substr($encrypted,0,1) == '#') {
            $encrypted = substr($encrypted,1);
        } else {
            $encrypted = '';
        }
        if ($encrypted != '') {
            $encrypted = base64_decode($encrypted);
            list($encrypted,$iv) = explode("||||", $encrypted,2);
            $td = mcrypt_module_open('blowfish', '', 'ecb', '');
            $key = substr(md5($this->secretKey),0,mcrypt_enc_get_key_size($td));
            mcrypt_generic_init($td, $key, $iv);
            $unencrypted = mdecrypt_generic($td, $encrypted);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            return trim($unencrypted);
        }
        return '';
    }

}
