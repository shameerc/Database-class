<?php

    /**
     * High level database abstraction class.
     * This class is intented to give an abstraction to basic Driver dependent
     * classes. Instead of directly calling them we can specify the type and
     * database connection details here in a standard dsn (Database Source Name)
     * format (given below). This will help us incase if we need a dbal to be
     * integrated to the system
     * 
     * @package     Database
     * @category    Database
     * @author      Shameer
     */

    // Default dsn values as given in the config file
    // The application must either include the config file 
    // with the db connection information or, should set it
    // using factory method
    $GLOBALS['_default_dsn'] =  array('dbhost'  => DB_HOST,
                                      'phptype' => DB_TYPE,
                                      'password'=> DB_PASS,
                                      'port'    => '3306',
                                      'username'=> DB_USER,
                                      'database'=> DB
                                     );
    //Start class Database
    class Database {
        /**
         * @var string Used to set the error informations
         */
        public  $errorInfo;

        /**
         * @var array Used to store the default dsn of current instance
         */
        private $default_dsn;

        /**
         * @var array Stores instances
         */
        public static $_instances;
        /**
         * Constructor
         * @param array $dsn
         */
        public function  __construct($dsn = null) {
            if($dsn) {
                if($this->isValid($dsn))
                    $this->default_dsn = $dsn;
                else {
                    throw new Exception("Invalide data source name");
                }
            }
            else {
                $this->default_dsn = $GLOBALS['_default_dsn'];
            }
        }
        /**
         * @param  mixed   $dsn Not required. Returns an instance of database object
         *                 that corresponds to the dsn. Only one object will be
         *                 created for a particular dsn.
         * @return mixed   Database object of the type @var $dsn['phptype'].
         */
        public function factory($dsn = null)
        {
            $dsn = $dsn == null ? Database::getDsn() : $dsn;
            //Check if the dsn is valid
            if (Database::isValid($dsn)) {
                $phpType  = $dsn['phptype'];
                $database = $dsn['database'];
            } else {
                throw new Exception("Invalid dsn. Please check the specifications");
            }
            //If an object for $phpType and $database already created , it will return
            //that, else will create a new instance.
            if (!($db = self::$_instances[$phpType][$database])) {
                try { 
                     $db = Database::loadDriver($phpType);
                     $db->setDsn($dsn);
                     $db->dbConnect();
                }
                catch (Exception $e){
                    echo $e->getMessage();
                }
                // Add the object to created instances array
                self::$_instances[$phpType][$database] = $db;
            }
            return $db;
        }

        /**
         * @param  mixed $type  Database driver type
         * @return mixed Object of database driver
         */
        public static function loadDriver($type)
        { 
            $file = _SYSTEM_ . 'database' .DS. 'driver' .DS. $type .'.php';
            require_once $file;
            $className ='Driver_'.$type;
            return new $className();
        }

        /**
         * Returns an object of global dsn
         */
        public static function singleton()
        {
            return Database::factory($GLOBALS['_default_dsn']);
        }
        
        /**
         * @return array dsn for current instace
         */
        public function getDsn()
        {
            if(isset($this) && isset($this->default_dsn)) {
                return $this->default_dsn;
            }
            else {
                return $GLOBALS['_default_dsn'];
            }
        }
        
        /**
         * @param  array $dsn "Data source name"
         * @return boolean true if dsn is valid
         *                 else false
         */
        public function isValid($dsn)
        {
            if (empty($dsn['phptype'])
                    || empty($dsn['dbhost'])
                    || empty($dsn['username'])
                    || empty($dsn['database']))
                return false;
            else {
                $dsn =array_merge($GLOBALS['_default_dsn'], $dsn);
                self::$_instances['_dsn'][] = $dsn;
                return true;
            }
        }

    }

?>
