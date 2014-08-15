<?php

namespace Concerto\Comms;

use Evenement\EventEmitterTrait;

interface ServerInterface extends CommsInterface
{
    /**
     * Does the server have an active client connection?
     *
     * @return  boolean
     */
    public function hasClient();

    /**
     * Start listening for client connections.
     */
    public function listen();

    /**
     * Remove all event listeners and close all streams.
     */
    public function shutdown();
}
