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

}