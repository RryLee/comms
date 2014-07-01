<?php

namespace Concerto\Comms;

use Evenement\EventEmitter;
use Concerto\TextExpressions\RegularExpression as RegExp;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionException;
use React\Socket\RuntimeException;

class Server extends EventEmitter
{
    /**
     *  Address of the socket.
     */
    protected $address;

    /**
     *  Filename of the socket (if available).
     */
    protected $filename;

    protected $client;
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function close()
    {
        if ($this->hasClient() === false) return;

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

        $this->client->on('data', function($data) {
            $transport = Transport::unpack($data);

            $this->emit('message', [$transport->getData(), $transport]);
        });

        $this->emit('join');
        $this->emit('joined');
    }

    public function listen($address)
    {
        $this->setAddress($address);

        $this->master = @stream_socket_server($this->address, $errno, $errstr);

        if (false === $this->master) {
            $message = "Could not bind to {$this->address}: $errstr";
            throw new ConnectionException($message, $errno);
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

    protected function parseAddress($address)
    {
        $exp = new RegExp('^(?<address>(?<scheme>.+?)://(?<resource>.+))$');

        return $exp->execute($address);
    }

    public function setAddress($address)
    {
        $data = $this->parseAddress($address);
        $this->address = $this->filename = null;

        if (false === isset($data->scheme, $data->resource)) {
            throw new AddressException('Could not parse given address, expecting either a tcp:// or unix:// resource.');
        }

        else if ('unix' === $data->scheme) {
            $filename = substr($address, 7);
            $this->address = $address;

            // Relative to root:
            if (0 === strpos($filename, '/')) {
                $this->filename = $filename;
            }

            // Relative to current path:
            else {
                $this->filename = getcwd() . '/' . $filename;
            }
        }

        else if ('tcp' === $data->scheme) {
            $this->address = $address;
        }

        else {
            throw new AddressException('Invalid address schema, expecting either a tcp:// or unix:// resource.');
        }
    }

    public function send($message)
    {
        if ($this->hasClient() === false) return false;

        $data = Transport::pack($message);

        return $this->client->write("{$data}\n");
    }

    public function shutdown()
    {
        $this->loop->removeStream($this->master);
        fclose($this->master);
        $this->removeAllListeners();

        if (isset($this->filename) && file_exists($this->filename)) {
            unlink($this->filename);
        }
    }
}