<?php

class DBDescriptor {
	private $username, $password, $server, $port, $database, $db_handle;
	private $descriptor_name;

	const SERVER   = 0x01;
	const PORT     = 0x02;
	const USERNAME = 0x03;
	const PASSWORD = 0x04;
	const SOCKET   = 0x05;

	public function __construct(
			$descriptor_name = '',
			$username = '', $password = '',
			$server = '', $port = null, $socket = '',
			$database = '',
			$driver = 'mysqli') {

		if ($descriptor_name && $username && $server && (!$port || is_int($port)) && ctype_alpha($driver)) {
			$this->descriptor_name = $descriptor_name;

			$this->username = $username;
			$this->password = $password;
			$this->server   = $server;
			$this->port     = $port ? $port : null;
			$this->database = $database;
			$this->socket   = $socket;
			$this->driver   = $driver;
		}
		else
			throw new Exception('Error instantiating '. __CLASS__ .' object; invalid parameters');
	}

	public function getConnection() {
		return array(
				self::SERVER   => $this->server,
				self::PORT     => $this->port,
				self::USERNAME => $this->username,
				self::PASSWORD => $this->password,
				self::SOCKET   => $this->socket,
				);
	}

	public function getDatabase() {
		return $this->database;
	}

	public function getDriver() {
		return $this->driver;
	}

	public function getDescriptorName() {
		return $this->descriptor_name;
	}
}

