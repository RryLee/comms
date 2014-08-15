<?php

namespace Concerto\Comms;

interface CommsInterface
{
    /**
     * Close the connection to a server or stop listening for client connections.
     */
    public function close();

    /**
     * Get the internal socket address.
     *
     * @return   AddressInterface
     */
    public function getAddress();

    /**
     * Send a message from a client to the server or to all clients from the server.
     */
    public function send($message);
}
