<?php

class Mapdrawer {

	private $CI = null;
	private $ships = array();
	private $asteroids = array();
	private $size = 100;
	private $mapSize = 6;
	private $shipscount = array();

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
		//imagepng($base, APPPATH.'../imgs/map/scans/'.$timestamp.'.png');

		header('Content-Type: image/png');

		imagepng($base);
		imagedestroy($base);

		//return APPPATH.'../imgs/map/scans/'.$timestamp.'.png';
	}

	private function rotate(&$source, $degrees) {
		imagealphablending($source, false);
		imagesavealpha($source, true);
		$degrees = 360-$degrees;
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

	public function generateShipMap($mainShip, $isScan = false) {
		$centerX = $mainShip->x;
		$centerY = $mainShip->y;
		$initX = $mainShip->x -1;
		$initY = $mainShip->y -1;
		$finX = $mainShip->x +1;
		$finY = $mainShip->y +1;

		$drawnShips = array();

		$base = imagecreatefrompng(APPPATH.'../imgs/map/background.png');

		$this->markForbidden($base, $mainShip);

		$asteroids = imagecreatefrompng(APPPATH."../imgs/map/asteroids_1_1.png");
		imagecopyresampled($base, $asteroids, $this->translate($mainShip->x, 3) * $this->size, $this->remapY($this->translate($mainShip->y, 3)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		imagedestroy($asteroids);

		if ($isScan) $this->addRadar($base, $mainShip);

		$this->CI->load->model('Ships');
		$ships = $this->CI->Ships->get_target_lock_candidates($mainShip);
		
		$target = null;
		if (is_array($ships)) {
			foreach ($ships as $ship) {
				if ($ship->id != $mainShip->id) $this->addShip($base, $mainShip, $ship);
				if ($ship->id == $mainShip->target) $target = $ship;
			}
		}

		if ($mainShip->target != null) {
			if ($target != null) {
				$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/target.png");
				imagecopyresampled($base, $target_symbol, $this->translate($mainShip->x, $target->x) * $this->size, $this->remapY($this->translate($mainShip->y, $target->y)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
				imagedestroy($target_symbol);
			} else {
				$target = $this->CI->Ships->get($mainShip->target);
				if (!empty($target)) $base = $this->addTargetIndicator($base, $mainShip, $target);
			}			
		}

		$this->addShip($base, $mainShip, $mainShip);

		$this->addCounts($base, $mainShip);

		if (FALSE) {
			header('Content-Type: image/png');
			imagepng($base);
			imagedestroy($base);
		} else {
			$timestamp = date('Ymdhis');
			imagepng($base, APPPATH.'../imgs/map/scans/'.$timestamp.'.png');
			imagedestroy($base);
			return APPPATH.'../imgs/map/scans/'.$timestamp.'.png';
		}
	}

	public function addShip(&$base, $mainShip, $currentShip) {
		$this->shipscount[$currentShip->x.'-'.$currentShip->y] = isset($this->shipscount[$currentShip->x.'-'.$currentShip->y]) ? $this->shipscount[$currentShip->x.'-'.$currentShip->y]+1 : 1;

		$type = $currentShip->id % 5;
		$specialAngle = $currentShip->angle % 90 == 0;

		$item = imagecreatefrompng($specialAngle ? APPPATH."../imgs/map/ship_type{$type}.png" : APPPATH."../imgs/map/ship_type{$type}_rotated.png");
		$this->rotate($item, $specialAngle ? $currentShip->angle : ($currentShip->angle-45));
		
		imagecopyresampled($base, $item, $this->translate($mainShip->x, $currentShip->x) * $this->size, $this->remapY($this->translate($mainShip->y, $currentShip->y)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		imagedestroy($item);
	}

	private function translate($base, $value) {
		$transform = 1 - $base;
		return $value + $transform;
	}

	private function remapY($value) {
		$y = array(0=>2,1=>1,2=>0);
		return isset($y[$value])?$y[$value]:-1;
	}

	private function markForbidden(&$base, $ship) {
		$forbidden = imagecreatefrompng(APPPATH."../imgs/map/forbidden.png");
		if ($ship->y == 1) {
			imagecopyresampled($base, $forbidden, 0 * $this->size, 2 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 1 * $this->size, 2 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 2 * $this->size, 2 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		}
		if ($ship->y == $this->mapSize) {
			imagecopyresampled($base, $forbidden, 0 * $this->size, 0 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 1 * $this->size, 0 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 2 * $this->size, 0 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		}
		if ($ship->x == 1) {
			imagecopyresampled($base, $forbidden, 0 * $this->size, 0 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 0 * $this->size, 1 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 0 * $this->size, 2 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		}
		if ($ship->x == $this->mapSize) {
			imagecopyresampled($base, $forbidden, 2 * $this->size, 0 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 2 * $this->size, 1 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			imagecopyresampled($base, $forbidden, 2 * $this->size, 2 * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		}

		imagedestroy($forbidden);
	}

	private function addTargetIndicator($base, $ship, $target) {
		$x = 0;
		$y = 0;
		if ($ship->x == $target->x) {
			$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/target_moved.png");
			if ($ship->y > $target->y) {
				$x = 1;
				$y = 2;
				$this->rotate($target_symbol, 180);
			} else {
				$x = 1;
				$y = 0;
			}
		} else if ($ship->y == $target->y) {
			$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/target_moved.png");
			if ($ship->x > $target->x) {
				$x = 0;
				$y = 1;
				$this->rotate($target_symbol, 270);
			} else {
				$x = 2;
				$y = 1;
				$this->rotate($target_symbol, 90);
			}
		} else {
			$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/target_moved_rotated.png");
			if ($ship->x > $target->x) {
				if ($ship->y > $target->y) {
					$x = 0;
					$y = 2;
					$this->rotate($target_symbol, 180);
				} else {
					$x = 0;
					$y = 0;
					$this->rotate($target_symbol, 270);
				}
			} else {
				if ($ship->y > $target->y) {
					$x = 2;
					$y = 2;
					$this->rotate($target_symbol, 90);
				} else {
					$x = 2;
					$y = 0;
				}
			}
		}

		imagecopyresampled($base, $target_symbol, $x * $this->size, $y * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		imagedestroy($target_symbol);

		return $base;
	}

	private function addCounts(&$base, $mainShip) {
		foreach ($this->shipscount as $key => $value) {
			if ($value > 1) {
				$parts = explode('-', $key);
				if ($value > 4) $value = 5;
				$item = imagecreatefrompng(APPPATH."../imgs/map/count{$value}.png");
				imagecopyresampled($base, $item, $this->translate($mainShip->x, $parts[0]) * $this->size, $this->remapY($this->translate($mainShip->y, $parts[1])) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
				imagedestroy($item);
			}
		}
	}

	private function addRadar(&$base, $mainShip) {
		$specialAngle = $mainShip->angle % 90 == 0;
		$radar = imagecreatefrompng($specialAngle ? APPPATH."../imgs/map/radar.png" : APPPATH."../imgs/map/radar_rotated.png");

		$this->rotate($radar, $specialAngle ? $mainShip->angle : ($mainShip->angle-45));

		imagecopyresampled($base, $radar, 0, 0, 0, 0, 300, 300, 300, 300);
		imagedestroy($radar);
	}

}