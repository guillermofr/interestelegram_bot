<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{

		$this->config->load('bot');

		$botToken = $this->config->item('botToken');
		$website = "https://api.telegram.org/bot".$botToken;

		//$last_id = intval(file_get_contents('./lastid.txt'))+1;
		$last_id = 1;

		$update = file_get_contents($website."/getupdates?offset=$last_id");

		//cuando se cambie a servidor SSL hay que poner esto
		//$update = file_get_contents("php://input");
		//y no hace falta recorrer los mensajes porque te mandan un post por mensaje

		echo "<pre>";
		print_r(json_decode($update));
		$data  = json_decode($update);

		/* 

		//esto es un data de ejemplo

		stdClass Object
		(
		    [ok] => 1
		    [result] => Array
		        (
		            [0] => stdClass Object
		                (
		                    [update_id] => 911242039
		                    [message] => stdClass Object
		                        (
		                            [message_id] => 63
		                            [from] => stdClass Object
		                                (
		                                    [id] => 8908013
		                                    [first_name] => Guillermo - Killer
		                                    [username] => killer415
		                                )

		                            [chat] => stdClass Object
		                                (
		                                    [id] => -12658615
		                                    [title] => Airsoft partidas club
		                                )

		                            [date] => 1441465223
		                            [text] => /crearpartida
		                        )

		                )

		*/

		foreach ($data->result as $key => $msg){

			echo "Analizando mensaje $key<br>";

			//datos grupo
			$in_group = !isset($msg->message->chat->username);
			$last_update_id = $msg->update_id;

			//solo permitimos hablar en grupo		
			//if (!$in_group) continue;
			//echo "mensaje a grupo :". intval($in_group) ."<br>";

			//datos mensaje
			$group_id 	= $msg->message->chat->id;
			$from_id	= $msg->message->from->id;
			$from_username	= $msg->message->from->username;
			$text 		= $msg->message->text;

			//lista de comandos disponibles
			$commands = array(
					'crearpartida' => '/(\/crearpartida)$/',
					'cancelarpartida' => '/(\/cancelarpartida)$/',
					'infopartida' => '/(\/infopartida)$/',
				);

				$matches = null;
				foreach ($commands as $commandkey => $commandexp){

					$returnValue = preg_match($commandexp, $text, $matches);

					//print_r($matches);

					if (!empty($matches)) {

						switch($commandkey){
	
							case "crearpartida":
								echo "comando $commandkey<br>";
								file_get_contents($website."/sendMessage?chat_id=$group_id&text=@$from_username: comando crearpartida leído");
							break;

							case "cancelarpartida":
								echo "comando $commandkey<br>";
								file_get_contents($website."/sendMessage?chat_id=$group_id&text=@$from_username: comando cerrarpartida leído.");
							break;

							case "infopartida":
								echo "comando $commandkey<br>";
								file_get_contents($website."/sendMessage?chat_id=$group_id&text=@$from_username: comando infopartida leído.");
							break;


						}

					}

					$matches = null;
					//$last_id = file_put_contents('./lastid.txt',$last_update_id);

				}


		}


	}
}
