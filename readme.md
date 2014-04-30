# Comms Component

Library for Inter-Process Communications using Unix domain sockets.

[![Build Status](https://secure.travis-ci.org/concertophp/comms.png?branch=master)](http://travis-ci.org/concertophp/comms)


## Install

The recommended way to install Comms is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "concerto/comms": "0.*"
    }
}
```


## Usage
### Server

```php
use Concerto\Comms\Server;
use React\EventLoop\Factory;

$loop = Factory::create();

$comms = new Server($loop);

$comms->on('joined', function() {
	echo "Client joined.\n";
});

$comms->on('parted', function() {
	echo "Client exited.\n";
});

$comms->on('message', function($message) use ($comms) {
	echo "Client said: $message\n";

	$comms->send('...');
});

$comms->listen(__DIR__ . '/test.ipc');
$loop->run();```

### Client

```php
use Concerto\Comms\Client;
use React\EventLoop\Factory;

$loop = Factory::create();

$comms = new Client($loop);

$comms->on('joined', function() {
	echo "Server joined.\n";
});

$comms->on('parted', function() {
	echo "Server exited.\n";
	exit;
});

$comms->on('message', function($data) {
	echo "Server said: $data\n";
});

$comms->send('...');

$comms->listen(__DIR__ . '/test.ipc');
$loop->run();
```