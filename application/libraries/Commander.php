<?php

class Commander {

	private $CI = null;
	private $botToken = null;
	private $botUsername = null;

	private $shipInitialHealth = 5;

	private $captain_methods = array(
				'escanear', 
				'informe', 
				'test'
	);

	public function __construct() {
		$this->CI =& get_instance();

		$this->CI->load->model(array(
							'Ships',
							'Users',
							'Crew',
							'Actions',
							'Votes',
							'Asteroids',
							'Powerups'));
		$this->CI->load->library('Calculations');
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


	private function _isCheat($last_action, $command) {

		if (is_null($last_action) || 
			( 
				!is_null($last_action) && 
				( 
					$last_action->prev_command != $command || 
					( $last_action->prev_command == $command && !$last_action->fail ) 
				) 
			)
		) 
			return TRUE;
		else 
			return FALSE;
		
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
						"¿ Hay alguien ahí ? Si hay alguien, que escriba /pilotar ya para ser el capitán. Los demás pueden escribir /alistarse para ayudar al capitán.\n".
						"Si necesitáis más información, utilizad /ayuda"
			);
			return $this->CI->telegram->sendMessage($output);
		}


		/* prevent invalid joins */
		if ($msg->isInvalidJoin()){
			$output = array(
				'chat_id' => $joiner->id,
				'text' => 'Para poder usar '.$this->botUsername.' es necesario que configures un alias.'
			);
			return $this->CI->telegram->sendMessage($output);
		}

		$user = $this->CI->Users->get_user($joiner->id);
		if (!$user) {
			$new_player = true;
			$user = $this->CI->Users->create_user(array('id' => $joiner->id, 'username' => $joiner->username, 'first_name' => $joiner->first_name));
		}

		$ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);
		if (!$ship) {
			$output = array(
				'chat_id' => $chat_id,
				'text' => "No puedo tener en cuenta a los nuevos tripulantes si nadie toma el mando primero: que alguien diga /pilotar !"
			);
			return $this->CI->telegram->sendMessage($output);
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

		$this->CI->load->library('Calculations');
		$newHealth = $this->CI->calculations->ship_health($ship, 1);

		$crew_count = $ship->total_crew + 1;
		$this->CI->Ships->update_ship(array( 'total_crew' => $crew_count, 'health' => $newHealth['health'], 'max_health' => $newHealth['max_health'], 'max_shield' => $newHealth['max_shield'] ), $ship->id);

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

		/* prevent invalid leave */
		if ($msg->isInvalidLeave()){
			$output = array(
				'chat_id' => $joiner->id,
				'text' => 'Para poder usar '.$this->botUsername.' es necesario que configures un alias.'
			);
			return $this->CI->telegram->sendMessage($output);
		}

		$user = $this->CI->Users->get_user($leaver->id);

		if (!$user) {
			$new_player = true;
			$user = $this->CI->Users->create_user(array('id' => $leaver->id, 'username' => $leaver->username, 'first_name' => $leaver->first_name));
		}

		if ($this->CI->Crew->get_crew_member($ship->id, $leaver->id)){
			if (!$this->CI->Crew->delete_crew($ship->id, $leaver->id)){
				/* Se puede entrar aquí? puedo hacer get y fallar el delete?
				$output = array(
					'chat_id' => $chat_id,
					'text' => "El usuario @".$leaver->username." no ha sido eliminado de la tripulación. ".
							"Si fue añadido al grupo antes que yo es normal. Si no, para que deje de contar deberás volver a añadirle y volver a expulsarle."
				);
				return $this->CI->telegram->sendMessage($output);
				*/
			};
		} else return;

		$this->CI->load->library('Calculations');
		$newHealth = $this->CI->calculations->ship_health($ship, -1);

		$crew_count = $ship->total_crew - 1;
		$this->CI->Ships->update_ship(array('total_crew' => $crew_count, 'health' => $newHealth['health'], 'max_health' => $newHealth['max_health'], 'max_shield' => $newHealth['max_shield']), $ship->id);

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
				'text' => "¡Ey Capitan! @".$leaver->username." abandonó su nave.\n\n".
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
					'text' => "Formas parte de la tripulación, tu misión es ayudar al capitan votando en las acciones que requieran tu participación. Ten en mente que sin tu ayuda el capitán no podrá hacer ciertas acciones. Eres indispensable para ganar."
				);
			}
			else {			
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "Capitán, ahora pilotáis una nave en un sector del espacio hostil. Vuestro objetivo es sobrevivir, y para ello tal vez tengáis que luchar.\n".
							"Cada tripulante incrementará la vida de vuestra nave, pero reducirá su capacidad para evitar ataques. Además, una nave con mucha tripulación necesita más colaboración para realizar tareas como /mover\n".
							"Todas las acciones de la nave requieren una cantidad mínima de votos para realizarse. Eso significa que una nave pequeña solo puede lanzar ataques pequeños, aunque no necesita apoyos para moverse, mientras una nave grande puede lanzar grandes ataques y sus movimientos serán más lentos al necesitar más colaboración para completarlos.\n".
							"Lo primero que debéis hacer es pedir un /informe para ver vuestro estado, o /escanear para intentar fijar en el blanco a una nave enemiga. Una vez fijada, un símbolo os indicará su posición si huye.\n".
							"Vuestra nave puede atacar solo hacia el frente, en el arco de fuego señalado en rojo (incluye la misma casilla en la que te encuentras). Es importante que os coloquéis bien para poder atacar.\n"
				);
				$output = $this->CI->telegram->sendMessage($content);
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "Para atacar utiliza /atacar_1 /atacar_2 etc o /a1 /a2 dependiendo de cuanta potencia quieras utilizar. Recuerda que necesitarás que tu tripulación colabore si lanzas ataques grandes\n".
							  "La probabilidad de impacto depende de la diferencia de tamaños de las naves. Una nave grande tendrá problemas para impactar a una pequeña, mientras que la pequeña tendrá menos fallos contra una mayor.\n".
							  "Si te fijan en el blanco y quieres evitar que te ataquen, deberás huír o utilizar /esquivar para que la nave enemiga deje de tenerte fijada en el blanco.\n".
							  "Si hay gente en el canal que no forma parte de la nave, deben utilizar /alistarse para participar. Si escribes / verás sugerencias de comandos para utilizar"
				);
			}

		} else {
			$content = array('chat_id' => $chat_id, 'text' => "Bienvenido a Interestelegram, tu aventura espacial!\n\nPara jugar debes configurar un alias en tu cuenta de Telegram en Ajustes. Después, crea un grupo e invita a este bot.\n\nUtiliza el comando /pilotar para iniciar la partida convirtiendote en el piloto de la nave.\n\nTu nave necesita tripulación, así que invita a toda la gente que quieras al grupo. Recuerda que necesitas su participación para que tu nave funcione!");
		}
		
		$output = $this->CI->telegram->sendMessage($content);
	}


	/**
	  Acción pilotar. Crea la nave en base de datos y asigna al capitán. Detecta si el capitán ya se ha fijado.
	 */
	private function _pilotar($msg, $ship=null, $params = FALSE) {
		$this->CI->load->library('Movement');
		$chat_id = $msg->chatId();
		$chat_title = $msg->chatTitle();
		$chat_title = ( !empty($chat_title) ) ? $chat_title : ('ship-'.microtime());
		$username = $msg->fromUsername();
		$first_name = $msg->fromFirstName();
		$user_id = $msg->fromId();

		if (empty($ship)) {
			if ($username != null) {
				// create new ship.
				$ship = $this->CI->Ships->create_ship(array('chat_id' => $chat_id, 
															'captain' => $user_id, 
															'name' => $chat_title, 
															'total_crew' => 1, 
															'active' => 1, 
															'x'=>$this->CI->movement->generateRandomX(),
															'y'=>$this->CI->movement->generateRandomY(),
															'angle'=>$this->CI->movement->generateRandomAngle(),
															'health' => $this->shipInitialHealth,
															'max_health' => $this->shipInitialHealth,
															'max_shield' => $this->shipInitialHealth));
				// create user if does not exist
				$user = $this->CI->Users->get_user($user_id);
				if (!$user) $user = $this->CI->Users->create_user(array('id' => $user_id, 'username' => $username, 'first_name' => $first_name));
				$crew = $this->CI->Crew->create_crew(array('ship_id' => $ship->id, 'user_id' => $user->id));

				$content = array('chat_id' => $chat_id, 'text' => 'Ascendiendo a @'.$username.' a piloto de la nave');
				$output = $this->CI->telegram->sendMessage($content);

				$this->CI->load->library('Mapdrawer');
				$imagePath = $this->CI->mapdrawer->generateShipMap($ship);
				$img = $this->CI->telegram->prepareImage($imagePath);
				
				$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => 'La "'.$chat_title.'" ha despegado con una tripulación de un solo miembro, el capitán '.$username.".\n\nBuena suerte!");
				$output = $this->CI->telegram->sendPhoto($content);

				$this->CI->telegram->updateImage($imagePath, $output);
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'Para ser piloto necesitas configurar un alias en Ajustes');						
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

		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$messageId = $msg->messageId();
		
		if ($user_id == $ship->captain ) {
			$option = array( array("SI", "NO") );
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
					'command' => 'do_escanear',
					'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ),
					'prev_command' => 'escanear'
				));
			}
		} else {
			$text = "Solo el capitán puede escanear, tripulante.";
			$content = array(
				'reply_to_message_id' => $messageId, 
				'chat_id' => $chat_id, 
				'text' => $text
			);
			$output = $this->CI->telegram->sendMessage($content);
		}

	}

	private function _do_escanear($msg, $ship, $params = FALSE, $last_action = null) {

		/* Code to prevent cheating on command series */
		if ($this->_isCheat($last_action, 'escanear')) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "Listar requiere haber hecho 'escanear'.");
			return $this->CI->telegram->sendMessage($content);
		}

		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);

		$sectorShips = $this->CI->Ships->get_target_lock_candidates($ship);

		if ($this->CI->calculations->scanFailsOnAsteroids()) {
			$sectorShips = $this->CI->Asteroids->hide_ships_in_asteroids($ship, 1, $sectorShips);
		}

		$nearShips = array();

		if (is_array($sectorShips)) {
			foreach ($sectorShips as $shipIndex => $sectorShip){

				$captain_name = $this->CI->Users->get_name_by_id($sectorShip->captain);
				if (empty($captain_name)) $captain_name = "Sin piloto";

				$nearShips[] = $sectorShip->id."@".$captain_name;

				$string = (strlen($sectorShip->name) > 20) ? substr($sectorShip->name,0,20).'...' : $sectorShip->name;
				$nearShipsDetail[] = $sectorShip->id.") ". $string." (@".$captain_name.") \xF0\x9F\x91\xA5x".$sectorShip->total_crew;

			}
		}

		$this->CI->load->library('Mapdrawer');
		$imagePath = $this->CI->mapdrawer->generateShipMap($ship, true);
		$img = $this->CI->telegram->prepareImage($imagePath);
		$content = array('chat_id' => $chat_id, 'photo' => $img);
		$output = $this->CI->telegram->sendPhoto($content);
		$this->CI->telegram->updateImage($imagePath, $output);

		if (count($nearShips) > 0) {
			$nearShips[] = "Ninguno";

			$nearShipsDetailString = "";
			foreach ($nearShipsDetail as $n) $nearShipsDetailString .= "\n".$n;

			$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "Listado de naves en tu sector:\n".$nearShipsDetailString));

			$option = array();
			$perRow = 2;
			$tmp = array();
			foreach ($nearShips as $opt) {
				$tmp[] = $opt;
				if (count($tmp) == $perRow) {
					$option[] = $tmp;
					$tmp = array();
				}
			}
			if (count($tmp)) $option[] = $tmp;
			
			$chat_id = $msg->chatId();
			$captain_id = $ship->captain;
			$username = $this->CI->Users->get_name_by_id($captain_id);
			$text = "Selecciona un objetivo @". $username ." :";

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
					'command' => 'do_seleccionar',
					'required' => 0,
					'prev_command' => 'do_escanear'
				));
			}
		} else {
			$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "No hay blancos posibles en rango capitán!"));
		}
		
	}


	private function _do_seleccionar($msg, $ship, $params, $last_action = null) {

		/* Code to prevent cheating on command series */
		if ( $this->_isCheat($last_action, 'do_escanear') ) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "seleccionar requiere haber hecho 'escanear' y haber pasado la votación.");
			return $this->CI->telegram->sendMessage($content);
		}

		$messageId = $msg->messageId();
		$username = "@".$msg->fromUsername();
		$chat_id = $msg->chatId();
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		
		$target = explode("@",$msg->text());
		if (!isset($target[1])){
			$text = $username ." esperemos tener otra oportunidad capitán...";
		} else {
			
			$targetShip = $this->CI->Ships->get_ship($target[0]);
			$sectorShips = $this->CI->Ships->get_target_lock_candidates($ship);

			if (in_array($targetShip, $sectorShips)){
				$this->CI->Ships->update_ship(array('target'=>$targetShip->id),$ship->id); 

				// Avisar al objetivo targeteado
				$this->CI->telegram->sendMessage(array('chat_id' => $targetShip->chat_id, 'text' => "\xE2\x9A\xA0 ATENCIÓN!, la nave de $username te tiene en su objetivo! Usa /esquivar para librarte de sus ataques."));
				
				$text = $username ." hemos fijado en el blanco a ".$target[1]. " con éxito, estámos listos para /atacar";
			} else {
				// La nave no está en rango
				$text = $username ." la nave de ".$target[1]. " está demasiado lejos para seleccionarla.";
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


	// Atajos para el ataque
	private function _a1($msg, $ship, $params) { $this->_atacar($msg, $ship, '1'); }
	private function _a2($msg, $ship, $params) { $this->_atacar($msg, $ship, '2'); }
	private function _a3($msg, $ship, $params) { $this->_atacar($msg, $ship, '3'); }
	private function _a4($msg, $ship, $params) { $this->_atacar($msg, $ship, '4'); }
	private function _a5($msg, $ship, $params) { $this->_atacar($msg, $ship, '5'); }

	private function _atacar($msg, $ship, $params = FALSE){
		$user_id = $msg->fromId();
		$chat_id = $msg->chatId();
		$messageId = $msg->messageId();
		$param = str_replace('_', '', $params);
		$param = intval($param);

		if ($user_id == $ship->captain ) {

			if (!is_numeric($param) || $param == 0) {
				$text = "Capitán debéis indicar la potencia del ataque! ( /atacar_1 , /atacar_5 ... ) o con ( /a1 , /a2 , /a5 ... )";
				$content = array(
					'reply_to_message_id' => $messageId, 
					'chat_id' => $chat_id, 
					'text' => $text
				);
				$output = $this->CI->telegram->sendMessage($content);
			} else {
				if ($this->CI->Ships->can_i_attack($ship)) {

					$option = array( array("SI", "NO") );
					$text = "El capitán quiere atacar con potencia ".$param." ¿Apoyas el ataque?";

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
							'command' => 'do_atacar',
							'required' => $param,
							'prev_command' => 'atacar'
						));
					}
				} else {
					$text = $ship->target == null ? "Capitán no tenemos objetivo, utilice /escanear" : "Capitán la nave no se encuentra dentro de nuestro arco de fuego!";
					$content = array(
						'reply_to_message_id' => $messageId, 
						'chat_id' => $chat_id, 
						'text' => $text
					);
					$output = $this->CI->telegram->sendMessage($content);
				}
			}
		} else {
			$text = "Solo el capitán puede atacar, tripulante.";
			$content = array(
				'reply_to_message_id' => $messageId, 
				'chat_id' => $chat_id, 
				'text' => $text
			);
			$output = $this->CI->telegram->sendMessage($content);
		}
	}

	private function _do_atacar($msg, $ship, $params = FALSE, $last_action = null){

		/* Code to prevent cheating on command series */
		if ( $this->_isCheat($last_action, 'atacar') ) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "realizar un ataque requiere haber hecho 'atacar' y haber pasado la votación.");
			return $this->CI->telegram->sendMessage($content);
		}

		$chat_id = $msg->chatId();

		$quantity = $last_action->required == 1 ? 'una bolea' : $last_action->required.' boleas';
		$imagePath = APPPATH.'../imgs/attack.png';
		$img = $this->CI->telegram->prepareImage($imagePath);

		$caption = "Atacando con ".$quantity." de torpedos de protones!";
		$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
		$output = $this->CI->telegram->sendPhoto($content);

		$this->CI->telegram->updateImage($imagePath, $output);

		$target_ship = $this->CI->Ships->get($ship->target);
		if ($ship->target != null && $this->CI->calculations->attack_success($ship, $target_ship)) {
			$target_ship = $this->CI->Ships->deal_damage($target_ship, $last_action->required);

			$text = "IMPACTO!!!";
			$text .= "\nEstado de la nave objetivo:".
				"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
				"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield;
			
			$target_text = "\xF0\x9F\x94\xA5 ATENCIÓN! La ".$ship->name.' de @'.$this->CI->Users->get_name_by_id($ship->captain).' nos acaba de alcanzar con su ataque!!';
			$target_text .= "\nEstado de la nave:".
				"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
				"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield;

			if ($target_ship->health == 0) {

				//calcular ranking
				$score = 500 + intval(($target_ship->score - $ship->score)/5);
				if ($score < 50) $score = 50;
				$this->CI->Ships->update_ship(array('score' => $ship->score + $score, 'target' => null), $ship->id);
				$this->CI->Ships->update(array('target' => null), array('target' => $target_ship->id)); // remove target from any other ship
				$playerScore = $target_ship->score - 1000;
				$pilot = $this->CI->Users->get_user($target_ship->captain);
				$this->CI->Users->update_user(array('score' => $pilot->score + $playerScore), $target_ship->captain);

			 	$text = "IMPACTO!!!";
				$text .= "\nEl enemigo ha sido destruido!:".
					"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
					"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield.
					"\n\nHemos obtenido +".$score." puntos!";


			 	$target_text = "\xF0\x9F\x92\x80 ATENCIÓN! La ".$ship->name.' de @'.$this->CI->Users->get_name_by_id($ship->captain).' nos acaba de destruir con su ataque!!';
				$target_text .= "\nEstado de la nave:".
					"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
					"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield.
					"\nLos pedazos de tu lamentable nave se esparcen por el espacio, hasta aquí ha llegado tu aventura capitán!
					\n(BETATESTERS: puedes resucitar la nave usando \n/pilotar , la tripulación necesita volver a \n/alistarse para que el ordenador de abordo los tome en cuenta.)" ;

				//morirse <-- seriously? morirsen!
				$this->CI->Ships->update_ship(array( 'active' => 0, 'chat_id' => null ), $target_ship->id);
				$this->CI->Ships->untarget_ship($target_ship);

				//poner explosión

				$this->CI->load->library('Mapdrawer');
				$imagePath = $this->CI->mapdrawer->generateShipMap($target_ship,false,true);
				$img = $this->CI->telegram->prepareImage($imagePath);

				$caption = "Boom!:";

				$content = array('chat_id' => $target_ship->chat_id, 'photo' => $img, 'caption' => $caption );
				$output = $this->CI->telegram->sendPhoto($content);

				$this->CI->telegram->updateImage($imagePath, $output);

			}
		} else {
			$text = "El ataque ha fallado!";
			$target_text = "\xE2\x9A\xA0 ATENCIÓN! La ".$ship->name.' de @'.$this->CI->Users->get_name_by_id($ship->captain).' nos esta atacando! Por suerte ha fallado!';
		}

		$content = array(
			'chat_id' => $chat_id, 
			'text' => $text
		);
		$output = $this->CI->telegram->sendMessage($content);

		// Avisar del impacto al objetivo
		$content = array(
			'chat_id' => $target_ship->chat_id, 
			'text' => $target_text
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
				$Captain = $this->CI->Users->get($user_id);

				$this->CI->load->library('Mapdrawer');
				$imagePath = $this->CI->mapdrawer->generateShipMap($Ship);
				$img = $this->CI->telegram->prepareImage($imagePath);

				$target = false;
				if (!is_null($Ship->target)){
					$TargetedShip = $this->CI->Ships->get_ship($Ship->target);
					$target = "\n\xF0\x9F\x92\xA2: ".$TargetedShip->name;
				}

				// http://apps.timwhitlock.info/emoji/tables/unicode
				$caption = "Información de la nave:".
							"\n\xF0\x9F\x9A\x80: ".$Ship->name.
							$target.
							"\n\xE2\x9D\xA4: ".$Ship->health."/".$Ship->max_health.
							"\n\xF0\x9F\x94\xB5: ".$Ship->shield."/".$Ship->max_shield.
							"\n\xF0\x9F\x92\xB0: ".$Ship->money.
							"\n\xF0\x9F\x92\x8E: ".$Ship->minerals.
							"\n\xF0\x9F\x8E\xAE: ".$Ship->score.
							"\n\xF0\x9F\x8F\x86: ".$Captain->score.'(+'.($Ship->score-1000).')';
							//.print_r($Ship,true);

				$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
				$output = $this->CI->telegram->sendPhoto($content);

				$this->CI->telegram->updateImage($imagePath, $output);
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
		Acción esquivar
	*/

	private function _esquivar($msg, $ship, $params = FALSE){

		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$messageId = $msg->messageId();
		$needDodge = $this->CI->Ships->needDodge($ship);
		
		if ($user_id == $ship->captain ) {
			if (!$needDodge){
				$text = "Nadie te tiene seleccionado, no es necesario esquivar...";
				$content = array(
					'reply_to_message_id' => $messageId, 
					'chat_id' => $chat_id, 
					'text' => $text
				);
				return $this->CI->telegram->sendMessage($content);
			}

			$option = array( array("SI", "NO") );
			$text = "El capitán quiere hacer maniobra de evasión para esquivar $needDodge enemigo".(($needDodge==1)?"":"s")." ¿Ayudas en la maniobra?";

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
					'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ),
					'prev_command' => 'esquivar'
				));
			}
		} else {
			$text = "Solo el capitán puede esquivar, tripulante.";
			$content = array(
				'reply_to_message_id' => $messageId, 
				'chat_id' => $chat_id, 
				'text' => $text
			);
			$output = $this->CI->telegram->sendMessage($content);
		}
	}

	private function _do_esquivar($msg, $ship, $params, $last_action = null) {

		/* Code to prevent cheating on command series */
		if ( $this->_isCheat($last_action, 'esquivar') ) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "realizar esquivar requiere haber hecho 'esquivar'.");
			return $this->CI->telegram->sendMessage($content);
		}

		$this->CI->load->library('Calculations');

		$messageId = $msg->messageId();
		$username = "@".$msg->fromUsername();
		$chat_id = $msg->chatId();
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		$enemies = $this->CI->Ships->where(array('target' => $ship->id))->get_all();

		$shipsDodged = array();
		$texts = "";
		$forEnemyText = null;

		if (is_array($enemies)) {
			$ship_captain_name = $this->CI->Users->get_name_by_id($ship->captain);
			foreach($enemies as $enemy) {
				$enemy_captain_name = $this->CI->Users->get_name_by_id($enemy->captain);
				if ($this->CI->calculations->ship_dodge($ship, $enemy)) {

					//quitar el id de todos los que le targetean
					$this->CI->Ships->untarget_ship($enemy); 
					$shipsDodged[] = $enemy;

					//avisar de exito
					$texts .= "@".$ship_captain_name." has desaparecido del radar de @".$enemy_captain_name."! \xF0\x9F\x91\x8D \n\n";
					$forEnemyText = "\xF0\x9F\x92\xA8 Hemos perdido el rastro de @".$ship_captain_name.", tenemos que volver a /escanear objetivos!";
				} else {
					//avisar de pifia
					$texts .= "@".$ship_captain_name." la maniobra evasiva ha fallado \xF0\x9F\x91\x8E y aún sigues en el radar de @".$enemy_captain_name."! Debemos alejarnos más de él! Vuelve a usar /esquivar las veces que quieras o huye \n\n";
				}
			}
		} else {
			$texts = "Ningún enemigo nos tiene en su radar en este momento capitán.";
			$forEnemyText = null;
		}
		
		//informar al user
		$content = array(
			'reply_to_message_id' => $messageId, 
			'reply_markup' => $keyboard, 
			'chat_id' => $chat_id, 
			'text' => $texts
		);

		$output = $this->CI->telegram->sendMessage($content);

		//informar a los enemigos
		if (!empty($shipsDodged) && $forEnemyText != null){
			foreach ($shipsDodged as $sD){
				$content = array(
					'chat_id' => $sD->chat_id, 
					'text' => $forEnemyText
				);

				$output = $this->CI->telegram->sendMessage($content);
			}
		}
	}

	/**
		Acción mover
	*/
	private function _mover($msg, $ship, $params = FALSE){

		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$messageId = $msg->messageId();
		if ($user_id == $ship->captain ) {
			$option = array( array("SI", "NO") );
			$text = "El capitán quiere mover la nave a otro sector \n¿Ayudas con la maniobra?";

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
					'command' => 'do_mover',
					'required' => round( ($ship->total_crew / 2), 0, PHP_ROUND_HALF_UP ),
					'prev_command' => 'mover'
				));
			}
		} else {
			$text = "Solo el capitán puede mover, tripulante.";
			$content = array(
				'reply_to_message_id' => $messageId, 
				'chat_id' => $chat_id, 
				'text' => $text
			);
			$output = $this->CI->telegram->sendMessage($content);
		}
	}

	private function _do_mover($msg, $ship, $params, $last_action = NULL) {

		/* Code to prevent cheating on command series */
		if ( $this->_isCheat($last_action, 'mover') ) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "realizar mover requiere haber hecho 'mover' y haber pasado la votación.");
			return $this->CI->telegram->sendMessage($content);
		}

		$this->CI->load->library('Movement');

		$messageId = $msg->messageId();
		$chat_id = $msg->chatId();
		$captain_id = $ship->captain;
		$username = $this->CI->Users->get_name_by_id($captain_id);

		/* Demasiado spam?
		$this->CI->load->library('Mapdrawer');
		$imagePath = $this->CI->mapdrawer->generateShipMap($ship);
		$img = $this->CI->telegram->prepareImage($imagePath);
		$caption = "Mostrando posición actual";
		$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
		$output = $this->CI->telegram->sendPhoto($content);
		*/

		$username = "@".$username;
		$keyboard = $this->CI->telegram->buildKeyBoard($this->CI->movement->generateKeyboard($ship), $onetime=TRUE, $resize=TRUE, $selective=TRUE);

		$text = $username ." fijad el rumbo!";

		$content = array(
			//'reply_to_message_id' => $messageId, 
			'reply_markup' => $keyboard, 
			'chat_id' => $chat_id, 
			'text' => $text
		);

		$output = $this->CI->telegram->sendMessage($content);

		$response = json_decode($output);

		if ($response->ok){
			$message_id = $response->result->message_id;
			$this->CI->Actions->create_action(array( 
				'chat_id' => $chat_id, 
				'ship_id' => $ship->id, 
				'captain_id' => $ship->captain, 
				'message_id' => $message_id,
				'command' => 'perform_mover',
				'required' => 0,
				'prev_command' => 'do_mover'
			));
		}
	}

	private function _perform_mover($msg, $ship, $params, $last_action = NULL) {
		
		/* Code to prevent cheating on command series */
		if ( $this->_isCheat($last_action, 'do_mover') ) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "realizar mover (fin) requiere haber hecho 'mover' y haber pasado la votación.");
			return $this->CI->telegram->sendMessage($content);
		}

		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		$messageId = $msg->messageId();

		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=false);

		if ($ship) {
			if ($user_id == $ship->captain ) {
				$ship = $this->CI->Ships->get_ship_by_chat_id($chat_id);

				$this->CI->load->library('Movement');
				switch ($this->CI->movement->moveShip($ship, $msg->text())) {
					case 1: // EXITO
						$this->CI->Ships->update_ship(array(
							'x'=>$ship->x,
							'y'=>$ship->y,
							'angle'=>$ship->angle
						),$ship->id); 

						$powerups = $this->CI->Powerups->ship_over_powerups($ship);
						$powerups_text = '';
						if (is_array($powerups) && count($powerups)) {
							$this->CI->calculations->consume_powerups($ship, $powerups);
							foreach ($powerups as $pwr) {
								switch ($pwr->type) {
									case 0: // shield
										$shield = ($pwr->rarity + 1) * 5;
										$powerups_text .= "Hemos ganado {$shield} puntos de escudo!\n";
										break;
									case 1: // health
										$health = ($pwr->rarity + 1) * 5;
										$powerups_text .= "Hemos ganado {$health} puntos de vida!\n";
										break;
									case 2: // points
										$score = 100 * ($pwr->rarity + 1);
										$powerups_text .= "Hemos ganado {$score} puntos para el ranking!\n";
										break;
								}
							}
						}

						$content = array(
							'reply_to_message_id' => $messageId, 
							'reply_markup' => $keyboard,
							'chat_id' => $chat_id, 
							'text' => "Fijando rumbo y coordenadas... generando impulso... \n¡SALTO COMPLETADO!"
						);

						if (!empty($powerups_text)) {
							$content['text'] .= "\n\n".$powerups_text;
						}

						$output = $this->CI->telegram->sendMessage($content);

						$this->CI->load->library('Mapdrawer');
						$imagePath = $this->CI->mapdrawer->generateShipMap($ship);
						$img = $this->CI->telegram->prepareImage($imagePath);
						$caption = "Acción /mover realizada satisfactoriamente";
						$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
						$output = $this->CI->telegram->sendPhoto($content);
						$this->CI->telegram->updateImage($imagePath, $output);

						break;
					
					case 0:
						$content = array(
							'reply_to_message_id' => $messageId, 
							'reply_markup' => $keyboard,
							'chat_id' => $chat_id, 
							'text' => "Cancelando la maniobra! Todos de vuelta a sus puestos!"
						);
						$output = $this->CI->telegram->sendMessage($content);
						break;

					case -1:
						$content = array(
							'reply_to_message_id' => $messageId, 
							'reply_markup' => $keyboard,
							'chat_id' => $chat_id, 
							'text' => "Capitán me temo que ese movimiento es totalmente imposible con el equipamiento actual. Conozco un taller en Coruscant que tal vez podría instalarnos unas piezas..."
						);
						$output = $this->CI->telegram->sendMessage($content);
						break;
				}
			} else {			
				$content = array(
					'reply_to_message_id' => $messageId, 
					'reply_markup' => $keyboard,
					'chat_id' => $chat_id, 
					'text' => "Sólo el capitán puede maniobrar la nave."
				);
				$output = $this->CI->telegram->sendMessage($content);
			}
		}
	}


	/**
	  Acción pilotar. Crea la nave en base de datos y asigna al capitán. Detecta si el capitán ya se ha fijado.
	 */
	private function _alistarse($msg, $ship=null, $params = FALSE) {
		$chat_id = $msg->chatId();
		$chat_title = $msg->chatTitle();
		$chat_title = ( !empty($chat_title) ) ? $msg->chatTitle() : ('ship-'.microtime());

		$new_player = false;
		$user_id = $msg->fromId();
		$username = $msg->fromUsername();
		$first_name = $msg->fromFirstName();

		if ($username == null){
			$content = array('chat_id' => $chat_id, 'text' => 'Para ser miembro de la tripulación tienes que configurar un username en Ajustes');						
			return $this->CI->telegram->sendMessage($content);
		}

		$joinerId = $msg->fromId();
		$joinerfromUsername = $msg->fromUsername();
		$joinerfromFirstName = $msg->fromFirstName();

		$user = $this->CI->Users->get_user($joinerId);
		if (!$user) {
			$new_player = true;
			$user = $this->CI->Users->create_user(array('id' => $joinerId, 'username' => $joinerfromUsername, 'first_name' => $joinerfromFirstName));
		}


		if ($ship == null) {
			$output = array(
				'chat_id' => $chat_id,
				'text' => "Antes debe haber un capitán, usa /pilotar para convertirte en él."
			);
			return $this->CI->telegram->sendMessage($output);
		}

		$captain = $this->CI->Users->get_user($ship->captain);

		$crew_member = $this->CI->Crew->get_crew_member($ship->id, $joinerId);
		if (empty($crew_member) ){
			if (!$this->CI->Crew->create_crew(array('ship_id' => $ship->id, 'user_id' => $joinerId))){
				$output = array(
					'chat_id' => $chat_id,
					'text' => "El usuario @".$joinerfromUsername." no ha sido añadido a la tripulación. ".
							"Es necesario que vuelvas a introducirle en el grupo para que cuente como tripulante."
				);
				return $this->CI->telegram->sendMessage($output);
			};

			$this->CI->load->library('Calculations');
			$newHealth = $this->CI->calculations->ship_health($ship, 1);

			$crew_count = $ship->total_crew + 1;
			$this->CI->Ships->update_ship(array('total_crew' => $crew_count, 'health' => $newHealth['health'], 'max_health' => $newHealth['max_health'], 'max_shield' => $newHealth['max_shield']), $ship->id);

			$outputGroup = array(
				'chat_id' => $chat_id,
				'text' => "¡Ey Capitan! @".$joinerfromUsername." ahora es un nuevo miembro de la '".$ship->name."'.\n\n".
						  "Capitan @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' ).", su nave ahora tiene ".$crew_count." miembros!"
			);
			$this->CI->telegram->sendMessage($outputGroup);
			$outputMention = array(
				'chat_id' => $joinerId,
				'text' => "@".$joinerfromUsername."! Ahora eres miembro de una nave, la '".$ship->name."'.\n".
						  "Permíteme presentarme, soy el ordenador de abordo\n.".
						  "Durante tu periplo por el espacio junto al capitan @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' )." podrás vivir aventuras trepidantes!".
						  "Recuerda estar atento a las órdenes de tu capitan, te necesita para cumplir sus objetivos.".
						  "Aunque siempre puedes fastidiarle el paseo y echarlo de su propia nave!! encuentra el cómo..."
			);
			$this->CI->telegram->sendMessage($outputMention);
		} else {
			$output = array(
				'chat_id' => $chat_id,
				'text' => "El usuario @".$joinerfromUsername." ya es miembro de la tripulación. "
			);
			return $this->CI->telegram->sendMessage($output);
		}

	}

	/**
	  Acción ranking. Muestra un ranking rudimentario. Habrá que hacerlo bonito.
	 */
	private function _ranking($msg, $ship=null, $params = FALSE) {
		$chat_id = $msg->chatId();
		$user_id = $msg->fromId();
		if ($ship) {
			if ($user_id == $ship->captain ) {
				$ships = $this->CI->Ships->order_by('score', 'DESC')->order_by('id', 'ASC')->limit(3)->where(array('active' => 1))->get_all();
				$alive_count_ships = $this->CI->Ships->where(array('active' => 1))->count();
				$text = "\xF0\x9F\x8E\xAE Top 3 de las $alive_count_ships naves operativas:\n";
				foreach ($ships as $pos => $shp) {
					$captain = $this->CI->Users->get_name_by_id($shp->captain);
					if (empty($captain)) $captain = 'Sin-piloto';
					$text .= "\n".(++$pos).") ".$shp->score."p | {$shp->name} de @{$captain}";
				}

				$text .= "\n\nTu puntuación: ".$ship->score;

				$users = $this->CI->Users->order_by('score', 'DESC')->order_by('id', 'ASC')->limit(5)->get_all();
				$text .= "\n\n\xF0\x9F\x8F\x86 Ranking de capitanes:\n";
				foreach ($users as $pos => $usr) {
					$text .= "\n".(++$pos).") @{$usr->username} con ".$usr->score."p ";
				}

				$current_user = $this->CI->Users->get_user($user_id);
				$text .= "\n\nTu puntuación: ".$current_user->score;

				$output = array(
					'chat_id' => $chat_id,
					'text' => $text
				);
				return $this->CI->telegram->sendMessage($output);
			}
		}
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
