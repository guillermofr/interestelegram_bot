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
		elseif ($msg->isJoin()) $this->_joinShip( $ship, $msg );
		elseif ($msg->isLeave()) $this->_leaveShip( $ship, $msg );
		elseif ($msg->isReply()) $this->_processReply( $ship, $msg );
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

		if (empty($ship)) {
			if ( $command == 'ayuda') $this->_ayuda($msg);
			elseif ( $command == 'pilotar' ) $this->_pilotar($msg);
		}
		else {
			if ( $command == 'ayuda') {
				$this->_ayuda($msg, $ship);
			}
			elseif ( $command == 'pilotar' ) {
				$this->_pilotar($msg, $ship);
			}
			elseif ( $command == 'test' && $ship->captain == $fromId ) {
				$this->_test($msg, $ship);
			}
			elseif ( $command == 'escanear' && $ship->captain == $fromId ) {
				$this->_vote_escanear($msg, $ship);
			}
			elseif ( $command == 'informe' ) {
				$this->_informe($msg, $ship);
			}
			else {
				$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'El comando "'.$command.'" no está contemplado o no tienes permisos para usarlo.'));
			}
		}

	}



	/**
	 * _joinShip
	 * - Esta operación es respuesta a un evento en el grupo. Un nuevo usuario ha entrado y deberá formar parte de la tripulación
	 * - Una vez añadido a la tripulación, creado el usuario si no existe y aumentado el contador en la nave hay que responder
	 * - responder a la nave que hay un nuevo tripulante.
	 * - mencionar al nuevo tripulante y decirle un par de cosas...
	 */
	private function _joinShip(& $ship, & $msg) {
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

	private function _leaveShip(& $ship, & $msg) {

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

		// seleccionar un objetivo (votación especial del capitán)
		if ($last_action->command == 'seleccionar'){
			return $this->_seleccionar( $msg, $ship );
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

		$output = array(
			'chat_id' => $chatId,
			'text' => "Votación ".($response_value + $last_action->positives)." de ".$last_action->required." hecha por @{$username} ({$response})"
		);
		$o = $this->CI->telegram->sendMessage($output);

		// hide keyboard
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		$content = array('chat_id' => $chatId, 'reply_markup' => $keyboard, 'reply_to_message_id' => $messageId, 'text' => 'tu voto se ha registrado');
		$o = $this->CI->telegram->sendMessage($content);

		//apply action if success
		if ($apply_action) {
			$this->{"_{$last_action->command}"}( $msg, $ship );
		}

	}

	/**
	  Acción texto de ayuda. Distingue entre ayuda a canal nuevo y canal que ya es nave.
	 */
	private function _ayuda(& $msg, $already_ship=false) {
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
	private function _pilotar(& $msg, $ship=null) {
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

	private function _vote_escanear(& $msg, & $ship){

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
				'command' => 'escanear',
				'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ) ));
		}

	}

	private function _escanear(& $msg, & $ship) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);

		//$username = $msg->fromUsername();

		$ships = $this->CI->Ships->get_ships_by_xy( 0 , 0 ,$chat_id);

		foreach ($ships as $k => $s){

			$cpt = $this->CI->Users->get_name_by_id($s->captain);

			$nearShips[] = $s->chat_id."@".$cpt->username;

			$string = (strlen($s->name) > 20) ? substr($s->name,0,20).'...' : $s->name;
			$nearShipsDetail[] = ($k+1).") ". $string." (@".$cpt->username.")";
			//$nearShips[] = "@".$s->captain;

		}
		$nearShips[] = "Ninguno";

		$nearShipsDetailString = "";
		foreach ($nearShipsDetail as $n){
			$nearShipsDetailString .= "\n".$n;
		}

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
				'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ) ));
		}


		
	}

	private function _seleccionar(& $msg, & $ship) {
		$messageId = $msg->messageId();
		$username = "@".$msg->fromUsername();
		$chat_id = $msg->chatId();
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		
		$target = explode("@",$msg->text());
		print_r($target);
		if (!isset($target[1])){
			$text = $username ." eres un CACAS! xD";
		} else {
			$text = $username ." has seleccionado a ".$target[1];

			
			//avisar al objetivo targeteado
			//echo "<pre>";
			//$targetCaptain = $this->CI->Users->get_id_by_name(substr($msg->text(),1));
			//print_r($targetCaptain);
			//$targetShip = $this->CI->Ships->get_ship_by_captain($targetCaptain->id);
			//print_r($targetShip);
			$this->CI->telegram->sendMessage(array('chat_id' => $target[0], 'text' => "⚠ ATENCIÓN!, la nave de $username te tiene en su objetivo!"));

			/**

				TODO. METER EN BASE DE DATOS EL TARGET.

			*/


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
	private function _informe(& $msg, $already_ship=false) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();

		if ($already_ship) {

			if ( $user_id == $already_ship->captain ) {

				$Ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);

				$this->CI->load->library('Mapdrawer');
				$this->CI->mapdrawer->__random();
				$imagePath = $this->CI->mapdrawer->generateMap();

				$img = '@'.realpath($imagePath);
				$caption = "Información de la nave:\n
					
					Nombre: ".$Ship->name."
					\xE2\x9D\xA4: ".$Ship->health."/".$Ship->max_health."
					\xF0\x9F\x8C\x80:".$Ship->shield."/".$Ship->max_shield."
					money: ".$Ship->money."
					minerals: ".$Ship->minerals."
					".print_r($Ship,true);

				$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
				$output = $this->CI->telegram->sendPhoto($content);
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

	private function _test(& $msg, & $ship){

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