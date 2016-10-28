<?php

/**
 * Comunications library
 * - This library will be used to implement the comunications layer with socket.io server using rabbitmq
 * @package Interestelegram Canvas
 * @author gustavo
 * @version 1.0
 * @dependencies
 * - php-amqlib
 * - RabbitmqConnector
 */
class Communications {
    
    private $CI = null;
    private $queue = 'inter_comunication';
    private $autoclose = false;
    private $format = 'json';
    private $communications_enabled = false;

    public function __construct(){
        $this->CI =& get_instance();
        $this->CI->config->load('communications', TRUE);
        $this->config = $this->CI->config->item('communications');
        $this->communications_enabled = $this->config['communications_enabled'];
        if ($this->communications_enabled) $this->CI->load->library('RabbitmqConnector');
    }

    private function format($data, $format = null){
        $formatter = $this->format;
        if ( $format && !empty($format) ) $formatter = $format;
        $formatter = '_formatter_'.$formatter;
        if ( method_exists($this, $formatter) ) return call_user_func_array(array($this, $formatter), array($data));
        else return $data;
    }

    private function _formatter_json($data){ return json_encode($data); }

    public function sendMessage($message){
        $formatted_data = $this->format($message);
        $this->_sendMessage($formatted_data);
    }

    public function sendPersonalMessage($user_id, $message){
        $this->sendMessage(array( 'to' => $user_id, 'data' => $message));
    }

    public function sendChannelMessage($channel, $message){
        // not implemented
    }

    public function close(){
        $this->_close();
    }

    private function _sendMessage($message){
        if ($this->communications_enabled){
            $this->CI->rabbitmqconnector->queue_message( 'inter_comunication', $message );
            if ( $this->autoclose ) $this->rabbitmqconnector->close();
        }
    }

    private function _close(){
        if ($this->communications_enabled) 
            $this->CI->rabbitmqconnector->close();
    }

}