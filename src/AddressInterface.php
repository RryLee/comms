<?php

namespace Concerto\Comms;

use Concerto\Sockets\AddressInterface as BaseAddressInterface;

interface AddressInterface extends BaseAddressInterface
{
    /**
     *  Does the address represent a local resource?
     */
    public function isLocalResource();
}
