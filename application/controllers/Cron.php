<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {

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
		$this->moveAsteroids();
		$this->addPowerups();
		$this->addMinerals();
		$this->obtainMinerals();
	}

	private function moveAsteroids() {
		$this->load->model('Asteroids');

		$timestamp = date('U');

		$minTimePast = 60*60*4;

		$asteroids = $this->Asteroids->select_moved_since($timestamp-$minTimePast);

		if (is_array($asteroids)) {
			$this->load->library('Calculations');
			foreach ($asteroids as $asteroid) {
				$hours = 0;
				if ($asteroid['timestamp'] != null) $hours = ($timestamp - $asteroid['timestamp']) / 60*60;
				if ($this->calculations->moveAsteroid($hours)) {
					$x = rand(-1,1);
					$y = rand(-1,1);

					$x = $asteroid['x'] + $x;
					if ($x <= 0) $x = MAP_SIZE;
					if ($x > MAP_SIZE) $x = 1;
					$y = $asteroid['y'] + $y;
					if ($y <= 0) $y = MAP_SIZE;
					if ($y > MAP_SIZE) $y = 1;

					$this->Asteroids->update(array(
							'x' => $x,
							'y' => $y,
							'timestamp' => $timestamp
						), $asteroid['id']);
				}
			}
		}
	}

	private function addPowerups() {
		$this->load->model('Powerups');

		$timestamp = date('i');

		if ($timestamp % 10 != 0) return;

		$types = array(0,1,2);
		$amount = 2;  //esto debería ir en un config

		foreach ($types as $type) {
			$count = $this->Powerups->count_by_type($type);
			if (!isset($count[0]['count'])) $count = 0;
			else $count = intval($count[0]['count']);
			if ($count < $amount) {
				for ($i=$count; $i < $amount; $i++) { 
					$this->Powerups->insert(array('x' => rand(1, MAP_SIZE), 'y' => rand(1, MAP_SIZE), 'type' => $type));
				}
			}
		}
	}

	private function addMinerals() {
		$this->load->model('Minerals');

		$types = array(0);
		$amount = 4;  //esto debería ir en un config

		foreach ($types as $type) {
			$count = $this->Minerals->count_by_type($type);
			if (!isset($count[0]['count'])) $count = 0;
			else $count = intval($count[0]['count']);
			if ($count < $amount) {
				for ($i=$count; $i < $amount; $i++) { 
					$this->Minerals->insert(array('x' => rand(1, MAP_SIZE), 'y' => rand(1, MAP_SIZE), 'type' => $type));
				}
			}
		}
	}


	private function obtainMinerals(){
		$this->load->model('Minerals');
		$this->load->model('Ships');
		$this->load->library('Calculations');
		$minerals = $this->Minerals->get_all();

		$MineralShips = $this->calculations->obtainMinerals($minerals);

		//$this->config->load('bot');
		//$params = array( $this->config->item('botToken') );
		//$this->load->library('Telegram', $params);

		foreach ($MineralShips['full'] as $user) {
			$output = array(
				'chat_id' => $user->chat_id,
				'text' => "\xF0\x9F\x94\x9A\xF0\x9F\x92\x8E You reach the limit of interestelegraminium, Go to the shop to change minerals for money."
			);
			//$this->telegram->sendMessage($output);

			$this->Ships->update(array('dont_remind_full_minerals' => 1),$user->id);


		}

		foreach ($MineralShips['empty'] as $user) {
			$output = array(
				'chat_id' => $user->chat_id,
				'text' => "\xF0\x9F\x92\x8E\xE2\x9D\x95 You have obtained 1 interestelegraminium, change it for money at shop."
			);
			//$this->telegram->sendMessage($output);
		}

	}

}