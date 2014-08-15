<?php

namespace Concerto\Comms;

use React\Stream\Stream;

class Connection extends Stream
{
    public $separator = '#';
    protected $message = '';

    public function handleData($stream)
    {
        $data = stream_socket_recvfrom($stream, $this->bufferSize);

        if ('' !== $data && false !== $data) {
            $this->handleMessage($data);
        }

        if ('' === $data || false === $data || !is_resource($stream) || feof($stream)) {
            $this->end();
        }
    }

    public function handleMessage($data)
    {
        if (strstr($data, $this->separator)) {
            $split = explode($this->separator, $data);
            $message = base64_decode($this->message . $split[0]);

            $this->emit('message', [$message]);
            $this->message = $split[1];
        } else {
            $this->message .= $data;
        }
    }

    public function handleClose()
    {
        if (is_resource($this->stream)) {
            // http://chat.stackoverflow.com/transcript/message/7727858#7727858
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
            stream_set_blocking($this->stream, false);
            fclose($this->stream);
        }
    }

    public function write($data)
    {
        parent::write(base64_encode($data) . $this->separator);
    }
}
