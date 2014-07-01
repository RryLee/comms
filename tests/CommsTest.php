<?php

namespace Concerto\Comms\Tests;

use Concerto\Comms\Client;
use Concerto\Comms\Server;
use React\EventLoop\Factory as EventLoopFactory;
use PHPUnit_Framework_TestCase as TestCase;

/**
 *  @covers Concerto\Comms\Server
 *  @covers Concerto\Comms\Client
 */
class CommsTest extends TestCase
{
    public function testConnection()
    {
        $address = 'unix://' . __DIR__ . '/test.ipc';
        $checksum = md5(uniqid(true));
        $loop = EventLoopFactory::create();
        $log = (object)[
            'clientJoined' =>   false,
            'clientParted' =>   false,
            'clientSaid' =>     1,
            'serverJoined' =>   false,
            'serverParted' =>   false,
            'serverSaid' =>     2
        ];

        // Create server:
        $server = new Server($loop, $address);

        $server->on('join', function() use ($log) {
            $log->clientJoined = true;
        });

        $server->on('part', function() use ($log) {
            $log->clientParted = true;
        });

        $server->on('message', function($message) use ($server, $log, $checksum) {
            $log->clientSaid = $message;

            $server->send($checksum);
        });

        $client = new Client($loop, $address);

        $client->on('join', function() use ($log) {
            $log->serverJoined = true;
        });

        $client->on('part', function() use ($log) {
            $log->serverParted = true;
        });

        $client->on('message', function($message) use ($client, $log) {
            $log->serverSaid = $message;
            $client->close();
        });

        $loop->nextTick(function() use ($client, $checksum) {
            $client->send($checksum);
        });

        // Timeout and hope?
        $loop->addTimer(0.5, function() use ($loop) {
            $loop->stop();
        });

        $server->listen();
        $client->connect();
        $loop->run();

        $this->assertTrue(file_exists(__DIR__ . '/test.ipc'));

        $client->close();
        $server->close();

        $this->assertFalse(file_exists(__DIR__ . '/test.ipc'));

        $this->assertTrue($log->clientJoined);
        $this->assertTrue($log->clientParted);

        $this->assertTrue($log->serverJoined);
        $this->assertTrue($log->serverParted);

        $this->assertEquals($log->clientSaid, $log->serverSaid);
    }
}