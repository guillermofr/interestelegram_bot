<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqConnector {

    private $error      = array();
    private $HOST       = '';
    private $PORT       = 0;
    private $USER       = '';
    private $PASS       = '';
    private $VHOST      = '';
    private $AMQP_DEBUG = true;
    public $CONNECTION  = null;
    public $MESSAGE     = null;
    public $CHANNELS    = array();

    function __construct() {
        $this->CI =& get_instance();

        $this->CI->config->load('rabbitmq', TRUE);
        $this->HOST = $this->CI->config->item('rabbit_host', 'rabbitmq');
        $this->PORT = $this->CI->config->item('rabbit_port', 'rabbitmq');
        $this->USER = $this->CI->config->item('rabbit_username', 'rabbitmq');
        $this->PASS = $this->CI->config->item('rabbit_password', 'rabbitmq');
        $this->VHOST = $this->CI->config->item('rabbit_vhost', 'rabbitmq');

        $this->MESSAGE = new AMQPMessage('', array('content_type' => 'text/plain', 'delivery_mode' => 2));

        // Set up connection
        $this->CONNECTION = new AMQPConnection($this->HOST, $this->PORT, $this->USER, $this->PASS, $this->VHOST);

        /*$this->CONNECTION->set_connection_block_handler(function($reason){
            $title = '[PUSHSERVER] Las conexiones con RabbitMQ están bloqueadas';
            $message = "RabbitMQ ha indicado que las conexiones están bloquedas.<br/><br>Esto puede deberse a que el servidor ".((trim($reason)=="low on memory")?"ha alcanzado el máximo de memoria RAM permitido.":"no tiene espacio suficiente en disco.")."<br/><br>Utilice el servicio web de gestión de RabbitMQ para consultar el estado actual de las alertas. Más información aqui: https://www.rabbitmq.com/memory.html";

            $CI =& get_instance();
            try {
                $CI->config->load('email_notifications', TRUE);
            
                $from_address   = $CI->config->item('notification_from_address', 'email_notifications');
                $from_name      = $CI->config->item('notification_from_name', 'email_notifications');
                $to             = $CI->config->item('notification_to', 'email_notifications');  
            } catch (Exception $e) {
                $from_address   = 'nahun@digio.es';
                $from_name      = 'Nahun';
                $to             = 'nahun@digio.es';
                $title          = $title.' - '.$_SERVER['SERVER_NAME'];
            }

            $CI->load->library('email');
            $CI->email->clear(TRUE);
            $CI->email->from($from_address, $from_name);
            $CI->email->to($to);
            $CI->email->subject($title);
            $CI->email->message($message);
            $CI->email->send(TRUE);
        });*/

    }

    function queue_message($queue, $message, $exchange='php_to_nodejs', $exchange_type='direct') {
        // Store channels to avoid reconecting every time
        if (!isset($this->CHANNELS[$queue])) {
            // Create the channel
            $channel = $this->CONNECTION->channel();

            /**
             * @param  queue_name   The name of the queue
             * @param  passive      Consuming type
             * @param  durable      Persistance for the messages
             * @param  exclusive    Queue accesible for more than one worker
             * @param  auto_delete  Don't delete the queue when the channel is closed
             */
            $channel->queue_declare($queue, false, true, false, false);

            /**
             * @param  exchange_name    The name of the queue
             * @param  exchange_type    The type of the exchange, direct by default
             * @param  passive          Consuming type
             * @param  durable          Persistance for the messages
             * @param  auto_delete      Don't delete the queue when the channel is closed
             */
            $channel->exchange_declare($exchange, $exchange_type, false, true, false);

            // Bind to this queue and exchange
            $channel->queue_bind($queue, $exchange);

            // Store the channel for later usage
            $this->CHANNELS[$queue] = $channel;
        } else {
            // Recover the channel
            $channel = $this->CHANNELS[$queue];
        }

        // delivery_mode = 2 -> perdurable
        $this->MESSAGE->setBody($message);

        $channel->basic_publish($this->MESSAGE, $exchange);
    }

    function queue_message_array($queue, $message, $exchange='php_to_nodejs', $exchange_type='direct') {
        // Store channels to avoid reconecting every time
        if (!isset($this->CHANNELS[$queue])) {
            // Create the channel
            $channel = $this->CONNECTION->channel();

            /**
             * @param  queue_name   The name of the queue
             * @param  passive      Consuming type
             * @param  durable      Persistance for the messages
             * @param  exclusive    Queue accesible for more than one worker
             * @param  auto_delete  Don't delete the queue when the channel is closed
             */
            $channel->queue_declare($queue, false, true, false, false);

            /**
             * @param  exchange_name    The name of the queue
             * @param  exchange_type    The type of the exchange, direct by default
             * @param  passive          Consuming type
             * @param  durable          Persistance for the messages
             * @param  auto_delete      Don't delete the queue when the channel is closed
             */
            $channel->exchange_declare($exchange, $exchange_type, false, true, false);

            // Bind to this queue and exchange
            $channel->queue_bind($queue, $exchange);

            // Store the channel for later usage
            $this->CHANNELS[$queue] = $channel;
        } else {
            // Recover the channel
            $channel = $this->CHANNELS[$queue];
        }

        // delivery_mode = 2 -> perdurable
        foreach ($message as $msg_data) {
            $this->MESSAGE->setBody($message);
            $channel->basic_publish($this->MESSAGE, $exchange);
        }
    }

    function queue_log($message_object) {
        $queue          = 'log_queue';
        $exchange       = $queue.'_exchange';
        $exchange_type  = 'direct';
        $routing_key    = 'log_work';

        // Store channels to avoid reconecting every time
        if (!isset($this->CHANNELS[$queue])) {
            // Create the channel
            $channel = $this->CONNECTION->channel();

            /**
             * @param  queue_name   The name of the queue
             * @param  passive      Consuming type
             * @param  durable      Persistance for the messages
             * @param  exclusive    Queue accesible for more than one worker
             * @param  auto_delete  Don't delete the queue when the channel is closed
             */
            $channel->queue_declare($queue, false, true, false, false);

            /**
             * @param  exchange_name    The name of the queue
             * @param  exchange_type    The type of the exchange, direct by default
             * @param  passive          Consuming type
             * @param  durable          Persistance for the messages
             * @param  auto_delete      Don't delete the queue when the channel is closed
             */
            $channel->exchange_declare($exchange, $exchange_type, false, true, false);

            // Bind to this queue and exchange
            $channel->queue_bind($queue, $exchange);

            // Store the channel for later usage
            $this->CHANNELS[$queue] = $channel;
        } else {
            // Recover the channel
            $channel = $this->CHANNELS[$queue];
        }

        // delivery_mode = 2 -> perdurable
        $this->MESSAGE->setBody(json_encode($message_object));
        $channel->basic_publish($this->MESSAGE, $exchange, $routing_key);
    }

    function close() {
        foreach ($this->CHANNELS as $channel) {
            $channel->close();
        }
        $this->CONNECTION->close();
    }

}