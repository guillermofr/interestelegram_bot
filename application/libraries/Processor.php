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
		$chat_id = $msg['message']['chat']['id'];
		$ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);
		
		$txt = $msg['message']['text'];

		if (empty($ship)) {
			// Ship no existe, solo escuchamos ayuda y pilotar
			if (preg_match('/^\/ayuda.*/', $txt)) {
				$this->_ayuda($msg);
			} else if (preg_match('/^\/pilotar.*/', $txt)) {
				$this->_pilotar($msg, $ship);
			}
		} else {
			// Ship existe, escuchamos todo...
			if (preg_match('/^\/ayuda.*/', $txt)) {
				$this->_ayuda($msg, true);
			} else if (preg_match('/^\/pilotar.*/', $txt)) {
				$this->_pilotar($msg, $ship);
			}
		}
	}

	/**
	 * Acción texto de ayuda. Distingue entre ayuda a canal nuevo y canal que ya es nave.
	 */
	private function _ayuda($msg=array(), $already_ship=false) {
		$chat_id = $msg['message']['chat']['id'];

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
	private function _pilotar($msg=array(), $ship=null) {
		$chat_id = $msg['message']['chat']['id'];
		$chat_title = $msg['message']['chat']['title'];
		$username = isset($msg['message']['from']['username']) ? $msg['message']['from']['username'] : null;
		$user_id = isset($msg['message']['from']['id']) ? $msg['message']['from']['id'] : null;

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