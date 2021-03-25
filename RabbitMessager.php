<?php

require_once __DIR__ . '../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMessager
{
    protected $connection;
    protected $debug;
    protected $queueName;
    protected $exchange;

    private static function send($message,$name)
    {
        $connection = new AMQPStreamConnection('finch-01.rmq.cloudamqp.com', 5672, 'mznkveic', 'u04i5Icct678dRcwA7EJMl0YqudyNjS8','mznkveic');
        $channel = $connection->channel();
        $channel->queue_declare($name, false, false, false, false);
        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, '', $name);
        $channel->close();
        $connection->close();
    }

    public static function downloadMessage($nameWithOwner){
        self::send($nameWithOwner,'downloadMessage');
    }

    public static function ckMessage($repositorioId){
        self::send($repositorioId,'ckMessage');
    }
}