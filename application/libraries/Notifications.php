<?php

/**
 * Notifications library
 * - This library will be used to implement the notifications layer using communications library
 * @package Interestelegram Canvas
 * @author gustavo
 * @version 1.0
 * @dependencies
 * - Communications
 */
class Notifications {
    
    private $CI = null;

    public function __construct(){
        $this->CI =& get_instance();
        $this->CI->load->library('Communications');
    }

    public function globalMessage( $message ){ $this->CI->communications->sendMessage( $message ); }
    public function personalMessage( $target_userid, $message ){ $this->CI->communications->sendPersonalMessage( $target_userid, $message ); }
    public function channelMessage( $channel, $message ){ $this->CI->communications->sendChannelMessage( $channel, $message ); }

    public function destroyedBy( $target_userid, $from = null ){
        $this->personalMessage( $target_userid, array( 'action' => 'destroyed', 'from' => $from ) );
    }
    public function underAttack( $target_userid, $from = null ){
        $this->personalMessage( $target_userid, array( 'action' => 'under_attack', 'from' => $from ) );
    }
    public function dodgedAttach( $target_userid, $from = null ){
        $this->personalMessage( $target_userid, array( 'action' => 'dodged_attack', 'from' => $from ) );
    }
    public function lockedAsTarget( $target_userid, $from = null ){
        $this->personalMessage( $target_userid, array( 'action' => 'locked_as_target', 'from' => $from ) );
    }
    public function announcement( $message ){
        $this->globalMessage( array( 'action' => 'announcement', 'message' => $message ) );
    }

}