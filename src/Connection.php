<?php

	namespace Concerto\Comms;
	use React\Stream\Stream;
	use React\Socket\ConnectionException;
	use React\Socket\ConnectionInterface;
	use React\Socket\RuntimeException;

	class Connection extends Stream {
		public function handleData($stream) {
			$data = fgets($stream, $this->bufferSize);

			if ('' !== $data && false !== $data) {
				$this->emit('data', array($data, $this));
			}

			if ('' === $data || false === $data || feof($stream)) {
				$this->end();
			}
		}

		public function handleClose() {
			if (is_resource($this->stream)) {
				// http://chat.stackoverflow.com/transcript/message/7727858#7727858
				stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
				stream_set_blocking($this->stream, false);
				fclose($this->stream);
			}
		}
	}