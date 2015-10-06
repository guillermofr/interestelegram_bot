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

		if ($msg->isPrivate()) return $this->_welcome($msg);

		$ship = $this->CI->Ships->get_ship_by_chat_id( $msg->chatId() );
		
		if ($msg->isCommand()) $this->_processAction( $ship, $msg );
		elseif ($msg->isReply()) $this->_processReply( $ship, $msg );
		elseif ($msg->isJoin()) $this->CI->commander->joinShip( $ship, $msg );
		elseif ($msg->isLeave()) $this->CI->commander->leaveShip( $ship, $msg );
		elseif ($msg->isTitleChange()) $this->_processTitleChange( $ship, $msg );
		else {

		}

	}

	private function _welcome(& $msg) {

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "
			Bienvenido a Interestelegram @".$msg->fromUsername().", tu aventura espacial!\n
			Para jugar debes configurar un username en tu cuenta de Telegram en Ajustes. Hecho esto estarás preparado para empezar.\n
			Crea un grupo de telegram con uno o más amigos.\n 
			"));
			
		//foto de invitar amigos
		$this->CI->telegram->sendPhoto(array('chat_id' => $msg->chatId(), 'photo' => $this->CI->config->item('img_help__crearGrupo')));		

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "
			Una vez creado, en el perfil del bot encontrarás como añadirlo a tu grupo.\n
			"));
		
		//foto de invitar bot
		$this->CI->telegram->sendPhoto(array('chat_id' => $msg->chatId(), 'photo' => $this->CI->config->item('img_help__invitarBot')));

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "
			Él os guiará en vuestro grupo y lo transformará en una nave espacial lista para jugar.\n
			Ten cuidado con tu tripulación, tus amigos podrán ser la ayuda que necesitas para conquistar la galaxia o la razón de la autodestrucción de la nave.\n
			Recuerda que necesitas su participación para que tu nave funcione!"));

		return true;
	}

	/* Only for group chats. */
	private function _processAction(& $ship, & $msg) {

		$fromId = $msg->fromId();
		$command = mb_strtolower($msg->command()); 
		$params = $msg->params();

		$this->CI->commander->{$command}( $msg, $ship );

		/*if (empty($ship)) {
			if ( $command == 'ayuda') $this->CI->commander->ayuda($msg);
			elseif ( $command == 'pilotar' ) $this->CI->commander->pilotar($msg);
		}
		else {
			if ( $command == 'ayuda') {
				$this->CI->commander->ayuda($msg, $ship);
			}
			elseif ( $command == 'pilotar' ) {
				$this->CI->commander->pilotar($msg, $ship);
			}
			elseif ( $command == 'test' && $ship->captain == $fromId ) {
				$this->CI->commander->test($msg, $ship);
			}
			elseif ( $command == 'escanear' && $ship->captain == $fromId ) {
				$this->CI->commander->vote_escanear($msg, $ship);
			}
			elseif ( $command == 'informe' ) {
				$this->CI->commander->informe($msg, $ship);
			}
			else {
				$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'El comando "'.$command.'" no está contemplado o no tienes permisos para usarlo.'));
			}
		}*/

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

		$last_action = $this->CI->Actions->get_last_action($ship->id);

		if ($last_action->message_id != $replyMessageId) {

			// hide keyboard
			$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
			$content = array(
				'chat_id' => $chatId, 
				'reply_markup' => $keyboard, 
				'reply_to_message_id' => $messageId, 
				'text' => '@'.$username.' el mensaje al que respondes ha caducado'
			);
			return $this->CI->telegram->sendMessage($content);

		}

		if ( ! $this->CI->Votes->create_vote( array('action_id' => $last_action->id, 'user_id' => $user_id, 'vote' => $response_value) ) ) {

			// hide keyboard
			$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
			$content = array(
				'chat_id' => $chatId, 
				'reply_markup' => $keyboard, 
				'reply_to_message_id' => $messageId, 
				'text' => '@'.$username.' tu voto no se ha tenido en cuenta. Ya has votado o ha fallado.'
			);
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
			$output = array(
				'chat_id' => $chatId,
				'text' => "Votación ".($response_value + $last_action->positives)." de ".$last_action->required." hecha por @{$username} ({$response})"
			);
			$o = $this->CI->telegram->sendMessage($output);
			// hide keyboard
			$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
			$content = array('chat_id' => $chatId, 'reply_markup' => $keyboard, 'reply_to_message_id' => $messageId, 'text' => 'tu voto se ha registrado');
			$o = $this->CI->telegram->sendMessage($content);
		}

		//apply action if success
		if ($apply_action) {
			$this->CI->commander->{"{$last_action->command}"}( $msg, $ship, $last_action->params );
		}

	}


	private function _processTitleChange(& $ship, & $msg) {

		$this->CI->Ships->update_ship(array('name' => $msg->chatTitle()), $ship->id);

	}

}