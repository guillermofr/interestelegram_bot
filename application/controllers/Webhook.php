<?php

class Webhook extends CI_Controller
{
		public $lastUpdate = 0;
		public $refreshMillis = 2000;

        public function index($lastUpdate=0)
        {
        	$this->lastUpdate = $lastUpdate;

            $this->config->load('bot');
			$params = array(
				$this->config->item('botToken')
			);

			$this->load->library('Telegram', $params);

			$output = $this->telegram->getUpdates($this->lastUpdate);

			$this->load->library('Processor');

			echo '<pre>';
			$message = array_shift($output['result']);

			$this->lastUpdate = $message['update_id'] + 1;
			echo print_r($message, TRUE);

			$chat_id = $message['message']['chat']['id'];
			$content = array('chat_id' => $chat_id, 'text' => 'La Beta ha finalizado. Para seguir jugando, echa a este bot e invita a @interestelegram_bot !');						
			$output = $this->telegram->sendMessage($content);

			//$this->processor->process($message);

			echo '<script>setTimeout(function(){ window.location = \''.$this->config->item('botPath').'/index.php/webhook/index/'.$this->lastUpdate.'\'; }, '.$this->refreshMillis.');</script>';
        }

        public function hook()
        {
        	try {
        		$rawData = file_get_contents("php://input");
	        	$message = json_decode($rawData, TRUE);

	        	$params = array(
					'APIKEY'
				);
				$this->load->library('Telegram', $params);
	        	$chat_id = $message['message']['chat']['id'];
				$content = array('chat_id' => $chat_id, 'text' => 'La Beta ha finalizado. Para seguir jugando, echa a este bot e invita a @interestelegram_bot !');						
				$output = $this->telegram->sendMessage($content);
        	} catch (Exception $e) {
        		log_message('error', $e->getMessage());
        	}
        	
        }

        public function hook2()
        {
        	try {
        		$rawData = file_get_contents("php://input");
	        	$message = json_decode($rawData, TRUE);
	        	$this->load->library('Processor');
	        	$this->processor->process($message);
        	} catch (Exception $e) {
        		log_message('error', $e->getMessage());
        	}
        	
        }

        public function sethook()
        {
        	$this->config->load('bot');
			$params = array(
				$this->config->item('botToken')
			);

			$this->load->library('Telegram', $params);

			$output = $this->telegram->setWebhook('https://'.$_SERVER['SERVER_NAME'].'/index.php/webhook/hook2', $this->telegram->prepareCert(APPPATH.'../cert/YOURPUBLIC.pem'));
        }

}