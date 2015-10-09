<?php

class Commander {

	private $CI = null;
	private $botToken = null;
	private $botUsername = null;

	private $captain_methods = array(
				'escanear', 
				'informe', 
				'test'
	);

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
	}


	public function __call($method, $arguments) {

		if (count($arguments)<2) return FALSE;

		$msg = $arguments[0];
		$ship = $arguments[1] ? $arguments[1] : null;

		if (!method_exists($this, "_{$method}")) {
			return $this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'El comando "'.$method.'" no está contemplado o no tienes permisos para usarlo.'));
		}

		if (in_array($method, $this->captain_methods) && ( is_null($ship) || $ship->captain != $msg->fromId() ) ){
			return $this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'El comando "'.$method.'" no está contemplado o no tienes permisos para usarlo.'));
		}
		else return call_user_func_array(array($this, "_{$method}"), $arguments);

	}


	/**
	 * _joinShip
	 * - Esta operación es respuesta a un evento en el grupo. Un nuevo usuario ha entrado y deberá formar parte de la tripulación
	 * - Una vez añadido a la tripulación, creado el usuario si no existe y aumentado el contador en la nave hay que responder
	 * - responder a la nave que hay un nuevo tripulante.
	 * - mencionar al nuevo tripulante y decirle un par de cosas...
	 */
	private function _joinShip( $ship, $msg, $params = FALSE) {
		$chat_id = $msg->chatId();
		$chat_title = $msg->chatTitle();
		$chat_title = ( !empty($chat_title) ) ? $msg->chatTitle() : ('ship-'.microtime());

		$new_player = false;
		$user_id = $msg->fromId();
		$username = $msg->fromUsername();
		$first_name = $msg->fromFirstName();

		$joiner = $msg->joiner();

		if ($msg->isBotJoin()) {
			// el bot es quien entra al grupo (no se contabiliza como crew)
			$output = array(
				'chat_id' => $chat_id,
				'text' => "Boot loading ".$chat_title."...\n".
						"...Loading space mappings...Ok\n".
						"...Loading personality interface...Failure (not funny dough)\n".
						"...Loading commands....Ok\n".
						"...Loading cat images and videos.............\n".
						".............................................\n".
						".............................................\n".
						">timeout - too many cat images and videos\n".
						"...Loading Intergalactic Guidance...Ok\n".
						"...Loading language packages...Ok (availables after checkings)\n".
						"...Check systems....Ok\n".
						"...Check intergalactic maps...Ok\n".
						"...Check improved human virtual reality interface...Failure (no VR yet)\n".
						"...Check fish...Ok\n".
						"¡La nave está lista!\n\n".
						"Hola...? soy el ordenador de abordo...\n".
						"¿ Hay alguien ahí ? Si hay alguien que escriba '/pilotar' ya!"
			);
			return $this->CI->telegram->sendMessage($output);
		}

		$user = $this->CI->Users->get_user($joiner->id);
		if (!$user) {
			$new_player = true;
			$user = $this->CI->Users->create_user(array('id' => $joiner->id, 'username' => $joiner->username, 'first_name' => $joiner->first_name));
		}

		$captain = $this->CI->Users->get_user($ship->captain);

		$crew_member = $this->CI->Crew->get_crew_member($ship->id, $joiner->id);
		if (empty($crew_member) )
			if (!$this->CI->Crew->create_crew(array('ship_id' => $ship->id, 'user_id' => $joiner->id))){
				$output = array(
					'chat_id' => $chat_id,
					'text' => "El usuario @".$joiner->username." no ha sido añadido a la tripulación. ".
							"Es necesario que vuelvas a introducirle en el grupo para que cuente como tripulante."
				);
				return $this->CI->telegram->sendMessage($output);
			};

		$crew_count = $ship->total_crew + 1;
		$this->CI->Ships->update_ship(array('total_crew' => $crew_count), $ship->id);

		$outputGroup = array(
			'chat_id' => $chat_id,
			'text' => "¡Ey Capitan! @".$joiner->username." ahora es un nuevo miembro de la '".$ship->name."'.\n\n".
					  "Capitan @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' ).", su nave ahora tiene ".$crew_count." miembros!"
		);
		$this->CI->telegram->sendMessage($outputGroup);
		$outputMention = array(
			'chat_id' => $joiner->id,
			'text' => "@".$joiner->username."! Ahora eres miembro de una nave, la '".$ship->name."'.\n".
					  "Permíteme presentarme, soy el ordenador de abordo\n.".
					  "Durante tu periplo por el espacio junto al capitan @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' )." podrás vivir aventuras trepidantes!".
					  "Recuerda estar atento a las órdenes de tu capitan, te necesita para cumplir sus objetivos.".
					  "Aunque siempre puedes fastidiarle el paseo y echarlo de su propia nave!! encuentra el cómo..."
		);
		$this->CI->telegram->sendMessage($outputMention);

	}

	private function _leaveShip($ship, $msg, $params = FALSE) {

		if (empty($ship)) return false;

		$chat_id = $msg->chatId();
		$chat_title = $msg->chatTitle();
		$chat_title = ( !empty($chat_title) ) ? $chat_title : ('ship-'.microtime());

		$new_player = false;
		$user_id = $msg->fromId();
		$username = $msg->fromUsername();
		$first_name = $msg->fromFirstName();

		$leaver = $msg->leaver();

		$captain = $this->CI->Users->get_user($ship->captain);

		if ($msg->isBotLeave()) {

			// el bot es quien sale del grupo. Ver qué hacemos:
			// - desactivar la nave dejándola ahí como asteroide hasta que la atraquen
			// - destruir los registros de tripulación y de nave para siempre
			// - comprobar si al añadir al bot de nuevo ocurre algo.
			$this->CI->Ships->update_ship(array( 'active' => 0, 'chat_id' => null ), $ship->id);

			$output = array(
				'chat_id' => $captain->id,
				'text' => "@".( isset($captain->username) ? $captain->username : 'no-hay-capitan' )." has eliminado el ordenador de abordo de tu nave '".$ship->name."'.\n".
						"Esto tendrá implicaciones, tu nave desaparecerá y quedará a la deriva.\n".
						"Sin contar con que tu y toda tu tripulación habéis muerto... so sad...\n".
						"Ehm, bueno... ".$ship->total_crew." bajas tampoco son tantas, el espacio es muy basto.\n".
						"Ya no será accesible y otras naves se aprovecharán de sus recursos.\n".
						"Ánimo. Podría ser peor... cogiste tu toalla verdad? verdad!?"
			);
			return $this->CI->telegram->sendMessage($output);
			
		}

		$user = $this->CI->Users->get_user($leaver->id);
		if (!$user) {
			$new_player = true;
			$user = $this->CI->Users->create_user(array('id' => $leaver->id, 'username' => $leaver->username, 'first_name' => $leaver->first_name));
		}

		if ($this->CI->Crew->get_crew_member(array('ship_id' => $ship->id, 'user_id' => $leaver->id)))
			if (!$this->CI->Crew->delete_crew(array('ship_id' => $ship->id, 'user_id' => $leaver->id))){
				$output = array(
					'chat_id' => $chat_id,
					'text' => "El usuario @".$joiner->username." no ha sido eliminado de la tripulación. ".
							"Si fue añadido al grupo antes que yo es normal. Si no, para que deje de contar deberás volver a añadirle y volver a expulsarle."
				);
				return $this->CI->telegram->sendMessage($output);
			};

		$crew_count = $ship->total_crew - 1;
		$this->CI->Ships->update_ship(array('total_crew' => $crew_count), $ship->id);

		if ($ship->captain == $leaver->id) {
			// el capitan abandona la nave!!
			$this->CI->Ships->update_ship(array('captain' => null), $ship->id);
			$output = array(
				'chat_id' => $chat_id,
				'text' => "¡Oh Dios! ¡Oh Diooos! El capitán se ha ido y vamos a la deriva.\n\n".
						  "Que no cunda el pánico, cualquiera en la tripulación puede intentar tomar el control usando '/pilotar'\n\n".
						  "Por cierto, ya estaba cansado de ese tal @".$leaver->username.". Menudo paquete..."
			);
			$this->CI->telegram->sendMessage($output);
		}
		else {
			$outputGroup = array(
				'chat_id' => $chat_id,
				'text' => "¡Ey Capitan! @".$leaver->username." abandonó su name.\n\n".
						  "Capitan @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' ).", su nave ahora tiene ".$crew_count." miembros!"
			);
			$this->CI->telegram->sendMessage($outputGroup);
			$outputMention = array(
				'chat_id' => $leaver->id,
				'text' => "@".$leaver->username."! has abandonado la nave '".$ship->name."'".
						  " y ya no recibirás más mensajes relacionados con ella.\n\n".
						  "No olvides tu toalla. Adios y gracias por el pescado."
			);
			$this->CI->telegram->sendMessage($outputMention);
		}

	}


	/**
	  Acción texto de ayuda. Distingue entre ayuda a canal nuevo y canal que ya es nave.
	 */
	private function _ayuda($msg, $already_ship=false, $params = FALSE) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();

		if ($already_ship) {

			if ( $user_id != $already_ship->captain ) {
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "Grumete, no hay mucho más que hacer por ahora. Siempre puedes invitar colegas a la nave."
				);
			}
			else {			
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "Capitán, no hay mucho más que hacer por ahora. ".
							"¿Por qué no observamos juntos el espacio frente a nosotros? Acaricieme el ratón capitán...\n\n".
							"Aunque bueno... siempre puedes aumentar la tripulación de la nave y no ser tan patético."
				);
			}

		} else {
			$content = array('chat_id' => $chat_id, 'text' => "Bienvenido a Interestelegram, tu aventura espacial!\n\nPara jugar debes configurar un username en tu cuenta de Telegram en Ajustes. Después, crea un grupo e invita a este bot.\n\nUtiliza el comando /pilotar para iniciar la partida convirtiendote en el piloto de la nave.\n\nTu nave necesita tripulación, así que invita a toda la gente que quieras al grupo. Recuerda que necesitas su participación para que tu nave funcione!");
		}
		
		$output = $this->CI->telegram->sendMessage($content);
	}


	/**
	  Acción pilotar. Crea la nave en base de datos y asigna al capitán. Detecta si el capitán ya se ha fijado.
	 */
	private function _pilotar($msg, $ship=null, $params = FALSE) {
		$chat_id = $msg->chatId();
		$chat_title = $msg->chatTitle();
		$chat_title = ( !empty($chat_title) ) ? $chat_title : ('ship-'.microtime());
		$username = $msg->fromUsername();
		$first_name = $msg->fromFirstName();
		$user_id = $msg->fromId();

		if (empty($ship)) {
			if ($username != null) {
				// create new ship.
				$ship = $this->CI->Ships->create_ship(array('chat_id' => $chat_id, 'captain' => $user_id, 'name' => $chat_title, 'total_crew' => 1, 'active' => 1));
				// create user if does not exist
				$user = $this->CI->Users->get_user($user_id);
				if (!$user) $user = $this->CI->Users->create_user(array('id' => $user_id, 'username' => $username, 'first_name' => $first_name));
				$crew = $this->CI->Crew->create_crew(array('ship_id' => $ship->id, 'user_id' => $user->id));

				$content = array('chat_id' => $chat_id, 'text' => 'Ascendiendo a @'.$username.' a piloto de la nave');
				$output = $this->CI->telegram->sendMessage($content);
				$content = array('chat_id' => $chat_id, 'text' => 'La "'.$chat_title.'" ha despegado con una tripulación de un solo miembro, el capitán '.$username.".\n\nBuena suerte!");
				$output = $this->CI->telegram->sendMessage($content);
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'Para ser piloto necesitas configurar un username en Ajustes');						
				$output = $this->CI->telegram->sendMessage($content);
			}
		} else {
			
			$captain = ( is_null($ship->captain) || $ship->captain == 0 ) ? null : $this->CI->Users->get_user($ship->captain);

			if ($user_id != $ship->captain) {

				if (empty($captain)) {
					$this->CI->Ships->update_ship(array('captain' => $user_id), $ship->id);

					$content = array('chat_id' => $chat_id, 'text' => 'Ascendiendo a @'.$username.' a piloto de la nave');
					$output = $this->CI->telegram->sendMessage($content);
				}
				else {
					$content = array('chat_id' => $chat_id, 'text' => 'La "'.$chat_title.'" ya tiene piloto, el capitán '.( isset($captain->username) ? $captain->username : 'no-hay-capitan' ));						
					$output = $this->CI->telegram->sendMessage($content);	
				}
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'Capitán, ya pilotáis la "'.$chat_title.'". Alguna otra orden?');						
				$output = $this->CI->telegram->sendMessage($content);	
			}
		}
	}

/**
	Acción escanear/seleccionar
*/

	private function _escanear($msg, $ship, $params = FALSE){

		$option = array( array("SI", "NO") );
		$chat_id = $msg->chatId();
		$text = "El capitán quiere escanear el sector en busca de objetivos ¿Ayudas a escanear?";

		// Create custom keyboard
		$keyboard = $this->CI->telegram->buildKeyBoard($option, $onetime=TRUE, $resize=TRUE, $selective=FALSE);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyboard, 'text' => $text);
		$output = $this->CI->telegram->sendMessage($content);
		$response = json_decode($output);

		if ($response->ok){
			$message_id = $response->result->message_id;
			$this->CI->Actions->create_action(array( 
				'chat_id' => $chat_id, 
				'ship_id' => $ship->id, 
				'captain_id' => $ship->captain, 
				'message_id' => $message_id,
				'command' => 'listar',
				'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ) ));
		}

	}

	private function _listar($msg, $ship, $params = FALSE) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);

		$sectorShips = $this->CI->Ships->get_ships_by_xy( $ship->x, $ship->y, $ship->chat_id);

		foreach ($sectorShips as $shipIndex => $sectorShip){

			$captain = $this->CI->Users->get_name_by_id($sectorShip->captain);
			$captain_name = ($captain && isset($captain->username) && $captain->username != '') ? $captain->username : "Sin piloto";

			$nearShips[] = $sectorShip->id."@".$captain_name;

			$string = (strlen($sectorShip->name) > 20) ? substr($sectorShip->name,0,20).'...' : $sectorShip->name;
			$nearShipsDetail[] = $sectorShip->id.") ". $string." (@".$captain_name.") ppl:".$sectorShip->total_crew;

		}
		$nearShips[] = "Ninguno";

		$nearShipsDetailString = "";
		foreach ($nearShipsDetail as $n) $nearShipsDetailString .= "\n".$n;

		$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "Listado de naves en tu sector:\n".$nearShipsDetailString));

		$option = array($nearShips);
		$chat_id = $msg->chatId();
		$captain_id = $ship->captain;
		$user = $this->CI->Users->get_name_by_id($captain_id);
		$text = "Selecciona un objetivo @". $user->username ." :";

		// Create custom keyboard
		$keyboard = $this->CI->telegram->buildKeyBoard($option, $onetime=TRUE, $resize=TRUE, $selective=TRUE);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyboard, 'text' => $text);
		$output = $this->CI->telegram->sendMessage($content);
		$response = json_decode($output);

		if ($response->ok){
			$message_id = $response->result->message_id;
			$this->CI->Actions->create_action(array( 
				'chat_id' => $chat_id, 
				'ship_id' => $ship->id, 
				'captain_id' => $ship->captain, 
				'message_id' => $message_id,
				'command' => 'seleccionar',
				'required' => 0));
		}
		
	}


	private function _seleccionar($msg, $ship, $params) {
		$messageId = $msg->messageId();
		$username = "@".$msg->fromUsername();
		$chat_id = $msg->chatId();
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		
		$target = explode("@",$msg->text());
		if (!isset($target[1])){
			$text = $username ." eres un CACAS! xD";
		} else {
			
			$targetShip = $this->CI->Ships->get_ship($target[0]);

/**

	TODO : FALTA PONER QUE ESTÉN EN POSICIONES VÁLIDAS DE ATAQUE, AHORA ESTÁ A LA MISMA CASILLA

*/

			if ($ship->x == $targetShip->x && $ship->y == $targetShip->y ){
				//todo, comprobar el rango avanzado

				$this->CI->Ships->update_ship(array('target'=>$targetShip->id),$ship->id); 

				$this->CI->telegram->sendMessage(array('chat_id' => $targetShip->chat_id, 'text' => "\xE2\x9A\xA0 ATENCIÓN!, la nave de $username te tiene en su objetivo! Usa /esquivar para librarte de sus ataques."));
				$text = $username ." has seleccionado a ".$target[1]. " con éxito";
			} else {
				//avisar al objetivo targeteado
				$text = $username ." la nave de ".$target[1]. " se está demasiado lejos para seleccionarla.";
			}
	
		}

		$content = array(
			'reply_to_message_id' => $messageId, 
			'reply_markup' => $keyboard, 
			'chat_id' => $chat_id, 
			'text' => $text
		);

		$output = $this->CI->telegram->sendMessage($content);
	}




	/**
	  Acción informe. Lista la información de la nave actual. 
	 */
	private function _informe($msg, $already_ship=false, $params = FALSE) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();

		if ($already_ship) {

			if ($user_id == $already_ship->captain ) {

				$Ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);

				$this->CI->load->library('Mapdrawer');
				$this->CI->mapdrawer->__random();
				$imagePath = $this->CI->mapdrawer->generateMap();


				$target = false;
				if (!is_null($Ship->target)){
					$TargetedShip = $this->CI->Ships->get_ship($Ship->target);
					$target = "\n\xF0\x9F\x92\xA2: ".$TargetedShip->name;
				}

				$img = $this->CI->telegram->prepareImage($imagePath);
				// http://apps.timwhitlock.info/emoji/tables/unicode
				$caption = "Información de la nave:".
							"\n\xF0\x9F\x9A\x80: ".$Ship->name.
							$target.
							"\n\xE2\x9D\xA4: ".$Ship->health."/".$Ship->max_health.
							"\n\xF0\x9F\x94\xB5: ".$Ship->shield."/".$Ship->max_shield.
							"\n\xF0\x9F\x92\xB0: ".$Ship->money.
							"\n\xF0\x9F\x92\x8E: ".$Ship->minerals;
							//.print_r($Ship,true);

				$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
				$output = $this->CI->telegram->sendPhoto($content);
				log_message('error', print_r($output, TRUE));
			}
			else {			
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "Sólo el capitán puede pedir el informe."
				);
				$output = $this->CI->telegram->sendMessage($content);
			}

		} 
		
	}



	/**
		Acción test
	*/

	private function _esquivar($msg, $ship, $params = FALSE){

		$option = array( array("SI", "NO") );
		$chat_id = $msg->chatId();
		$text = "El capitán quiere hacer maniobra de evasión ¿Ayudas en la maniobra?";

		// Create custom keyboard
		$keyboard = $this->CI->telegram->buildKeyBoard($option, $onetime=TRUE, $resize=TRUE, $selective=FALSE);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyboard, 'text' => $text);
		$output = $this->CI->telegram->sendMessage($content);
		$response = json_decode($output);

		if ($response->ok){
			$message_id = $response->result->message_id;
			$this->CI->Actions->create_action(array( 
				'chat_id' => $chat_id, 
				'ship_id' => $ship->id, 
				'captain_id' => $ship->captain, 
				'message_id' => $message_id,
				'command' => 'do_esquivar',
				'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ) ));
		}

	}

	private function _do_esquivar($msg, $ship, $params) {
		$messageId = $msg->messageId();
		$username = "@".$msg->fromUsername();
		$chat_id = $msg->chatId();
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		
/**

	TODO : FALTA EL CALCULO DE ESQUIVA Y LA FUNCIÓN QUE TE QUITA EL TARGET DE TODAS LAS NAVES

*/

		if ( false ){ //cambiar esto por el cálculo de esquive
			//quitar el id de todos los que le targetean
			//$this->CI->Ships->untarget_ship($ship->id); 
			//avisar de exito
			$text = $username ." has desaparecido del radar de tus enemigos!";
		} else {
			//avisar de pifia
			$text = $username ." la maniobra evasiva ha fallado y aún sigues en el radar de tus enemigos! Vuelve a usar /esquivar las veces que quieras o huye";
		}

		$content = array(
			'reply_to_message_id' => $messageId, 
			'reply_markup' => $keyboard, 
			'chat_id' => $chat_id, 
			'text' => $text
		);

		$output = $this->CI->telegram->sendMessage($content);
	}



	/**
		Acción test
	*/
	private function _test($msg, $ship, $params = FALSE){

		$option = array( array("SI", "NO") );
		$chat_id = $msg->chatId();
		$text = "¿Nos vamos de paseo?";

		// Create custom keyboard
		$keyboard = $this->CI->telegram->buildKeyBoard($option, $onetime=TRUE, $resize=TRUE, $selective=FALSE);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyboard, 'text' => $text);
		$output = $this->CI->telegram->sendMessage($content);
		$response = json_decode($output);

		if ($response->ok){
			$message_id = $response->result->message_id;
			$this->CI->Actions->create_action(array( 
				'chat_id' => $chat_id, 
				'ship_id' => $ship->id, 
				'captain_id' => $ship->captain, 
				'message_id' => $message_id,
				'command' => 'ayuda',
				'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ) ));
		}

	}

}

/* EOF */