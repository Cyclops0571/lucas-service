<?php

namespace Acme;

use GeoIp2\Database\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class LocationService extends Command
{
    public function configure()
    {
        $this->setName('getLocation')
            ->setDescription('Returns the location of the given ip');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        error_reporting(E_ALL);

        /* Allow the script to hang around waiting for connections. */
        set_time_limit(0);

        /* Turn on implicit output flushing so we see what we're getting
         * as it comes in. */
        ob_implicit_flush();

        $server = stream_socket_server("tcp://127.0.0.1:1337", $errno, $errorMessage); //also can be a local socket

        if ($server === false) {
            throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
        }

        $reader = new Reader(dirname(__FILE__) . '/GeoLite2-City.mmdb');

        while (true) {
            $client = @stream_socket_accept($server);
            if ($client) {
                $ip = stream_get_contents($client, -1, -1);
                if ($ip) {
                    $record = $reader->city(trim($ip));
                    $stream = fopen('data://text/plain;base64,' . base64_encode(serialize($record)), 'r');
                    if ($client) {
                        stream_copy_to_stream($stream, $client);
                        fclose($client);
                    }
                }
            }
        }
    }
}
