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
		//$this->shipsMining();
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

		foreach ($types as $type) {
			$count = $this->Powerups->count_by_type($type);
			if (!isset($count[0]['count'])) $count = 0;
			else $count = intval($count[0]['count']);
			if ($count < 2) {
				for ($i=$count; $i < 2; $i++) { 
					$this->Powerups->insert(array('x' => rand(1, MAP_SIZE), 'y' => rand(1, MAP_SIZE), 'type' => $type));
				}
			}
		}
	}


	/*private function shipsMining(){
		//para cada asteroide que exista 

		//buscamos los players que están en las mismas coordenadas 

		//le sumamos 1 a la carga de minerales, comprobando que no se pase del max de la carga
	}*/


}