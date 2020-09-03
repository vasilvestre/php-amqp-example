<?php

$url = parse_url(getenv('CLOUDAMQP_URL'));
$vhost = substr($url['path'], 1);

$credentials = [
    'host' => $url['host'],
    'vhost' => $vhost,
    'login' => $url['user'],
    'password' => $url['pass'],
    'port' => 5672
];

if ($url['scheme'] === "amqps") {
    $credentials = array_merge($credentials, array(
        'port' => 5671,
        'cacert' => '/etc/ssl/certs/ca-certificates.crt',
    ));
}

$conn = new AMQPConnection($credentials);
$conn->connect();

$ch = new AMQPChannel($conn);

$ex = new AMQPExchange($ch);
$exchange = 'amq.fanout';
$ex->setName($exchange);
$ex->setType(AMQP_EX_TYPE_DIRECT);
$ex->setFlags(AMQP_DURABLE);
$ex->declareExchange();

$q = new AMQPQueue($ch);
$q->setName('basic_get_queue');
$q->setFlags(AMQP_DURABLE);
$q->declareQueue();

$ex->bind($exchange);

$ex->publish('message', 'routing.key', AMQP_NOPARAM, ['content_type' => 'text/plain', 'delivery_mode' => 2]);

$conn->disconnect();
