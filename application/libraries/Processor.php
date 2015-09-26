<?php

class Processor {

	private $CI = null;

	public function __construct() {
		$this->CI =& get_instance();

		$this->CI->load->model('Ships');
		$this->CI->config->load('bot');
		$params = array(
				$this->CI->config->item('botToken')
			);

		$this->CI->load->library('Telegram', $params);
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

	}

	private function _welcome(& $msg) {
		$output = array('chat_id' => $msg->chatId(), 'text' => "Bienvenido a Interestelegram @".$msg->fromUsername().", tu aventura espacial!\n\nPara jugar debes configurar un username en tu cuenta de Telegram en Ajustes. Después, crea un grupo e invita a este bot.\n\nUtiliza el comando '/pilotar.'' para iniciar la partida convirtiendote en el piloto de la nave.\n\nTu nave necesita tripulación, así que invita a toda la gente que quieras al grupo. Recuerda que necesitas su participación para que tu nave funcione!");
		return $this->CI->telegram->sendMessage($output);
	}

	/* Only for group chats. */
	private function _processAction(& $ship, & $msg) {

		$fromId = $msg->fromId();
		$command = mb_strtolower($msg->command()); 
		$params = $msg->params();

		if (empty($ship)) {
			if ( $command == 'ayuda') $this->_ayuda($msg);
			elseif ( $command == 'pilotar' ) $this->_pilotar($msg);
		}
		else {
			if ( $command == 'ayuda') $this->_ayuda($msg, $ship);
			elseif ( $command == 'pilotar' ) {
				$this->_pilotar($msg, $ship);
			}
			else {
				$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'El comando "'.$command.'" no está contemplado.'));
			}
		}

	}

	/**
	 * Acción texto de ayuda. Distingue entre ayuda a canal nuevo y canal que ya es nave.
	 */
	private function _ayuda(& $msg, $already_ship=false) {
		$chat_id = $msg->chatId();

		if ($already_ship) {
			$content = array('chat_id' => $chat_id, 'text' => "Capitán, no hay mucho más que hacer por ahora. ¿Por qué no observamos juntos el espacio frente a nosotros? Acaricieme el ratón capitán...");
		} else {
			$content = array('chat_id' => $chat_id, 'text' => "Bienvenido a Interestelegram, tu aventura espacial!\n\nPara jugar debes configurar un username en tu cuenta de Telegram en Ajustes. Después, crea un grupo e invita a este bot.\n\nUtiliza el comando /pilotar para iniciar la partida convirtiendote en el piloto de la nave.\n\nTu nave necesita tripulación, así que invita a toda la gente que quieras al grupo. Recuerda que necesitas su participación para que tu nave funcione!");
		}
		
		$output = $this->CI->telegram->sendMessage($content);
	}

	/**
	 * Acción pilotar. Crea la nave en base de datos y asigna al capitán. Detecta si el capitán ya se ha fijado.
	 */
	private function _pilotar(& $msg, $ship=null) {
		$chat_id = $msg->chatId();
		$chat_title = ( !empty($msg->chatTitle()) ) ? $msg->chatTitle() : ('ship-'.microtime());
		$username = $msg->fromUsername();
		$user_id = $msg->fromId();

		if (empty($ship)) {
			if ($username != null) {
				$content = array('chat_id' => $chat_id, 'text' => 'Ascendiendo a @'.$username.' a piloto de la nave');
				$output = $this->CI->telegram->sendMessage($content);
				$ship = $this->CI->Ships->create_ship(array('chat_id' => $chat_id, 'captain' => $user_id, 'name' => $chat_title));
				$content = array('chat_id' => $chat_id, 'text' => 'La "'.$chat_title.'" ha despegado con una tripulación de un solo miembro, el capitán '.$username.".\n\nBuena suerte!");
				$output = $this->CI->telegram->sendMessage($content);
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'Para ser piloto necesitas configurar un username en Ajustes');						
				$output = $this->CI->telegram->sendMessage($content);
			}
		} else {
			if ($user_id != $ship->captain) {
				$content = array('chat_id' => $chat_id, 'text' => 'La "'.$chat_title.'" ya tiene piloto, el capitán '.$username);						
				$output = $this->CI->telegram->sendMessage($content);	
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'Capitán, ya pilotáis la "'.$chat_title.'". Alguna otra orden?');						
				$output = $this->CI->telegram->sendMessage($content);	
			}
			
		}
	}
}