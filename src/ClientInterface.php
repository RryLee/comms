<?php

namespace Concerto\Comms;

interface ClientInterface extends CommsInterface
{
    /**
     * Does the client have a server connection?
     *
     * @return  boolean
     */
    public function hasServer();

    /**
     * Attempt to connect to the server.
     */
    public function connect();
}
