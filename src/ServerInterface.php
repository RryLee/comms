<?php

namespace Concerto\Comms;

use Evenement\EventEmitterTrait;
use Concerto\TextExpressions\RegularExpression as RegExp;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionException;
use React\Socket\RuntimeException;

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