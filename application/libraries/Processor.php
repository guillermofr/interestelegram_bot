<?php

class Processor {

	private $CI = null;
	private $botToken = null;
	private $botUsername = null;

	public function __construct() {
		$this->CI =& get_instance();

		$this->CI->load->model('Ships');
		$this->CI->load->model('Users');
		$this->CI->load->model('Crew');
		$this->CI->load->model('Actions');
		$this->CI->load->model('Votes');

		$this->CI->config->load('bot');
		$this->CI->config->load('images');
		$this->botToken = $this->CI->config->item('botToken');
		$this->botUsername = $this->CI->config->item('botUsername');
		$this->debug = $this->CI->config->item('debug');

		$params = array( $this->botToken );

		$this->CI->load->library('Telegram', $params);
		$this->CI->load->library('Commander');
	}

	/**
	 * Método principal que matchea el comando recibido con una acción
	 */
	public function process($msg=array()) {

		$this->CI->load->library('Message', $msg);
		$msg =& $this->CI->message;

		/* Prevent users without username */
		if ($msg->isEmptyFromUsername()) return $this->_empty_username_warning($msg);

		/*if ($msg->isPrivate()) {
			if ($msg->command() == 'olvidar')	{
				return $this->_olvidar($msg);
			} else return $this->_welcome($msg);
		}*/

		$ship = $this->CI->Ships->get_ship_by_chat_id( $msg->chatId() );
		
		if ($msg->isCommand()) $this->_processAction( $ship, $msg );
		elseif ($msg->isReply()) $this->_processReply( $ship, $msg );
		elseif ($msg->isJoin()) $this->CI->commander->joinShip( $ship, $msg );
		elseif ($msg->isLeave()) $this->CI->commander->leaveShip( $ship, $msg );
		elseif ($msg->isTitleChange()) $this->_processTitleChange( $ship, $msg );
		else {
			if ($msg->isPrivate()) {
				
					$last_action = $this->CI->Actions->get_last_action($ship->id);
					if ($last_action->closedAt == null){
						$msg->replyce($last_action->message_id);
						$this->_processReply( $ship, $msg );
					}
				

			}
		}

	}


	private function _empty_username_warning(& $msg) {

		return $this->CI->telegram->sendMessage(array(
			'chat_id' => $msg->fromId(),
			'text' => 'To play '.$this->botUsername.' you need to set up your username at telegram'
		));

	}


	private function _welcome(& $msg) {

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "
			Welcome to Interestelegram @".$msg->fromUsername().", your space adventure!\n
			Talk directly to bot or create a group if you want to play with friends at same ship.\n 
			"));
			
		//foto de invitar amigos
		$img = $this->CI->telegram->prepareImage(APPPATH.'../imgs/help1.jpg');
		$this->CI->telegram->sendPhoto(array('chat_id' => $msg->chatId(), 'photo' => $img));		

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "
			With your group created, add the bot to your group, and some friends if you want.\n
			"));
		
		//foto de invitar bot
		$img = $this->CI->telegram->prepareImage(APPPATH.'../imgs/help2.jpg');
		$this->CI->telegram->sendPhoto(array('chat_id' => $msg->chatId(), 'photo' => $img));

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "
			The bot will transform your group into a spaceship ready to combat.\n
			You dont need to invite friends, be a lonely pirate or a captain with no crew.\n
			The teamplay is not allways funny xD, but you can try!"));

		return true;
	}

	private function _olvidar($msg) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();

		$this->CI->Users->delete($user_id);

		$output = array(
			'chat_id' => $chat_id,
			'text' => 'We just lost you, like tears in the rain...'
		);
		return $this->CI->telegram->sendMessage($output);
	}

	/* Only for group chats. */
	private function _processAction(& $ship, & $msg) {

		$fromId = $msg->fromId();
		$command = mb_strtolower($msg->command()); 
		$params = $msg->params();

		$this->CI->commander->{$command}( $msg, $ship, $params );

	}


	private function _processReply(& $ship, & $msg){

		$chatId = $msg->chatId();
		$user_id = $msg->fromId();
		$username = $msg->fromUsername();
		$response = $msg->text();
		$messageId = $msg->messageId();
		$replyMessageId = $msg->replyId();
		$apply_action = false;

		$response_value = ( $response == 'SI' ? 1 : 0 );

		if (!$ship) return;

		$last_action = $this->CI->Actions->get_last_action($ship->id);

		if ($last_action->message_id != $replyMessageId) {

			// hide keyboard
			$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
			$content = array(
				'chat_id' => $chatId, 
				'reply_to_message_id' => $messageId, 
				'text' => '@'.$username.' your response is too late!'
			);

			if ($ship->captain != $user_id) { // No ocultes teclados del capitán
				$content['reply_markup'] = $keyboard;
			}

			return $this->CI->telegram->sendMessage($content);

		}

		if ( ! $this->CI->Votes->create_vote( array('action_id' => $last_action->id, 'user_id' => $user_id, 'vote' => $response_value), $msg ) ) {

			// hide keyboard
			$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
			$content = array(
				'chat_id' => $chatId, 
				'reply_to_message_id' => $messageId, 
				'text' => '@'.$username.' your vote has failed.'
			);

			if ($ship->captain != $user_id) { // No ocultes teclados del capitán
				$content['reply_markup'] = $keyboard;
			}

			return $this->CI->telegram->sendMessage($content);

		}

		$update = array();
		if ($response_value == 1) $update['positives'] = $last_action->positives + 1;
		else $update['negatives'] = $last_action->negatives + 1;
		if ($response_value + $last_action->positives >= $last_action->required ) {
			$update['fail'] = 0;
			$update['closedAt'] = Date('Y-m-d H:i:s', time());
			$apply_action = true;
		}

		$this->CI->Actions->update_action($update, $last_action->id);

		//si no hay required no se muestra el conteo de datos
		if ($last_action->required) {
			$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
			
			if (($response_value + $last_action->positives) == $last_action->required) {
				$text = "Vote succeeded";
			} else {
				$text = "Vote ".($response_value + $last_action->positives)." of ".$last_action->required." done by @{$username} ({$response})";
			}


			$output = array(
				'chat_id' => $chatId,
				'reply_markup' => $keyboard, 
				'text' => $text
			);
			if (($response_value + $last_action->positives) == $last_action->required) {
				$output['reply_markup'] = $this->CI->telegram->buildKeyBoardHide($selective=FALSE);
			}

			if (($response_value + $last_action->positives) <= $last_action->required) {
				$o = $this->CI->telegram->sendMessage($output);
			}
			
		}

		//apply action if success
		if ($apply_action) {
			$this->CI->commander->{"{$last_action->command}"}( $msg, $ship, $last_action->params, $last_action );
		}

	}


	private function _processTitleChange(& $ship, & $msg) {

		$this->CI->Ships->update_ship(array('name' => $msg->chatTitle()), $ship->id);

	}

}