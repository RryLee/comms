<?php

namespace Concerto\Comms;

use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;

/**
 *  @event join {
 *      Triggered when the client connects.
 *  }
 *  @event part {
 *      Triggered when the server disconnects.
 *  }
 *  @event message {
 *      Triggered when a server sends a message.
 *
 *      @param  mixed       $data
 *          The data recieved from the client.
 *      @param  Transport   $transport
 *          The transport object used to send the data.
 *  }
 */
class Client implements ClientInterface
{
    use EventEmitterTrait;

    /**
     *  Address of the socket.
     */
    protected $address;

    protected $loop;
    protected $server;

    public function __construct(LoopInterface $loop, $address)
    {
        if (false === ($address instanceof AddressInterface)) {
            $address = new Address($address);
        }

        $this->address = $address;
        $this->loop = $loop;
    }

    public function close()
    {
        if ($this->hasServer() === false) return;

        $this->loop->nextTick(function() {
            $this->server->close();
        });
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function hasServer()
    {
        return ($this->server instanceof Connection);
    }

    public function connect()
    {
        $client = stream_socket_client($this->address);
        stream_set_read_buffer($client, 0);
        stream_set_write_buffer($client, 0);

        $this->server = new Connection($client, $this->loop);

        $this->server->on('close', function() {
            $this->server = null;
            $this->emit('part');
            $this->emit('parted');
        });

        $this->server->on('data', function($data) {
            $transport = Transport::unpack($data);

            $this->emit('message', [$transport->getData(), $transport]);
        });

        $this->emit('join');
        $this->emit('joined');
    }

    public function send($message)
    {
        if ($this->hasServer() === false) return false;

        $data = Transport::pack($message);

        return $this->server->write("{$data}\n");
    }
}