<?php

class Mapdrawer {

	private $CI = null;
	private $ships = array();
	private $asteroids = array();
	private $size = 100;

	public function __construct() {
		$this->CI =& get_instance();

		if (!is_dir(APPPATH.'../imgs/map/scans')) {
			$oldumask = umask(0); 
			try {
				mkdir(APPPATH.'../imgs/map/scans', 0777);
			} catch (Exception $e) {
				throw new Exception("Can not create imgs/map/scans directory, permission denied", 1);
			}
			umask($oldumask);
		}
	}

	public function setShips($ships) {
		$this->ships = $ships;
	}

	public function setAsteroids($asteroids) {
		$this->asteroids = $asteroids;
	}

	public function generateMap() {
		$base = imagecreatefrompng(APPPATH.'../imgs/map/background.png');

		foreach ($this->asteroids as $asteroid) {
			$item = imagecreatefrompng(APPPATH."../imgs/map/asteroids_{$asteroid->type}_{$asteroid->size}.png");
			imagecopyresampled($base, $item, $asteroid->x * $this->size, $asteroid->y * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagedestroy($item);
		}

		foreach ($this->ships as $ship) {
			$item = imagecreatefrompng(APPPATH."../imgs/map/ship_{$ship->type}.png");
			$this->rotate($item, $ship->angle);
			imagecopyresampled($base, $item, $ship->x * $this->size, $ship->y * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagedestroy($item);
		}

		$timestamp = date('Ymdhis');
		imagepng($base, APPPATH.'../imgs/map/scans/'.$timestamp.'.png');

		return APPPATH.'../imgs/map/scans/'.$timestamp.'.png';
	}

	private function rotate(&$source, $degrees) {
		imagealphablending($source, false);
		imagesavealpha($source, true);
		$source = imagerotate($source, $degrees, imageColorAllocateAlpha($source, 0, 0, 0, 127));
		imagealphablending($source, false);
		imagesavealpha($source, true);
	}

	public function __random() {
		$this->asteroids = array(
			(Object) array(
				'type' => rand(1,2),
				'size' => 1,
				'x' => rand(0,2),
				'y' => rand(0,2)
			)
		);
		$angles = array(90, 180, 270, 0);
		$this->ships = array(
			(Object) array(
				'type' => 1,
				'x' => 1,
				'y' => 1,
				'angle' => $angles[rand(0,3)]
			),
			(Object) array(
				'type' => rand(2,3),
				'x' => rand(0,2),
				'y' => rand(0,2),
				'angle' => $angles[rand(0,3)]
			)
		);
	}

}