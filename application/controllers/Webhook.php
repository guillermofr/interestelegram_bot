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

			$this->processor->process($message);

			echo '<script>setTimeout(function(){ window.location = \''.$this->config->item('botPath').'/index.php/webhook/index/'.$this->lastUpdate.'\'; }, '.$this->refreshMillis.');</script>';
        }

        public function hook()
        {
        	log_message('error', 'got hook');
        	$rawData = file_get_contents("php://input");
        	$message = json_decode($rawData, TRUE);
        	$this->load->library('Processor');
        	$this->processor->process($message);
        }

        public function sethook()
        {
        	$this->config->load('bot');
			$params = array(
				$this->config->item('botToken')
			);

			$this->load->library('Telegram', $params);

			$output = $this->telegram->setWebhook('https://'.$_SERVER['SERVER_NAME'].'/index.php/webhook/hook', $this->telegram->prepareCert(APPPATH.'../certs/YOURPUBLIC.pem'));
			log_message('error', print_r($output, true));
			log_message('error', 'is_file?'.is_file(APPPATH.'../certs/YOURPUBLIC.pem'));
        }

}