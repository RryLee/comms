<?php

namespace Concerto\Comms;

use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionException;

/**
 *  @event error {
 *      Triggered when a connection error occurs.
 *
 *      @param  \RuntimeException    $error
 *  }
 *  @event join {
 *      Triggered when a client connects.
 *  }
 *  @event part {
 *      Triggered when a client disconnects.
 *  }
 *  @event message {
 *      Triggered when a client sends a message.
 *
 *      @param  mixed       $data
 *          The data recieved from the client.
 *      @param  Transport   $transport
 *          The transport object used to send the data.
 *  }
 */
class Server implements ServerInterface
{
    use EventEmitterTrait;

    /**
     *  Address of the socket.
     */
    protected $address;

    protected $master;
    protected $client;
    protected $loop;

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
        if ($this->hasClient() === false)  {
            return;
        }

        $this->client->close();
        $this->shutdown();
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function hasClient()
    {
        return ($this->client instanceof Connection);
    }

    public function handleConnection($socket)
    {
        stream_set_blocking($socket, 0);
        stream_set_read_buffer($socket, 0);
        stream_set_write_buffer($socket, 0);

        $this->client = new Connection($socket, $this->loop);

        $this->client->on('close', function($data) {
            $this->emit('part');
            $this->emit('parted');
        });

        $this->client->on('message', function($data) {
            $transport = Transport::unpack($data);

            $this->emit('message', [$transport->getData(), $transport]);
        });

        $this->emit('join');
        $this->emit('joined');
    }

    public function listen()
    {
        if (isset($this->address) && $this->address->isLocalResource()) {
            @unlink($this->address->getPath());
        }

        $this->master = @stream_socket_server($this->address, $errno, $errstr);

        if (false === $this->master) {
            throw new ConnectionException(
                sprintf('Could not bind to %s: %s', $this->address, $errno),
                $errno
            );
        }

        stream_set_blocking($this->master, 0);

        $this->loop->addReadStream($this->master, function ($master) {
            $newSocket = stream_socket_accept($master);

            if (false === $newSocket) {
                $this->emit('error', [new \RuntimeException('Error accepting new connection')]);

                return;
            }

            $this->handleConnection($newSocket);
        });
    }

    public function send($message)
    {
        if ($this->hasClient() === false)  {
            return false;
        }

        $data = Transport::pack($message);

        return $this->client->write($data);
    }

    public function shutdown()
    {
        $this->loop->removeStream($this->master);
        $this->removeAllListeners();

        if (is_resource($this->master)) {
            fclose($this->master);
        }

        if (isset($this->address) && $this->address->isLocalResource()) {
            @unlink($this->address->getPath());
        }
    }
}
