<?php

require_once __DIR__ . '../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;


class RabbitConsumer
{
    protected $connection;
    protected $debug;
    protected $queueName;
    protected $exchange;

    protected function consumme($name,$callbackMethod)
    {
        $connection = new AMQPStreamConnection('finch-01.rmq.cloudamqp.com', 5672, 'mznkveic', 'u04i5Icct678dRcwA7EJMl0YqudyNjS8','mznkveic');
        $channel = $connection->channel();
        $channel->queue_declare($name, false, false, false, false);
        $channel->basic_consume($name, '', false, true, false, false, $callbackMethod);
          
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}