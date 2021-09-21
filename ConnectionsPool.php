<?php

use React\Socket\ConnectionInterface;

class ConnectionsPool 
{
    /** @var SplObjectStorage  */
    private $connections;

    public function __construct()
    {
        $this->connections = new SplObjectStorage();
    }

    public function add(ConnectionInterface $connection)
    {
        $this->initEvents($connection);
        $this->setConnectionData($connection, []);
    }

    /**
     * @param ConnectionInterface $connection
     */
    private function initEvents(ConnectionInterface $connection)
    {
        // On receiving the data we loop through other connections
        // from the pool and write this data to them
        $connection->on('data', function ($data) use ($connection) {
            $connectionData = $this->getConnectionData($connection);

            // It is the first data received, so we consider it as
            // a user's name.
            if(empty($connectionData)) {
                $this->addNewMember($connection);
            }
            $this->sendAll("$data", $connection);
        });

        // When connection closes detach it from the pool
        $connection->on('close', function() use ($connection){
            $this->connections->offsetUnset($connection);
            //$this->sendAll("User $name leaves the chat\n", $connection);
        });
    }

    private function addNewMember($name, $connection)
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

    /**
     * Send data to all connections from the pool except
     * the specified one.
     *
     * @param mixed $data
     * @param ConnectionInterface $except
     */
    private function sendAll($data, ConnectionInterface $except) {
        foreach ($this->connections as $conn) {
            if ($conn != $except) $conn->write($data);
        }
    }
}