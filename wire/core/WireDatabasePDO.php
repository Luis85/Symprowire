<?php namespace ProcessWire;

/**
 * ProcessWire PDO Database
 *
 * Serves as a wrapper to PHP’s PDO class
 * 
 * ProcessWire 3.x, Copyright 2021 by Ryan Cramer
 * https://processwire.com
 *
 */

/**
 * Database class provides a layer on top of mysqli
 * 
 * #pw-summary All database operations in ProcessWire are performed via this PDO-style database class.
 * 
 * @method void unknownColumnError($column) #pw-internal
 * @property bool $debugMode
 *
 */
class WireDatabasePDO extends Wire implements WireDatabase {

	const operatorTypeComparison = 0;
	const operatorTypeBitwise = 1;
	const operatorTypeAny = 2;

	/**
	 * Log of all queries performed in this instance
	 *
	 * @var array
	 *
	 */
	protected $queryLog = array();

	/**
	 * Max queries allowedin the query log (set from $config->dbQueryLogMax)
	 *
	 * @var int
	 *
	 */
	protected $queryLogMax = 500;

	/**
	 * Whether queries will be logged
	 *
	 */
	protected $debugMode = false;

	/**
	 * Cached result from getTables() method
	 *
	 * @var array
	 *
	 */
	protected $tablesCache = array();

	/**
	 * Data for read-write PDO connection
	 *
	 * @var array
	 *
	 */
	protected $writer = array(
		'pdo' => null,
		'init' => false,
		'commands' => array( 
			// commands that rewrite a writable connection
			'alter',
			'call',
			'comment',
			'commit',
			'create',
			'delete',
			'drop',
			'insert',
			'lock',
			'merge',
			'rename',
			'replace',
			'rollback',
			'savepoint',
			'set',
			'start',
			'truncate',
			'unlock',
			'update',
		)
	);

	/**
	 * Data for read-only PDO connection
	 *
	 * @var array
	 *
	 */
	protected $reader = array(
		'pdo' => null,
		'has' => false,  // is reader available? 
		'init' => false, // is reader initalized?
		'allow' => true, // is reader allowed? (false when in transaction, etc.)
	);

	/**
	 * Last used PDO connection
	 *
	 * @var null|\PDO
	 *
	 */
	protected $pdoLast = null;

	/**
	 * Whether or not our _init() has been called for the current $pdo connection
	 *
	 * @var bool
	 *
	 */
	protected $init = false;

	/**
	 * Strip 4-byte characters in “quote” and “escapeStr” methods? (only when dbEngine is not utf8mb4)
	 *
	 * @var bool
	 *
	 */
	protected $stripMB4 = false;

	/**
	 * Lowercase value of $config->dbEngine
	 *
	 * @var string
	 *
	 */
	protected $engine = '';

	/**
	 * Lowercase value of $config->dbCharset
	 *
	 * @var string
	 *
	 */
	protected $charset = '';

	/**
	 * Regular comparison operators
	 *
	 * @var array
	 *
	 */
	protected $comparisonOperators = array('=', '<', '>', '>=', '<=', '<>', '!=');

	/**
	 * Bitwise comparison operators
	 *
	 * @var array
	 *
	 */
	protected $bitwiseOperators = array('&', '~', '&~', '|', '^', '<<', '>>');

	/**
	 * Substitute variable names according to engine as used by getVariable() method
	 *
	 * @var array
	 *
	 */
	protected $subVars = array(
		'myisam' => array(),
		'innodb' => array(
			'ft_min_word_len' => 'innodb_ft_min_token_size',
			'ft_max_word_len' => 'innodb_ft_max_token_size',
		),
	);

	/**
	 * PDO connection settings
	 *
	 */
	private $pdoConfig = array(
		'dsn' => '',
		'user' => '',
		'pass' => '',
		'options' => '',
		'reader' => array(
			'dsn' => '',
			'user' => '',
			'pass' => '',
			'options' => '',
		),
	);

	/**
	 * Cached values from getVariable method
	 *
	 * @var array associative of name => value
	 *
	 */
	protected $variableCache = array();

	/**
	 * Cached InnoDB stopwords (keys are the stopwords and values are irrelevant)
	 *
	 * @var array|null Becomes array once loaded
	 *
	 */
	protected $stopwordCache = null;

	/**
	 * Create a new PDO instance from ProcessWire $config API variable
	 *
	 * If you need to make other PDO connections, just instantiate a new WireDatabasePDO (or native PDO)
	 * rather than calling this getInstance method.
	 *
	 * #pw-internal
	 *
	 * @param Config $config
	 *
	 * @return WireDatabasePDO
	 * @throws WireException
	 *
	 */
	public static function getInstance(Config $config) {

		if(!class_exists('\PDO')) {
			throw new WireException('Required PDO class (database) not found - please add PDO support to your PHP.');
		}

		$username = $config->dbUser;
		$password = $config->dbPass;
		$charset = $config->dbCharset;
		$options = $config->dbOptions;
		$reader = $config->dbReader;
		$initCommand = str_replace('{charset}', $charset, $config->dbInitCommand);

		if(!is_array($options)) $options = array();

		if(!isset($options[\PDO::ATTR_ERRMODE])) {
			$options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
		}

		if($initCommand && !isset($options[\PDO::MYSQL_ATTR_INIT_COMMAND])) {
			$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = $initCommand;
		}

		$dsnArray = array(
			'socket' => $config->dbSocket,
			'name' => $config->dbName,
			'host' => $config->dbHost,
			'port' => $config->dbPort,
		);

		$data = array(
			'dsn' => self::dsn($dsnArray),
			'user' => $username,
			'pass' => $password,
			'options' => $options,
		);

		if(!empty($reader)) { 
			if(isset($reader['host']) || isset($reader['socket'])) {
				// single reader
				$reader['dsn'] = self::dsn(array_merge($dsnArray, $reader));
				$reader = array_merge($data, $reader);
				$data['reader'] = $reader;
			} else {
				// multiple readers
				$readers = array();
				foreach($reader as $r) {
					if(empty($r['host']) && empty($r['socket'])) continue;
					$r['dsn'] = self::dsn(array_merge($dsnArray, $r));
					$readers[] = array_merge($data, $r);
				}
				$data['reader'] = $readers;
			}
		}

		$database = new WireDatabasePDO($data);
		$database->setDebugMode($config->debug);
		$config->wire($database);
		// $database->_init();

		return $database;
	}

	/**
	 * Create a PDO DSN string from array
	 *
	 * #pw-internal
	 *
	 * @param array $options May contain keys: 'name', 'host', 'port', 'socket' (if applies), 'type' (default=mysql)
	 *
	 * @return string
	 * @since 3.0.175
	 *
	 */
	static public function dsn(array $options) {
		$defaults = array(
			'type' => 'mysql',
			'socket' => '',
			'name' => '',
			'host' => '',
			'port' => '',
		);
		$options = array_merge($defaults, $options);
		if($options['socket']) {
			// if socket is provided ignore $host and $port and use socket instead
			$dsn = "mysql:unix_socket=$options[socket];dbname=$options[name];";
		} else {
			$dsn = "mysql:dbname=$options[name];host=$options[host]";
			if($options['port']) $dsn .= ";port=$options[port]";
		}
		return $dsn;
	}

	/**
	 * Construct WireDatabasePDO
	 *
	 * ~~~~~
	 * // The following are required to construct a WireDatabasePDO
	 * $dsn = 'mysql:dbname=mydb;host=myhost;port=3306';
	 * $username = 'username';
	 * $password = 'password';
	 * $driver_options = []; // optional
	 *
	 * // Construct option A
	 * $db = new WireDatabasePDO($dsn, $username, $password, $driver_options);
	 *
	 * // Construct option B
	 * $db = new WireDatabasePDO([
	 *   'dsn' => $dsn,
	 *   'user' => $username,
	 *   'pass' => $password,
	 *   'options' => $driver_options, // optional
	 *   'reader' => [ // optional
	 *     'dsn' => '…',
	 *     …
	 *   ],
	 *   …
	 * ]);
	 * ~~~~~
	 *
	 * #pw-internal
	 *
	 * @param string|array $dsn DSN string or (3.0.175+) optionally use array of connection options and omit all remaining arguments.
	 * @param null $username
	 * @param null $password
	 * @param array $driver_options
	 *
	 */
	public function __construct($dsn, $username = null, $password = null, array $driver_options = array()) {
		parent::__construct();
		if(is_array($dsn) && isset($dsn['dsn'])) {
			// configuration data provided in $dsn argument array
			if($username !== null && empty($dsn['user'])) $dsn['user'] = $username;
			if($password !== null && empty($dsn['pass'])) $dsn['pass'] = $password;
			if(!isset($dsn['options'])) $dsn['options'] = $driver_options;
			$this->pdoConfig = array_merge($this->pdoConfig, $dsn);
			if(!empty($this->pdoConfig['reader'])) {
				if(!empty($this->pdoConfig['reader']['dsn'])) {
					// single reader
					$this->reader['has'] = true;
				} else if(!empty($this->pdoConfig['reader'][0]['dsn'])) {
					// multiple readers
					$this->reader['has'] = true;
				}
			}
		} else {
			// configuration data in direct arguments
			$this->pdoConfig['dsn'] = $dsn;
			$this->pdoConfig['user'] = $username;
			$this->pdoConfig['pass'] = $password;
			$this->pdoConfig['options'] = $driver_options;
		}
		// $this->pdo();
	}

	/**
	 * Additional initialization after DB connection established and Wire instance populated
	 *
	 * #pw-internal
	 *
	 * @param \PDO|null
	 *
	 */
	public function _init($pdo = null) {

		if(!$this->isWired()) return;

		if($pdo === $this->reader['pdo']) {
			if($this->reader['init']) return;
			$this->reader['init'] = true;
		} else {
			if($this->writer['init']) return;
			$this->writer['init'] = true;
			if($pdo === null) $pdo = $this->writer['pdo'];
		}

		$config = $this->wire()->config;

		if(empty($this->engine)) {
			$this->engine = strtolower($config->dbEngine);
			$this->charset = strtolower($config->dbCharset);
			$this->stripMB4 = $config->dbStripMB4 && $this->charset != 'utf8mb4';
			$this->queryLogMax = (int) $config->dbQueryLogMax;
		}

		if($config->debug && $pdo) {
			// custom PDO statement for debug mode
			$this->debugMode = true;
			$pdo->setAttribute(
				\PDO::ATTR_STATEMENT_CLASS,
				array(__NAMESPACE__ . "\\WireDatabasePDOStatement", array($this))
			);
		}

		$sqlModes = $config->dbSqlModes;

		if(is_array($sqlModes)) {
			// ["5.7.0" => "remove:mode1,mode2/add:mode3"]
			foreach($sqlModes as $minVersion => $commands) {
				if(strpos($commands, '/') !== false) {
					$commands = explode('/', $commands);
				} else {
					$commands = array($commands);
				}
				foreach($commands as $modes) {
					$modes = trim($modes);
					if(empty($modes)) continue;
					$action = 'set';
					if(strpos($modes, ':')) list($action, $modes) = explode(':', $modes);
					$this->sqlMode(trim($action), trim($modes), $minVersion, $pdo);
				}
			}
		}
	}

	/**
	 * Return the actual current PDO connection instance
	 *
	 * If connection is lost, this will restore it automatically.
	 *
	 * #pw-group-PDO
	 *
	 * @param string|\PDOStatement|null SQL, statement, or statement type (reader or primary) (3.0.175+)
	 *
	 * @return \PDO
	 *
	 */
	public function pdo($type = null) {
		if($type === null) return $this->pdoWriter();
		return $this->pdoType($type);
	}

	/**
	 * Return read-write (primary) PDO connection 
	 *
	 * @return \PDO
	 * @since 3.0.175
	 *
	 */
	protected function pdoWriter() {
		if(!$this->writer['pdo']) {
			$this->writer['init'] = false;
			$pdo = new \PDO(
				$this->pdoConfig['dsn'],
				$this->pdoConfig['user'],
				$this->pdoConfig['pass'],
				$this->pdoConfig['options']
			);
			$this->writer['pdo'] = $pdo;
			$this->_init($pdo);
		} else {
			$pdo = $this->writer['pdo'];
		}
		$this->pdoLast = $pdo;
		return $pdo;
	}

	/**
	 * Return read-only PDO connection if available or read/write PDO connection if not
	 * 
	 * @return \PDO
	 * @since 3.0.175
	 * 
	 */
	protected function pdoReader() {
		if(!$this->allowReader()) return $this->pdoWriter();
		
		if($this->reader['pdo']) {
			$pdo = $this->reader['pdo'];
			$this->pdoLast = $pdo;
			return $pdo;
		}
		
		$this->reader['init'] = false;
		$lastException = null;
		
		if(isset($this->pdoConfig['reader']['dsn'])) {
			// just one reader
			$readers = array($this->pdoConfig['reader']);
		} else {
			// randomly select a reader
			$readers = $this->pdoConfig['reader'];
			shuffle($readers);
		}
		
		do {
			// try readers till we find one that gives us a connection
			$reader = array_shift($readers);
			try {
				$pdo = new \PDO($reader['dsn'], $reader['user'], $reader['pass'], $reader['options']);
			} catch(\PDOException $e) {
				$pdo = null;
				$lastException = $e;
			}
		} while(!$pdo && count($readers));
		
		if(!$pdo) throw $lastException;
		
		$this->reader['pdo'] = $pdo;
		$this->_init($pdo);
		$this->pdoLast = $pdo;
		
		return $pdo;
	}
	
	/**
	 * Return correct PDO instance type (reader or writer) based on given statement
	 *
	 * @param string|\PDOStatement $statement
	 * @param bool $getName Get name of PDO type rather than instance? (default=false)
	 * @return \PDO|string
	 *
	 */
	protected function pdoType(&$statement, $getName = false) {

		$reader = 'reader';
		$writer = 'writer';

		if(!$this->reader['has']) return $getName ? $writer : $this->pdoWriter();

		if($statement === $writer || $statement === $reader) {
			$type = $statement;
		} else if(!$this->reader['has']) {
			$type = $writer;
		} else if(!is_string($statement)) {
			// PDOStatement or other, always return write
			// @todo add support for inspection of PDOStatement
			$type = $writer;
		} else if(stripos($statement, 'select') === 0) {
			$type = $reader;
		} else if(stripos($statement, 'insert') === 0) {
			$type = $writer;
		} else {
			$pos = strpos($statement, ' ');
			$word = strtolower(($pos ? substr($statement, 0, $pos) : $statement));
			if($word === 'set') {
				// all 'set' commands are read-only allowed except autocommit and transaction
				$word = trim(substr($statement, $pos + 1, 12));
				if(stripos($word, 'autocommit') === 0 || stripos($word, 'transaction') === 0) {
					$type = $writer;
				} else {
					$type = $reader;
				}
			} else if($word === 'lock') {
				if(!$getName) $this->allowReader(false);
				$type = $writer;
			} else if($word === 'unlock') {
				if(!$getName) $this->allowReader(true);
				$type = $writer;
			} else {
				$type = in_array($word, $this->writer['commands']) ? $writer : $reader;
			}
		}

		if($type === $reader && !$this->reader['allow']) $type = $writer;

		if($getName) return $type;

		return $type === 'reader' ? $this->pdoReader() : $this->pdoWriter();
	}

	/**
	 * Return last used PDO connection
	 *
	 * @return \PDO
	 * @since 3.0.175
	 * 
	 */
	protected function pdoLast() {
		if($this->pdoLast) {
			$pdo = $this->pdoLast;
			if($pdo === $this->reader['pdo'] && !$this->reader['allow']) $pdo = null;
		} else {
			$pdo = null;
		}
		if($pdo === null) $pdo = $this->pdoWriter();
		return $pdo;
	}

	/**
	 * Fetch the SQLSTATE associated with the last operation on the statement handle
	 * 
	 * #pw-group-PDO
	 * 
	 * @return string
	 * @link http://php.net/manual/en/pdostatement.errorcode.php
	 * 
	 */
	public function errorCode() {
		return $this->pdoLast()->errorCode();
	}

	/**
	 * Fetch extended error information associated with the last operation on the database handle
	 * 
	 * #pw-group-PDO
	 * 
	 * @return array
	 * @link http://php.net/manual/en/pdo.errorinfo.php
	 * 
	 */
	public function errorInfo() {
		return $this->pdoLast()->errorInfo();
	}

	/**
	 * Retrieve a database connection attribute
	 * 
	 * #pw-group-PDO
	 * 
	 * @param int $attribute
	 * @return mixed
	 * @link http://php.net/manual/en/pdo.getattribute.php
	 * 
	 */
	public function getAttribute($attribute) {
		return $this->pdoLast()->getAttribute($attribute); 
	}

	/**
	 * Sets an attribute on the database handle
	 * 
	 * #pw-group-PDO
	 * 
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 * @link http://php.net/manual/en/pdo.setattribute.php
	 * 
	 */
	public function setAttribute($attribute, $value) {
		return $this->pdoLast()->setAttribute($attribute, $value); 
	}

	/**
	 * Returns the ID of the last inserted row or sequence value
	 * 
	 * #pw-group-PDO
	 * 
	 * @param string|null $name
	 * @return string
	 * @link http://php.net/manual/en/pdo.lastinsertid.php
	 * 
	 */
	public function lastInsertId($name = null) {
		return $this->pdoWriter()->lastInsertId($name); 
	}

	/**
	 * Executes an SQL statement, returning a result set as a PDOStatement object
	 * 
	 * #pw-group-PDO
	 * 
	 * @param string $statement
	 * @param string $note
	 * @return \PDOStatement
	 * @link http://php.net/manual/en/pdo.query.php
	 * 
	 */
	public function query($statement, $note = '') {
		if($this->debugMode) $this->queryLog($statement, $note); 
		$pdo = $this->pdoType($statement);
		return $pdo->query($statement); 
	}

	/**
	 * Initiates a transaction
	 * 
	 * #pw-group-PDO
	 * 
	 * @return bool
	 * @link http://php.net/manual/en/pdo.begintransaction.php
	 * 
	 */
	public function beginTransaction() {
		$this->allowReader(false);
		return $this->pdoWriter()->beginTransaction();
	}

	/**
	 * Checks if inside a transaction
	 * 
	 * #pw-group-PDO
	 * 
	 * @return bool
	 * @link http://php.net/manual/en/pdo.intransaction.php
	 * 
	 */
	public function inTransaction() {
		return (bool) $this->pdoWriter()->inTransaction();
	}

	/**
	 * Are transactions available with current DB engine (or table)?
	 * 
	 * #pw-group-PDO
	 * 
	 * @param string $table Optionally specify a table to specifically check to that table
	 * @return bool
	 * 
	 */
	public function supportsTransaction($table = '') {
		$engine = '';
		if($table) {
			$query = $this->pdoReader()->prepare('SHOW TABLE STATUS WHERE name=:name'); 
			$query->bindValue(':name', $table); 
			$query->execute();
			if($query->rowCount()) {
				$row = $query->fetch(\PDO::FETCH_ASSOC);
				$engine = empty($row['engine']) ? '' : $row['engine'];
			}
			$query->closeCursor();
		} else {
			$engine = $this->engine;
		}
		return strtoupper($engine) === 'INNODB';
	}

	/**
	 * Allow a new transaction to begin right now? (i.e. supported and not already in one)
	 * 
	 * Returns combined result of supportsTransaction() === true and inTransaction() === false.
	 * 
	 * #pw-group-PDO
	 * 
	 * @param string $table Optional table that transaction will be for
	 * @return bool
	 * @since 3.0.140
	 * 
	 */
	public function allowTransaction($table = '') {
		return $this->supportsTransaction($table) && !$this->inTransaction();
	}

	/**
	 * Commits a transaction
	 * 
	 * #pw-group-PDO
	 * 
	 * @return bool
	 * @link http://php.net/manual/en/pdo.commit.php
	 * 
	 */
	public function commit() {
		$this->allowReader(true);
		return $this->pdoWriter()->commit();
	}

	/**
	 * Rolls back a transaction
	 * 
	 * #pw-group-PDO
	 * 
	 * @return bool
	 * @link http://php.net/manual/en/pdo.rollback.php
	 * 
	 */
	public function rollBack() {
		$this->allowReader(true);
		return $this->pdoWriter()->rollBack();
	}

	/**
	 * Get an array of all queries that have been executed thus far
	 *
	 * Active in ProcessWire debug mode only
	 * 
	 * #pw-internal
	 *
	 * @deprecated use queryLog() method instead
	 * @return array
	 *
	 */
	static public function getQueryLog() {
		/** @var WireDatabasePDO $database */
		$database = wire('database');
		return $database->queryLog();
	}

	/**
	 * Prepare an SQL statement for accepting bound parameters
	 * 
	 * #pw-group-PDO
	 * 
	 * @param string $statement
	 * @param array|string|bool $driver_options Optionally specify one of the following: 
	 *  - Boolean true for WireDatabasePDOStatement rather than PDOStatement (also assumed when debug mode is on) 3.0.162+
	 *  - Driver options array 
	 *  - or you may specify the $note argument here
	 * @param string $note Debug notes to save with query in debug mode
	 * @return \PDOStatement|WireDatabasePDOStatement
	 * @link http://php.net/manual/en/pdo.prepare.php
	 * 
	 */
	public function prepare($statement, $driver_options = array(), $note = '') {
		if(is_string($driver_options)) {
			$note = $driver_options; 
			$driver_options = array();
		} else if($driver_options === true) {
			$driver_options = array(
				\PDO::ATTR_STATEMENT_CLASS => array(__NAMESPACE__ . "\\WireDatabasePDOStatement", array($this))
			);
		}
		$pdo = $this->reader['has'] ? $this->pdoType($statement) : $this->pdoWriter();
		$pdoStatement = $pdo->prepare($statement, $driver_options);
		if($this->debugMode) {
			if($pdoStatement instanceof WireDatabasePDOStatement) {
				/** @var WireDatabasePDOStatement $pdoStatement */
				$pdoStatement->setDebugNote($note);
			} else {
				$this->queryLog($statement, $note);
			}
		}
		return $pdoStatement;
	}

	/**
	 * Execute an SQL statement string
	 * 
	 * If given a PDOStatement, this method behaves the same as the execute() method. 
	 * 
	 * #pw-group-PDO
	 * 
	 * @param string|\PDOStatement $statement
	 * @param string $note
	 * @return bool|int
	 * @throws \PDOException
	 * @link http://php.net/manual/en/pdo.exec.php
	 * 
	 */
	public function exec($statement, $note = '') {
		if(is_object($statement) && $statement instanceof \PDOStatement) {
			return $this->execute($statement);
		}
		if($this->debugMode) $this->queryLog($statement, $note); 
		$pdo = $this->reader['has'] ? $this->pdoType($statement) : $this->pdoWriter();
		return $pdo->exec($statement);
	}
	
	/**
	 * Execute a PDO statement, with retry and error handling
	 * 
	 * Given a PDOStatement ($query) this method will execute the statement and return
	 * true or false as to whether it was successful. 
	 * 
	 * Unlike other PDO methods, this one (native to ProcessWire) will retry queries
	 * if they failed due to a lost connection. By default it will retry up to 3 times,
	 * but you can adjust this number as needed in the arguments. 
	 * 
	 * ~~~~~
	 * // prepare the query
	 * $query = $database->prepare("SELECT id, name FROM pages LIMIT 10");
	 * // you can do the following, rather than native PDO $query->execute(); 
	 * $database->execute($query);
	 * ~~~~~
	 * 
	 * #pw-group-custom
	 *
	 * @param \PDOStatement $query
	 * @param bool $throw Whether or not to throw exception on query error (default=true)
	 * @param int $maxTries Deprecated/argument does nothing (was: “Max number of times it will attempt to retry query on error”)
	 * @return bool True on success, false on failure. Note if you want this, specify $throw=false in your arguments.
	 * @throws \PDOException
	 *
	 */
	public function execute(\PDOStatement $query, $throw = true, $maxTries = 3) {

		try {
			$result = $query->execute();
		} catch(\PDOException $e) {
			$result = false;
			if($query->errorCode() == '42S22') {
				// unknown column error
				$errorInfo = $query->errorInfo();
				if(preg_match('/[\'"]([_a-z0-9]+\.[_a-z0-9]+)[\'"]/i', $errorInfo[2], $matches)) {
					$this->unknownColumnError($matches[1]);
				}
			}
			if($throw) {
				throw $e;
			} else {
				$this->error($e->getMessage());
			}
			if($maxTries) {} // ignore, argument no longer used
		}

		return $result;
	}

	/**
	 * Hookable method called by execute() method when query encounters an unknown column
	 * 
	 * #pw-internal
	 *
	 * @param string $column Column format tableName.columnName
	 *
	 */
	protected function ___unknownColumnError($column) { }

	/**
	 * Log a query, start/stop query logging, or return logged queries
	 * 
	 * - To log a query, provide the $sql argument containing the query (string). 
	 * - To retrieve the query log, call this method with no arguments. 
	 * - Note the core only populates the query log when `$config->debug` mode is active.
	 * - Specify boolean true for $sql argument to reset and start query logging (3.0.173+)
	 * - Specify boolean false for $sql argument to stop query logging (3.0.173+)
	 * 
	 * #pw-group-custom
	 * 
	 * @param string $sql Query (string) to log, boolean true to reset/start query logging, boolean false to stop query logging
	 * @param string $note Any additional debugging notes about the query
	 * @return array|bool|int Returns query log array, boolean true on success, boolean false if not
	 * 
	 */
	public function queryLog($sql = '', $note = '') {
		if(empty($sql)) return $this->queryLog;
		if($sql === true) {
			$this->debugMode = true; 
			$this->queryLog = array();
			return true;
		} else if($sql === false) {
			$this->debugMode = false; 
			return true;
		}
		if(!$this->debugMode) return false;
		if(count($this->queryLog) > $this->queryLogMax) {
			if(isset($this->queryLog['error'])) {
				$qty = (int) $this->queryLog['error'];
			} else {
				$qty = 0;
			}
			$qty++;
			$this->queryLog['error'] = "$qty additional queries omitted because \$config->dbQueryLogMax = $this->queryLogMax";
			return false;
		} else {
			if($this->reader['has']) {
				$type = $this->pdoType($sql, true);
				$note = trim("$note [$type]");
			}
			$this->queryLog[] = $sql . ($note ? " -- $note" : "");
			return true;
		}
	}

	/**
	 * Get array of all tables in this database.
	 * 
	 * Note that this method caches its result unless you specify boolean false for the $allowCache argument. 
	 * 
	 * #pw-group-custom
	 *
	 * @param bool $allowCache Specify false if you don't want result to be cached or pulled from cache (default=true)
	 * @return array Returns array of table names
	 *
	 */
	public function getTables($allowCache = true) {
		if($allowCache && count($this->tablesCache)) return $this->tablesCache;
		$tables = array();
		$query = $this->query("SHOW TABLES");
		/** @noinspection PhpAssignmentInConditionInspection */
		while($col = $query->fetchColumn()) $tables[] = $col;
		if($allowCache) $this->tablesCache = $tables;
		return $tables; 
	}

	/**
	 * Get all columns from given table
	 * 
	 * By default returns array of column names. If verbose option is true then it returns
	 * an array of arrays, each having 'name', 'type', 'null', 'default', and 'extra' keys,
	 * indicating the column name, column type, whether it can be null, what it’s default value 
	 * is, and any extra information, such as whether it is auto_increment. The verbose option 
	 * also makes the return value indexed by column name (associative array).
	 * 
	 * @param string $table
	 * @param bool $verbose Include array of verbose information for each? (default=false)
	 * @return array 
	 * @since 3.0.180
	 * 
	 */
	public function getColumns($table, $verbose = false) {
		$columns[] = array();
		$table = $this->escapeTable($table);
		$query = $this->query("SHOW COLUMNS FROM $table");
		while($col = $query->fetch(\PDO::FETCH_ASSOC)) {
			$name = $col['Field'];
			if($verbose) {
				$columns[$name] = array(
					'name' => $name,
					'type' => $col['Type'],
					'null' => (strtoupper($col['Null']) === 'YES' ? true : false),
					'default' => $col['Default'],
					'extra' => $col['Extra'],
				);
			} else {
				$columns[] = $name;
			}
		}
		$query->closeCursor();
		return $columns;	
	}

	/**
	 * Does the given table exist in this database?
	 * 
	 * #pw-group-custom
	 * 
	 * @param string $table
	 * @return bool
	 * @since 3.0.133
	 * 
	 */
	public function tableExists($table) {
		$query = $this->prepare('SHOW TABLES LIKE ?');
		$query->execute(array($table));
		$result = $query->fetchColumn();
		return !empty($result);
	}

	/**
	 * Does the given column exist in given table? 
	 * 
	 * ~~~~~
	 * // Standard usage:
	 * if($database->columnExists('pages', 'name')) {
	 *   echo "The pages table has a 'name' column";
	 * }
	 * 
	 * // You can also bundle table and column together:
	 * if($database->columnExists('pages.name')) {
	 *   echo "The pages table has a 'name' column";
	 * }
	 * 
	 * $exists = $database->columnExists('pages', 'name', true); 
	 * if($exists) {
	 *   // associative array with indexes: Name, Type, Null, Key, Default, Extra
	 *   echo "The pages table has a 'name' column and here is verbose info: ";
	 *   print_r($exists); 
	 * }
	 * ~~~~~
	 *
	 * #pw-group-custom
	 * 
	 * @param string $table Specify table name (or table and column name in format "table.column").
	 * @param string $column Specify column name (or omit or blank string if already specified in $table argument). 
	 * @param bool $getInfo Return array of column info (with type info, etc.) rather than boolean?
	 * @return bool|array
	 * @since 3.0.154
	 * @throws WireDatabaseException
	 * 
	 */
	public function columnExists($table, $column = '', $getInfo = false) {
		if(empty($column)) {
			if(!strpos($table, '.')) throw new WireDatabaseException('No column specified');
			list($table, $column) = explode('.', $table, 2);
		}
		$exists = false;
		$table = $this->escapeTable($table);
		try {
			$query = $this->prepare("SHOW COLUMNS FROM `$table` WHERE Field=:column");
			$query->bindValue(':column', $column, \PDO::PARAM_STR);
			$query->execute();
			$numRows = (int) $query->rowCount();
			if($numRows) $exists = $getInfo ? $query->fetch(\PDO::FETCH_ASSOC) : true;
			$query->closeCursor();
		} catch(\Exception $e) {
			// most likely given table does not exist
			$exists = false;
		}
		return $exists;
	}

	/**
	 * Is the given string a database comparison operator?
	 * 
	 * #pw-group-custom
	 * 
	 * ~~~~~
	 * if($database->isOperator('>=')) {
	 *   // given string is a valid database operator
	 * } else {
	 *   // not a valid database operator
	 * }
	 * ~~~~~
	 *
	 * @param string $str 1-2 character operator to test
	 * @param bool|null|int $operatorType Specify a WireDatabasePDO::operatorType* constant (3.0.162+), or any one of the following (3.0.143+): 
	 *  - `NULL`: allow all operators (default value if not specified)
	 *  - `FALSE`: allow only comparison operators
	 *  - `TRUE`: allow only bitwise operators
	 * @param bool $get Return the operator rather than true, when valid? (default=false) Added 3.0.162
	 * @return bool True if valid, false if not
	 *
	 */
	public function isOperator($str, $operatorType = self::operatorTypeAny, $get = false) {
		
		$len = strlen($str);
		
		if($len > 2 || $len < 1) return false;
		
		if($operatorType === null || $operatorType === self::operatorTypeAny) {
			// allow all operators
			$operators = array_merge($this->comparisonOperators, $this->bitwiseOperators); 
			
		} else if($operatorType === true || $operatorType === self::operatorTypeBitwise) {
			// allow only bitwise operators
			$operators = $this->bitwiseOperators; 
			
		} else {
			// self::operatorTypeComparison
			$operators = $this->comparisonOperators;
		}
	
		if($get) {
			$key = array_search($str, $operators, true);
			return $key === false ? false : $operators[$key];
		} else {
			return in_array($str, $operators, true);
		}
	}

	/**
	 * Is given word a fulltext stopword for database engine?
	 * 
	 * @param string $word
	 * @param string $engine DB engine ('myisam' or 'innodb') or omit for current engine
	 * @return bool
	 * @since 3.0.160
	 * 
	 */
	public function isStopword($word, $engine = '') {
		$engine = $engine === '' ? $this->engine : strtolower($engine);
		if($engine === 'myisam') return DatabaseStopwords::has($word);
		if($this->stopwordCache === null) $this->getStopwords($engine, true);
		return isset($this->stopwordCache[strtolower($word)]);
	}

	/**
	 * Get all fulltext stopwords for database engine
	 * 
	 * @param string $engine Specify DB engine of "myisam" or "innodb" or omit for current DB engine
	 * @param bool $flip Return flipped array where stopwords are array keys rather than values? for isset() use (default=false)
	 * @return array
	 * 
	 */
	public function getStopwords($engine = '', $flip = false) {
		$engine = $engine === '' ? $this->engine : strtolower($engine);
		if($engine === 'myisam') return DatabaseStopwords::getAll();
		if($this->stopwordCache === null) { //  && $engine === 'innodb') {
			$cache = $this->wire()->cache;
			$stopwords = null;
			if($cache) {
				$stopwords = $cache->get('InnoDB.stopwords');
				if($stopwords) $stopwords = explode(',', $stopwords);
			}
			if(!$stopwords) {
				$query = $this->prepare('SELECT value FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD');
				$query->execute();
				$stopwords = $query->fetchAll(\PDO::FETCH_COLUMN, 0);
				$query->closeCursor();
				if($cache) $cache->save('InnoDB.stopwords', implode(',', $stopwords), WireCache::expireDaily);
			}
			$this->stopwordCache = array_flip($stopwords);
		}
		return $flip ? $this->stopwordCache : array_keys($this->stopwordCache);
	}

	/**
	 * Sanitize a table name for _a-zA-Z0-9
	 * 
	 * #pw-group-sanitization
	 *
	 * @param string $table String containing table name
	 * @return string Sanitized table name
	 *
	 */
	public function escapeTable($table) {
		$table = (string) trim($table); 
		if(ctype_alnum($table)) return $table; 
		if(ctype_alnum(str_replace('_', '', $table))) return $table;
		return preg_replace('/[^_a-zA-Z0-9]/', '_', $table);
	}

	/**
	 * Sanitize a column name for _a-zA-Z0-9
	 * 
	 * #pw-group-sanitization
	 *
	 * @param string $col
	 * @return string
	 *
	 */
	public function escapeCol($col) {
		return $this->escapeTable($col);
	}

	/**
	 * Sanitize a table.column string, where either part is optional
	 * 
	 * #pw-group-sanitization
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeTableCol($str) {
		if(strpos($str, '.') === false) return $this->escapeTable($str); 
		list($table, $col) = explode('.', $str); 
		return $this->escapeTable($table) . '.' . $this->escapeCol($col);
	}

	/**
	 * Sanitize comparison operator
	 * 
	 * @param string $operator
	 * @param bool|int|null $operatorType Specify a WireDatabasePDO::operatorType* constant (default=operatorTypeComparison)
	 * @param string $default Default/fallback operator to return if given one is not valid (default='=')
	 * @return string
	 * 
	 */
	public function escapeOperator($operator, $operatorType = self::operatorTypeComparison, $default = '=') {
		$operator = $this->isOperator($operator, $operatorType, true); 
		return $operator ? $operator : $default;
	}

	/**
	 * Escape a string value, same as $db->quote() but without surrounding quotes
	 * 
	 * #pw-group-sanitization
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function escapeStr($str) {
		return substr($this->quote($str), 1, -1);
	}

	/**
	 * Escape a string value, for backwards compatibility till PDO transition complete
	 * 
	 * #pw-internal
	 *
	 * @deprecated
	 * @param string $str
	 * @return string
	 *
	 */
	public function escape_string($str) {
		return $this->escapeStr($str); 
	}

	/**
	 * Quote and escape a string value
	 * 
	 * #pw-group-sanitization
	 * #pw-group-PDO
	 *
	 * @param string $str
	 * @return string
	 * @link http://php.net/manual/en/pdo.quote.php
	 *
	 */
	public function quote($str) {
		if($this->stripMB4 && is_string($str) && !empty($str)) {
			$str = $this->wire()->sanitizer->removeMB4($str);
		}
		return $this->pdoLast()->quote($str);
	}

	/**
	 * Escape a string value, plus escape characters necessary for a MySQL 'LIKE' phrase
	 * 
	 * #pw-group-sanitization
	 *
	 * @param string $like
	 * @return string
	 *
	 */
	public function escapeLike($like) {
		$like = $this->escapeStr($like); 
		return addcslashes($like, '%_'); 
	}

	/**
	 * Set whether debug mode is enabled for this database instance
	 * 
	 * #pw-internal
	 * 
	 * @param $debugMode
	 * 
	 */
	public function setDebugMode($debugMode) {
		$this->debugMode = (bool) $debugMode; 
	}

	/**
	 * @param string $key
	 * @return mixed|null|\PDO
	 * 
	 */
	public function __get($key) {
		if($key === 'pdo') return $this->pdo();
		if($key === 'pdoReader') return $this->pdoReader();
		if($key === 'pdoWriter') return $this->pdoWriter();
		if($key === 'debugMode') return $this->debugMode;
		return parent::__get($key);
	}

	/**
	 * Close the PDO connection
	 * 
	 * #pw-group-custom
	 * 
	 */
	public function closeConnection() {
		$this->reader['pdo'] = null;
		$this->writer['pdo'] = null;
		$this->reader['init'] = false;
		$this->writer['init'] = false;
	}

	/**
	 * Get the value of a MySQL variable
	 * 
	 * ~~~~~
	 * // Get the minimum fulltext index word length
	 * $value = $database->getVariable('ft_min_word_len');
	 * echo $value; // outputs "4"
	 * ~~~~~
	 * 
	 * #pw-group-custom
	 * 
	 * @param string $name Name of MySQL variable you want to retrieve
	 * @param bool $cache Allow use of cached values? (default=true)
	 * @param bool $sub Allow substitution of MyISAM variable names to InnoDB equivalents when InnoDB is engine? (default=true)
	 * @return string|int
	 * 
	 */
	public function getVariable($name, $cache = true, $sub = true) {
		if($sub && isset($this->subVars[$this->engine][$name])) $name = $this->subVars[$this->engine][$name]; 
		if($cache && isset($this->variableCache[$name])) return $this->variableCache[$name];
		$query = $this->prepare('SHOW VARIABLES WHERE Variable_name=:name');
		$query->bindValue(':name', $name);
		$query->execute();
		/** @noinspection PhpUnusedLocalVariableInspection */
		list($varName, $value) = $query->fetch(\PDO::FETCH_NUM);
		$this->variableCache[$name] = $value;
		$query->closeCursor();
		return $value;
	}

	/**
	 * Get MySQL/MariaDB version
	 * 
	 * Example return values:
	 *
	 *  - 5.7.23
	 *  - 10.1.34-MariaDB
	 * 
	 * @return string
	 * @since 3.0.166
	 * 
	 */
	public function getVersion() {
		return $this->getVariable('version', true, false); 
	}
	
	/**
	 * Get the regular expression engine used by database
	 * 
	 * Returns one of 'ICU' (MySQL 8.0.4+) or 'HenrySpencer' (earlier versions and MariaDB)
	 * 
	 * @return string
	 * @since 3.0.166
	 * @todo this will need to be updated when/if MariaDB adds version that uses ICU engine
	 * 
	 */
	public function getRegexEngine() {
		$version = $this->getVersion();
		$name = 'MySQL';
		if(strpos($version, '-')) list($version, $name) = explode('-', $version, 2);
		if(strpos($name, 'mariadb') === false) {
			if(version_compare($version, '8.0.4', '>=')) return 'ICU';
		}
		return 'HenrySpencer';
	}

	/**
	 * Get current database engine (lowercase) 
	 * 
	 * @return string
	 * @since 3.0.160
	 * 
	 */
	public function getEngine() {
		return $this->engine;
	}

	/**
	 * Get current database charset (lowercase)
	 * 
	 * @return string
	 * @since 3.0.160
	 * 
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Retrieve new instance of WireDatabaseBackups ready to use with this connection
	 * 
	 * See `WireDatabaseBackup` class for usage. 
	 * 
	 * #pw-group-custom
	 * 
	 * @return WireDatabaseBackup
	 * @throws WireException|\Exception on fatal error
	 * @see WireDatabaseBackup::backup(), WireDatabaseBackup::restore()
	 * 
	 */
	public function backups() {
	
		$path = $this->wire()->config->paths->assets . 'backups/database/';
		if(!is_dir($path)) {
			$this->wire()->files->mkdir($path, true); 
			if(!is_dir($path)) throw new WireException("Unable to create path for backups: $path"); 
		}

		$backups = new WireDatabaseBackup($path); 
		$backups->setWire($this->wire());
		$backups->setDatabase($this);
		$backups->setDatabaseConfig($this->wire()->config);
		$backups->setBackupOptions(array('user' => $this->wire()->user->name)); 
	
		return $backups; 
	}

	/**
	 * Get max length allowed for a fully indexed varchar column in ProcessWire
	 * 
	 * #pw-internal
	 * 
	 * @return int
	 * 
	 */
	public function getMaxIndexLength() {
		$max = 250; 
		if($this->charset === 'utf8mb4') {
			if($this->engine === 'innodb') {
				$max = 191; 
			}
		}
		return $max;
	}

	/**
	 * Enable or disable PDO reader instance, or omit argument to get current state
	 * 
	 * Returns true if reader is configured and allowed
	 * Returns false if reader is not configured or not allowed
	 * 
	 * @param bool $allow
	 * @return bool
	 * @since 3.0.175
	 * 
	 */
	protected function allowReader($allow = null) {
		if($allow !== null) $this->reader['allow'] = (bool) $allow;
		return $this->reader['has'] && $this->reader['allow'];
	}

	/**
	 * Get SQL mode, set SQL mode, add to existing SQL mode, or remove from existing SQL mode
	 * 
	 * #pw-group-custom
	 * 
	 * ~~~~~
	 * // Get SQL mode
	 * $mode = $database->sqlMode();
	 * 
	 * // Add an SQL mode
	 * $database->sqlMode('add', 'STRICT_TRANS_TABLES');
	 * 
	 * // Remove SQL mode if version at least 5.7.0
	 * $database->sqlMode('remove', 'ONLY_FULL_GROUP_BY', '5.7.0');
	 * ~~~~~
	 * 
	 * @param string $action Specify "get", "set", "add" or "remove". (default="get")
	 * @param string $mode Mode string or CSV string with SQL mode(s), i.e. "STRICT_TRANS_TABLES,ONLY_FULL_GROUP_BY".
	 *   This argument should be omitted when using the "get" action. 
	 * @param string $minVersion Make the given action only apply if MySQL version is at least $minVersion, i.e. "5.7.0".
	 * @param \PDO PDO connection to use or omit for current (default=null) 3.0.175+
	 * @return string|bool Returns string in "get" action, boolean false if required version not present, or true otherwise.
	 * @throws WireException If given an invalid $action
	 * 
	 */
	public function sqlMode($action = 'get', $mode = '', $minVersion = '', $pdo = null) {

		$result = true;
		$modes = array();
		
		if($pdo === null) {
			$pdo = $this->pdoLast();
		} else {
			$this->pdoLast = $pdo;
		}
		
		if(empty($action)) $action = 'get';
		
		if($action !== 'get' && $minVersion) {
			$serverVersion = $this->getAttribute(\PDO::ATTR_SERVER_VERSION);
			if(version_compare($serverVersion, $minVersion, '<')) return false;
		}
	
		if($mode) {
			foreach(explode(',', $mode) as $m) {
				$modes[] = $this->escapeStr(strtoupper($this->wire()->sanitizer->fieldName($m)));
			}
		}
		
		switch($action) {
			case 'get':
				$query = $pdo->query("SELECT @@sql_mode");
				$result = $query->fetchColumn();
				$query->closeCursor();
				break;
			case 'set':
				$modes = implode(',', $modes);
				$result = $modes;
				$pdo->exec("SET sql_mode='$modes'");
				break;
			case 'add':
				foreach($modes as $m) {
					$pdo->exec("SET sql_mode=(SELECT CONCAT(@@sql_mode,',$m'))");
				}
				break;
			case 'remove':
				foreach($modes as $m) {
					$pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'$m',''))");
				}
				break;
			default:
				throw new WireException("Unknown action '$action'");
		}
		
		return $result;
	}

}

