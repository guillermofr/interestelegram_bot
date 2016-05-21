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
							'Powerups',
							'Minerals'));
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
			return $this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'The command "'.$method.'" does not exists.'));
		}

		if (in_array($method, $this->captain_methods) && ( is_null($ship) || $ship->captain != $msg->fromId() ) ){
			return $this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => 'The command "'.$method.'" does not exists.'));
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
						"Hola...? ¿Hello...? I am the IA...\n".
						"¿ someone live ? Please type /pilotar to be captain. The crew type /alistarse to support your captain at combat.\n".
						"If you nedd more info , type /ayuda"
			);
			return $this->CI->telegram->sendMessage($output);
		}


		/* prevent invalid joins */
		if ($msg->isInvalidJoin()){
			$output = array(
				'chat_id' => $joiner->id,
				'text' => 'To use '.$this->botUsername.' you need an alias on telegram.'
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
				'text' => "We need a captain before creating new crew: someone have to type /pilotar !"
			);
			return $this->CI->telegram->sendMessage($output);
		}

		$captain = $this->CI->Users->get_user($ship->captain);
		$crew_member = $this->CI->Crew->get_crew_member($ship->id, $joiner->id);
		if (empty($crew_member) )
			if (!$this->CI->Crew->create_crew(array('ship_id' => $ship->id, 'user_id' => $joiner->id))){
				$output = array(
					'chat_id' => $chat_id,
					'text' => "User @".$joiner->username." have not added to crew. ".
							"You need to be invited again to group to be part of the crew."
				);
				return $this->CI->telegram->sendMessage($output);
			};

		$this->CI->load->library('Calculations');
		$newHealth = $this->CI->calculations->ship_health($ship, 1);

		$crew_count = $ship->total_crew + 1;
		$this->CI->Ships->update_ship(array( 'total_crew' => $crew_count, 'health' => $newHealth['health'], 'max_health' => $newHealth['max_health'], 'max_shield' => $newHealth['max_shield'] ), $ship->id);

		$outputGroup = array(
			'chat_id' => $chat_id,
			'text' => "¡Ey Captain! @".$joiner->username." is a new member of the '".$ship->name."'.\n\n".
					  "Captain @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' ).", your crew have ".$crew_count." members!"
		);
		$this->CI->telegram->sendMessage($outputGroup);
		$outputMention = array(
			'chat_id' => $joiner->id,
			'text' => "@".$joiner->username."! you are a new crew member of the ship '".$ship->name."'.\n".
					  "Let me introduce myself first, im the Artificial intelligence of this ship\n.".
					  "Your mission is to obey the captain @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' )." orders to stay alive!\n".
					  "Im glad to have you here, more people, more fun!"
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
				'text' => "@".( isset($captain->username) ? $captain->username : 'no-hay-capitan' )." has deleted the control IA of the '".$ship->name."'.\n".
						"This is a dissaster!! the ship is totally disabled.\n".
						"And all your crew will die soon... so sad...\n".
						"Ehm, well... ".$ship->total_crew." dead people in a tin can.\n"
			);
			return $this->CI->telegram->sendMessage($output);
			
		}

		/* prevent invalid leave */
		if ($msg->isInvalidLeave()){
			$output = array(
				'chat_id' => $joiner->id,
				'text' => 'To play '.$this->botUsername.' you need to set an alias on Telegram.'
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
				'text' => "¡Oh God! ¡Oh my God! There is no captain!.\n\n".
						  "Please someone of the crew have to take control of the spaceship, type '/pilotar'\n\n".
						  "We all miss you @".$leaver->username.". Forever..."
			);
			$this->CI->telegram->sendMessage($output);
		}
		else {
			$outputGroup = array(
				'chat_id' => $chat_id,
				'text' => "¡Ey Captain! @".$leaver->username." opens the worng door and now is floating in space.\n\n".
						  "Captain @".( isset($captain->username) ? $captain->username : 'no-hay-capitan' ).", your ship has now ".$crew_count." members!"
			);
			$this->CI->telegram->sendMessage($outputGroup);
			$outputMention = array(
				'chat_id' => $leaver->id,
				'text' => "@".$leaver->username."! you are out of the starship '".$ship->name."'".
						  " you will not receive future messages of that ship.\n\n".
						  "Did you leave your towel at your room?"
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
					'text' => "You are now part of the crew, your mission aboard is help your captain with the actions. The captain is useless without your help, so you are very important in this game."
				);
			}
			else {			
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "Captain!, you are now the pilot of the starship. Your goal is to survive in space, watch out for pirates!\n".
					"You have to know some rules:\n".
					"Each person you add to your crew, will increase your starship's stats, like life and shield, but will decrease your flee against attacks\n".
					"Actions require your crew participation, some actions like move can be done only by captain if the starship is small, but will be dependant on crew if your starship is bigger. Same with attacks.\n".
					"You can get your ship info with /informe and also check your environment with /escanear to lock on your enemies and pursue them.\n".
					"Your ship can only atack to targets placed on your red arc of fire, also can attack enemies on your same map tile. Watch out for asteroids, people can hide there."
				);
				$output = $this->CI->telegram->sendMessage($content);
				$content = array(
					'chat_id' => $chat_id, 
					'text' => "To attack other people's starships, you have to target it before use /atacar_1 /atacar_2 or /a1 /a2 where the number is the power of your attack\n".
					"Remember that your attack have a probability of fail, higher with smaller starships\n".
					"If you are the target of your enemy, your only way to avoid their attacks is typing the command /esquivar, if your command is successfull your enemy will have to target you again before shoot\n".
					"If you have people in your telegram group that is not part of the crew, they have to type /alistarse to take part of the crew\n".
					"Easy tip is to type / and telegram will show you all available commands"
				);
			}

		} else {
			$content = array('chat_id' => $chat_id, 'text' => 
				"Welcome to INTERESTELEGRAM , a game about starship battles\n 
				You have to ways to play:\n
				SINGLEPLAYER: Just talk directly to bot and type /pilotar. Bot will give you a 1-player starship to play with\n
				COOPERATIVE: Create a Telegram group, and invite the interestelegram_bot to join. The bot will transform your group into a Starship where you can invite all people you want as a crew, remember type /pilotar before you invite your frieds, because first to type that will become the captain!\n
				");
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
		$chat_title = ( !empty($chat_title) ) ? $chat_title : ('nave de '.$msg->fromUsername());
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

				$content = array('chat_id' => $chat_id, 'text' => 'Ascending @'.$username.' to captain');
				$output = $this->CI->telegram->sendMessage($content);

				$this->CI->load->library('Mapdrawer');
				$imagePath = $this->CI->mapdrawer->generateShipMap($ship);
				$img = $this->CI->telegram->prepareImage($imagePath);
				
				$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => 'The starship "'.$chat_title.'" has taken off with only one person, the awesome captain '.$username.".\n\nGood luck!");
				$output = $this->CI->telegram->sendPhoto($content);

				$this->CI->telegram->updateImage($imagePath, $output);
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'You need an alias on Telegram to be Captain');						
				$output = $this->CI->telegram->sendMessage($content);
			}
		} else {
			
			$captain = ( is_null($ship->captain) || $ship->captain == 0 ) ? null : $this->CI->Users->get_user($ship->captain);

			if ($user_id != $ship->captain) {

				if (empty($captain)) {
					$this->CI->Ships->update_ship(array('captain' => $user_id), $ship->id);

					$content = array('chat_id' => $chat_id, 'text' => 'Ascending @'.$username.' to captain');
					$output = $this->CI->telegram->sendMessage($content);
				}
				else {
					$content = array('chat_id' => $chat_id, 'text' => 'This ship "'.$chat_title.'" has already a captain, the captain '.( isset($captain->username) ? $captain->username : 'no-hay-capitan' ));						
					$output = $this->CI->telegram->sendMessage($content);	
				}
			} else {
				$content = array('chat_id' => $chat_id, 'text' => 'Captain, you already pilot "'.$chat_title.'". Im at your command!');						
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
			$option = $this->CI->calculations->trollKeyboardV1();
			$text = "The captain commanded a scan for enemies. Do you agree?";

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
			$text = "Only the captain can perform a scan";
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
			$content = array('chat_id' => $msg->chatId(), 'text' => "You need the command 'escanear' before this.");
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
				if (empty($captain_name)) $captain_name = "No captain";

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
			$nearShips[] = "None";

			$nearShipsDetailString = "";
			foreach ($nearShipsDetail as $n) $nearShipsDetailString .= "\n".$n;

			$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "List of enemies:\n".$nearShipsDetailString));

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
			$text = "Select a target @". $username ." :";

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
			$this->CI->telegram->sendMessage(array('chat_id' => $msg->chatId(), 'text' => "There is no one near!"));
		}
		
	}


	private function _do_seleccionar($msg, $ship, $params, $last_action = null) {

		/* Code to prevent cheating on command series */
		if ( $this->_isCheat($last_action, 'do_escanear') ) {
			$content = array('chat_id' => $msg->chatId(), 'text' => "'seleccionar' requires the command 'escanear' and a vote passed.");
			return $this->CI->telegram->sendMessage($content);
		}

		$messageId = $msg->messageId();
		$username = "@".$msg->fromUsername();
		$chat_id = $msg->chatId();
		$keyboard = $this->CI->telegram->buildKeyBoardHide($selective=TRUE);
		
		$target = explode("@",$msg->text());
		if (!isset($target[1])){
			$text = $username ." the target is unreachable...";
		} else {
			
			$targetShip = $this->CI->Ships->get_ship($target[0]);
			$sectorShips = $this->CI->Ships->get_target_lock_candidates($ship);

			if (in_array($targetShip, $sectorShips)){
				$this->CI->Ships->update_ship(array('target'=>$targetShip->id),$ship->id); 

				// Avisar al objetivo targeteado
				$this->CI->telegram->sendMessage(array('chat_id' => $targetShip->chat_id, 'text' => "\xE2\x9A\xA0 WARNING!, $username spaceship has lock on you! Use /esquivar to flee their target."));
				
				$text = $username ." we lock on ".$target[1]. " successfully, ready to /atacar";
			} else {
				// La nave no está en rango
				$text = $username ." , ".$target[1]. " spaceship is too far for a lock on.";
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
				$text = "Captain! you need to type the power of the attack ( /atacar_1 , /atacar_5 ... ) o con ( /a1 , /a2 , /a5 ... )";
				$content = array(
					'reply_to_message_id' => $messageId, 
					'chat_id' => $chat_id, 
					'text' => $text
				);
				$output = $this->CI->telegram->sendMessage($content);
			} else {
				if ($this->CI->Ships->can_i_attack($ship)) {

					$option = $this->CI->calculations->trollKeyboardV1();
					$text = "Captain commanded fire rockets lv. ".$param." ¿Do you agree?";

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
					$text = $ship->target == null ? "Captain we lost our target, use /escanear" : "Captain your target is not on range of fire!";
					$content = array(
						'reply_to_message_id' => $messageId, 
						'chat_id' => $chat_id, 
						'text' => $text
					);
					$output = $this->CI->telegram->sendMessage($content);
				}
			}
		} else {
			$text = "Only the captain can attack";
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
			$content = array('chat_id' => $msg->chatId(), 'text' => "Attack recuires command 'atacar' y and successfully vote.");
			return $this->CI->telegram->sendMessage($content);
		}

		$chat_id = $msg->chatId();

		$quantity = $last_action->required == 1 ? 'one laser' : $last_action->required.' lasers';
		$imagePath = APPPATH.'../imgs/attack.png';
		$img = $this->CI->telegram->prepareImage($imagePath);

		$caption = "Attacking with ".$quantity." of proton!";
		$content = array('chat_id' => $chat_id, 'photo' => $img, 'caption' => $caption );
		$output = $this->CI->telegram->sendPhoto($content);

		$this->CI->telegram->updateImage($imagePath, $output);

		$target_ship = $this->CI->Ships->get($ship->target);
		if ($ship->target != null && $this->CI->calculations->attack_success($ship, $target_ship)) {
			$target_ship = $this->CI->Ships->deal_damage($target_ship, $last_action->required);

			$text = "HIT!!!";
			$text .= "\nTarget status:".
				"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
				"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield;
			
			$target_text = "\xF0\x9F\x94\xA5 WARNING! La ".$ship->name.' de @'.$this->CI->Users->get_name_by_id($ship->captain).' has reached us with its attack!!';
			$target_text .= "\nShip status:".
				"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
				"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield;

			if ($target_ship->health == 0) {

				//calcular ranking
				$score = 500 + intval(($target_ship->score - $ship->score)/5);
				if ($score < 50) $score = 50;
				//recover cargo and moneyz
				$cargo = ceil($target_ship->minerals / 3);
				$ship->minerals += $cargo;
				if ($ship->minerals > $ship->max_minerals) $ship->minerals = $ship->max_minerals;
				$moneyz = ceil($target_ship->money / 3);
				
				$this->CI->Ships->update_ship(array('score' => $ship->score + $score, 'target' => null, 'minerals' => $ship->minerals, 'money' => $ship->money + $moneyz), $ship->id);
				$this->CI->Ships->update(array('target' => null), array('target' => $target_ship->id)); // remove target from any other ship
				$playerScore = $target_ship->score - 1000;
				$pilot = $this->CI->Users->get_user($target_ship->captain);
				$this->CI->Users->update_user(array('score' => $pilot->score + $playerScore), $target_ship->captain);

			 	$text = "FINAL HIT!!!";
				$text .= "\nEnemy was destroyed!:".
					"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
					"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield.
					"\n\nWe gain +".$score." points!".
					(($cargo>0)?("\nYou obtain +".$cargo." minerals!"):'').
					(($moneyz>0)?("\nYou obtain +".$moneyz." money!"):'');


			 	$target_text = "\xF0\x9F\x92\x80 WARNING! ".$ship->name.' of @'.$this->CI->Users->get_name_by_id($ship->captain).' has destroyed you!!';
				$target_text .= "\nYour ship is:".
					"\n\xE2\x9D\xA4: ".$target_ship->health."/".$target_ship->max_health.
					"\n\xF0\x9F\x94\xB5: ".$target_ship->shield."/".$target_ship->max_shield.
					"\nWas a pity that your adventure ends here! 
					\n(BETATESTERS: If you want revenge, you can play again typing /pilotar , your crew have to type /alistarse also to be part of the crew)" ;

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
			$text = "Attack missed!";
			$target_text = "\xE2\x9A\xA0 WARNING! ".$ship->name.' of @'.$this->CI->Users->get_name_by_id($ship->captain).' has failed the attack agains us!';
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
				$caption = "Starship info:".
							"\n\xF0\x9F\x9A\x80: ".$Ship->name.
							$target.
							"\n\xE2\x9D\xA4: ".$Ship->health."/".$Ship->max_health.
							"\n\xF0\x9F\x94\xB5: ".$Ship->shield."/".$Ship->max_shield.
							"\n\xF0\x9F\x92\xB0: ".$Ship->money.
							"\n\xF0\x9F\x92\x8E: ".$Ship->minerals."/".$Ship->max_minerals.
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
					'text' => "Only captain can get the report."
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
				$text = "Any has targeted you , what are you trying to avoid?...";
				$content = array(
					'reply_to_message_id' => $messageId, 
					'chat_id' => $chat_id, 
					'text' => $text
				);
				return $this->CI->telegram->sendMessage($content);
			}

			$option = $this->CI->calculations->trollKeyboardV1();
			$text = "Captains is trying to avoid $needDodge starship".(($needDodge==1)?"":"s")." ¿Do you agree?";

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
			$text = "Only the captain can do that";
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
			$content = array('chat_id' => $msg->chatId(), 'text' => "You need to do esquivar to do that.");
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
			$option = $this->CI->calculations->trollKeyboardV1();
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


						//powerups
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

						//minerals
						$minerals = $this->CI->Minerals->ship_over_minerals($ship);
						$minerals_text = '';
						if (is_array($minerals) && count($minerals)) {
							foreach ($minerals as $mineral) {
								switch ($mineral->type) {
									case 0: // interestelegraminium
										$minerals_text .= "\xF0\x9F\x92\x8E\xF0\x9F\x9A\xBF Has empezado a minar un asteroide de interestelegraminium!\n";
										break;
								}
							}
						}

						$starport = $this->CI->Minerals->ship_over_starport($ship);
						$starport_text = '';
						if ($starport) {
							$starport_text .= "Aterrizas en el comercio de minerales!\n";
							$obtainedMoney = $this->CI->Ships->vender_todo($ship);
							if ($obtainedMoney){
								$starport_text .= "Has cambiado tus minerales por $obtainedMoney dineros!\n";
							} else {
								$starport_text .= "Aquí parece que pagan si les traes minerales de interestelegraminium, busca unos cuantos y vuelve.\n";
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
						if (!empty($minerals_text)) {
							$content['text'] .= "\n\n".$minerals_text;
						}
						if (!empty($starport_text)) {
							$content['text'] .= "\n\n".$starport_text;
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

		$option = $this->CI->calculations->trollKeyboardV1();
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
