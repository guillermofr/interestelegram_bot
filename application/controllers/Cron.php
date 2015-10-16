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
}