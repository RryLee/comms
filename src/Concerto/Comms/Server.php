<?php

	namespace Concerto\Comms;
	use Evenement\EventEmitter;
	use React\EventLoop\LoopInterface;
	use React\Socket\Connection;
	use React\Socket\ConnectionException;
	use React\Socket\RuntimeException;

	class Server extends EventEmitter {
		protected $client;
		protected $loop;

		public function __construct(LoopInterface $loop) {
			$this->loop = $loop;
		}

		public function close() {
			if ($this->hasClient() === false) return;

			$this->loop->nextTick(function() {
				$this->client->close();
			});
		}

		public function hasClient() {
			return ($this->client instanceof Connection);
		}

		public function handleConnection($socket) {
			stream_set_blocking($socket, 0);

			$this->client = new Connection($socket, $this->loop);

			$this->client->on('close', function($data) {
				$this->client = null;
				$this->emit('parted');
			});

			$this->client->on('data', function($data) {
				$transport = Transport::unpack($data);

				$this->emit('message', [$transport->getData(), $transport]);
			});

			$this->emit('joined');
		}

		public function listen($filename) {
			if (file_exists($filename)) {
				unlink($filename);
			}

			$this->master = @stream_socket_server('unix://' . $filename, $errno, $errstr);

			if (false === $this->master) {
				$message = "Could not bind to $filename: $errstr";
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

		public function send($message) {
			if ($this->hasClient() === false) return false;

			$data = Transport::pack($message);

			return $this->client->write("{$data}\n");
		}

		public function shutdown() {
			$this->loop->removeStream($this->master);
			fclose($this->master);
			$this->removeAllListeners();
		}
	}