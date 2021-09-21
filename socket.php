<?php

set_time_limit(0);
require  'vendor/autoload.php';
use React\Socket\ConnectionInterface;

class ConnectionsPool 
{
	private $connections;

	public function __construct()
	{
		$this->connections = new SplObjectStorage();
	}

	public function add(ConnectionInterface $connection)
	{
		$this->initEvents($connection);
		$this->setConnectionData($connection);
	}

	private function initEvents(ConnectionInterface $connection)
	{
		$connection->on('data', function ($data) use ($connection) {
		$connectionData = $this->getConnectionData($connection);
		
		if(empty($connectionData)) {
			$this->addNewMember($connection);
		}
		
		echo trim($data). PHP_EOL;
		
		if($data == 'dissconnect')
		{
			$this->connections->offsetUnset($connection);
		}
		else
			$this->sendAll("$data", $connection);
        });
		
        $connection->on('close', function() use ($connection){
            $this->connections->offsetUnset($connection);
        });
    }

	private function addNewMember($connection)
	{
		$this->setConnectionData($connection);
	}

	private function setConnectionData(ConnectionInterface $connection)
	{
		$this->connections->offsetSet($connection);
	}

	private function getConnectionData(ConnectionInterface $connection)
	{
		return $this->connections->offsetGet($connection);
	}

	private function sendAll($data, ConnectionInterface $except) {
		foreach ($this->connections as $conn) {
			try
			{
				$conn->write($data);
			}
			catch( Exception $e )
			{
				
			}
		}
	}
}

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server('0.0.0.0:8080', $loop);
$pool = new ConnectionsPool();

$socket->on('connection', function(ConnectionInterface $connection) use ($pool){
	$pool->add($connection);
});

$socket->on('error', function (Exception $e) {
	echo 'error: ' . $e->getMessage() . PHP_EOL;
});

echo "Listening on {$socket->getAddress()}\n";

$loop->run();