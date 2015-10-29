<?php

/**
 * Mapdrawer class by Telemako for Interestelegram
 * 
 * In the game, ships will be held in a square defined by the coordinates 1,1 - mapSize,mapSize
 * The game sprites are created in two forms, straight, pointing top, and rotated, pointing 45 degrees clockwise.
 * PNG rotations of less than 90 degrees are blurry, so we rotate 90 degrees the rotated sprites, to avoid losing quality.
 * 
 */
class Mapdrawer {

	private $CI = null;
	private $ships = array();
	private $asteroids = array();
	private $size = 100;
	private $mapSize = MAP_SIZE;
	private $shipscount = array();

	/**
	 * Contructs the library. Creates the folder that will hold the images.
	 */
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

	/**
	 * Rotates an image any degrees. Use always with 90º multipliers to avoid quality loss.
	 */
	private function rotate(&$source, $degrees) {
		imagealphablending($source, false);
		imagesavealpha($source, true);
		$degrees = 360-$degrees;
		$source = imagerotate($source, $degrees, imageColorAllocateAlpha($source, 0, 0, 0, 127));
		imagealphablending($source, false);
		imagesavealpha($source, true);
	}

	/**
	 * Draws a sprite in a cell, related to the position to the main ship
	 */
	private function addSquare(&$base, $mainShip, $path, $squared) {
		if (is_string($path)) {
			if (!is_file($path)) {
				log_message('error', 'File does not exist: '.$path);
				return;
			}
			$item = imagecreatefrompng($path);
		} else $item = $path;
		if (isset($squared->angle)) {
			$specialAngle = $squared->angle % 90 == 0;
			$this->rotate($item, $specialAngle ? $squared->angle : ($squared->angle-45));
		}
		imagecopyresampled($base, $item, $this->translate($mainShip->x, $squared->x) * $this->size, $this->remapY($this->translate($mainShip->y, $squared->y)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		imagedestroy($item);
	}

	/**
	 * Draws a sprite (file in $path) in a cell, related to the position to the main ship and to the given coordinates
	 */
	private function addSquareXY(&$base, $mainShip, $path, $x, $y, $angle = null) {
		if (is_string($path)) {
			if (!is_file($path)) {
				log_message('error', 'File does not exist: '.$path);
				return;
			}
			$item = imagecreatefrompng($path);
		} else $item = $path;
		if ($angle != null) {
			$specialAngle = $angle % 90 == 0;
			$this->rotate($item, $specialAngle ? $angle : ($angle-45));
		}
		imagecopyresampled($base, $item, $this->translate($mainShip->x, $x) * $this->size, $this->remapY($this->translate($mainShip->y, $y)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		imagedestroy($item);
	}

	private function addSquareXYSize(&$base, $mainShip, $path, $x, $y, $angle = null, $offset = null) {
		if (is_string($path)) {
			if (!is_file($path)) {
				log_message('error', 'File does not exist: '.$path);
				return;
			}
			$item = imagecreatefrompng($path);
		} else $item = $path;
		if ($angle != null) {
			$specialAngle = $angle % 90 == 0;
			$this->rotate($item, $specialAngle ? $angle : ($angle-45));
		}
		if ($offset != null) {
			$size = $this->size + 2*$offset;
			imagecopyresampled($base, $item, ($this->translate($mainShip->x, $x) * $this->size)-$offset, ($this->remapY($this->translate($mainShip->y, $y)) * $this->size)-$offset, 0, 0, $size, $size, $size, $size);
		} else {
			imagecopyresampled($base, $item, $this->translate($mainShip->x, $x) * $this->size, $this->remapY($this->translate($mainShip->y, $y)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		}

		imagedestroy($item);
	}

	/**
	 * Generates a map relative to a ship that will be placed in the center
	 * The ship targeted by this ship will be highlighted too
	 * 
	 * TODO: $range does nothing at the moment
	 */
	public function generateShipMap($mainShip, $isScan = false, $isDead = false) {
		$debug = false;
		$this->CI->load->model('Images_cache');
		$data = $this->prepareData($mainShip);
		$data['scan'] = $isScan ? 1 : 0;
		$data['dead'] = $isDead ? 1 : 0;

		$data = json_encode($data);
		$dataHash = md5($data);

		$scanPath = APPPATH.'../imgs/map/scans/'.$dataHash.'.png';

		$cache = $this->CI->Images_cache->get_by_path($scanPath);
		if (!$debug && $cache != null && is_object($cache) && !empty($cache->telegram_id)) {
			return $scanPath;
		}

		// Fast transformation to objects
		$data = json_decode($data);

		$centerX = $mainShip->x;
		$centerY = $mainShip->y;
		$initX = $mainShip->x -1;
		$initY = $mainShip->y -1;
		$finX = $mainShip->x +1;
		$finY = $mainShip->y +1;

		$drawnShips = array();

		$base = imagecreatefrompng(APPPATH.'../imgs/map/background.png');

		$this->markForbidden($base, $mainShip);

		$this->showStarports($base, $mainShip);

		if ($isScan) $this->addRadar($base, $mainShip);

		$ships = $data->os;
		$asteroids = $data->as;
		$powerups = $data->pu;
		$minerals = $data->m;
		
		$target = null;
		if (is_array($ships)) {
			foreach ($ships as $ship) {
				if ($ship->id != $mainShip->id) {
					// Oculta naves dentro de campos de asteroides, salvo si son objetivos
					if (!$this->_coordinatesInSet($ship->x, $ship->y, $asteroids) || $ship->id == $mainShip->target) {
						$this->addShip($base, $mainShip, $ship);
					}
				}
				if ($ship->id == $mainShip->target) $target = $ship;
			}
		}

		$this->addMinerals($base, $mainShip, $minerals);

		$this->addShip($base, $mainShip, $mainShip);

		$this->addAsteroids($base, $mainShip, $asteroids);

		$this->addPowerups($base, $mainShip, $powerups);


		
		// Draw target markers
		if ($mainShip->target != null) {
			if ($target != null) {
				$this->addTargetMarker($base, $mainShip, $target->x, $target->y);
			} else {
				$target = $this->CI->Ships->get($mainShip->target);
				if (!empty($target)) $this->addTargetIndicator($base, $mainShip, $target);
			}			
		}
		$this->addMineralSaleIndicator($base, $mainShip);

		$this->addCounts($base, $mainShip);
		
		if ($isDead) $this->addDead($base, $mainShip);

		if ($debug) {
			header('Content-Type: image/png');
			imagepng($base);
			imagedestroy($base);
		} else {
			imagepng($base, $scanPath);
			imagedestroy($base);
			return $scanPath;
		}
	}


	/**
	 * Adds a ship to the map.
	 * Gets the sprite and rotates it accordingly. Stores its position in shipscount.
	 */
	public function addShip(&$base, $mainShip, $currentShip) {
		$this->shipscount[$currentShip->x.'-'.$currentShip->y] = isset($this->shipscount[$currentShip->x.'-'.$currentShip->y]) ? $this->shipscount[$currentShip->x.'-'.$currentShip->y]+1 : 1;

		$type = ($currentShip->id % 9)+1;
		$specialAngle = $currentShip->angle % 90 == 0;

		$path = $specialAngle ? APPPATH."../imgs/map/newset/random{$type}_100.png" : APPPATH."../imgs/map/newset/random{$type}_100_rotated.png";

		$this->addSquare($base, $mainShip, $path, $currentShip);

		if ($currentShip->shield > 0) {
			$shield = 'low';
			if ($currentShip->shield == $currentShip->max_shield) {
				$shield = 'full';
			} else if ($currentShip->shield >= 5) {
				$shield = 'med';
			}
			$shield = $specialAngle ? APPPATH."../imgs/map/shield_{$shield}.png" : APPPATH."../imgs/map/shield_{$shield}_rotated.png";
			$this->addSquare($base, $mainShip, $shield, $currentShip);
		}
	}

	/**
	 * Translates a coordinate relative to the main ship coordinate.
	 * As the image is built with its own coordinates system, we need to translate coordinates.
	 * The map will hold elements in 9 positions 0,0 to 2,2, as our main ship will be at 1,1
	 * the rest of the things placed need their coordinates translated to that correction.
	 */
	private function translate($base, $value) {
		$transform = 1 - $base;
		return $value + $transform;
	}

	/**
	 * In the game, the Y coordinate grows from bottom to top, but in the png sprite the 0,0 is at top, so we switch the Y coordinate
	 */
	private function remapY($value) {
		$y = array(0=>2,1=>1,2=>0);
		return isset($y[$value])?$y[$value]:-1;
	}

	/**
	 * If the ship is in the border of the available coordinates, draw the forbidden symbol
	 */
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

	/**
	 * Adds the target pointer depending on the position of the targeted ship
	 */
	private function addTargetIndicator(&$base, $mainShip, $target) {
		$x = 0;
		$y = 0;
		$target_symbol = null;
		if ($mainShip->x == $target->x) {
			$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/target_moved.png");
			if ($mainShip->y > $target->y) {
				$x = 1;
				$y = 2;
				$this->rotate($target_symbol, 180);
			} else {
				$x = 1;
				$y = 0;
			}
		} else if ($mainShip->y == $target->y) {
			$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/target_moved.png");
			if ($mainShip->x > $target->x) {
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
			if ($mainShip->x > $target->x) {
				if ($mainShip->y > $target->y) {
					$x = 0;
					$y = 2;
					$this->rotate($target_symbol, 180);
				} else {
					$x = 0;
					$y = 0;
					$this->rotate($target_symbol, 270);
				}
			} else {
				if ($mainShip->y > $target->y) {
					$x = 2;
					$y = 2;
					$this->rotate($target_symbol, 90);
				} else {
					$x = 2;
					$y = 0;
				}
			}
		}

		if ($target_symbol != null) {
			imagecopyresampled($base, $target_symbol, $x * $this->size, $y * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
		}
	}

	private function addTargetMarker(&$base, $mainShip, $x, $y) {
		$target_symbol = APPPATH."../imgs/map/target.png";
		$this->addSquareXY($base, $mainShip, $target_symbol, $x, $y);
	}

	/**
	 * Adds the count indicators to the positions where there are more than one ship
	 */
	private function addCounts(&$base, $mainShip) {
		foreach ($this->shipscount as $key => $value) {
			if ($value > 1) {
				$parts = explode('-', $key);
				if ($value > 4) $value = 5;
				$count = APPPATH."../imgs/map/count{$value}.png";
				$this->addSquareXY($base, $mainShip, $count, $parts[0], $parts[1]);
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

	private function addDead(&$base, $mainShip) {
		
		$explosion = imagecreatefrompng(APPPATH."../imgs/map/self_destruction.png");

		imagecopyresampled($base, $explosion, 0, 0, 0, 0, 300, 300, 300, 300);
		imagedestroy($explosion);
	}

	private function addAsteroids(&$base, $mainShip, $asteroids) {
		if (!is_array($asteroids)) return false;

		$asteroid = imagecreatefrompng(APPPATH."../imgs/map/asteroids_1_1.png");

		$target = null;
		if ($mainShip->target != null) {
			$target = $this->CI->Ships->get($mainShip->target);	
		}

		foreach ($asteroids as $astr) {
			imagecopyresampled($base, $asteroid, $this->translate($mainShip->x, $astr->x) * $this->size, $this->remapY($this->translate($mainShip->y, $astr->y)) * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			if ($target != null && $astr->x == $target->x && $astr->y == $target->y) {
				$this->addTargetMarker($base, $mainShip, $target->x, $target->y);
			}
		}
		
		imagedestroy($asteroid);
	}

	/**
	 * Helper method to see if a set of coordinates is contained in an array of objects that have coordinates too
	 */
	private function _coordinatesInSet($x, $y, $set) {
		if (!is_array($set)) return false;
		$flag = false;
		foreach ($set as $value) {
			$flag = $flag || ($value->x == $x && $value->y == $y);
		}
		return $flag;
	}

	/**
	 * Draws an array of powerups in the map
	 */
	private function addPowerups(&$base, $mainShip, $powerups) {
		if (!is_array($powerups)) return false;

		$this->CI->load->library('Calculations');

		foreach ($powerups as $pwr) {
			$rarity = $this->CI->calculations->getPowerUpRarityString($pwr->rarity);
			$type = $this->CI->calculations->getPowerUpTypeString($pwr->type);

			$power = APPPATH."../imgs/map/pu_{$rarity}_{$type}.png";

			$this->addSquare($base, $mainShip, $power, $pwr);
		}
	}

	/**
	 * Draws an array of minerals in the map
	 */
	private function addMinerals(&$base, $mainShip, $minerals) {
		if (!is_array($minerals)) return false;

		$this->CI->load->library('Calculations');

		foreach ($minerals as $mineral) {
			//$rarity = $this->CI->calculations->getPowerUpRarityString($mineral->rarity); //TODO, SPRITE DIFERENTE POR CANTIDAD QUE QUEDA?
			$type = $this->CI->calculations->getMineralTypeString($mineral->type);

			$image = APPPATH."../imgs/map/m_{$type}.png";

			$this->addSquare($base, $mainShip, $image, $mineral);
		}
	}

	/**
	 * Hold in this array all the data to be drawn, to allow a cache system
	 */
	private function prepareData($mainShip) {
		// Keep array keys small for better compression of the array
		$data = array(
				'ms' => array( // mainShip
						'id' => $mainShip->id,
						'angle' => $mainShip->angle,
						'shield' => $mainShip->shield,
						'max_shield' => $mainShip->max_shield,
						'target' => $mainShip->target,
						'x' => $mainShip->x,
						'y' => $mainShip->y,
						'm' => $mainShip->minerals > 0
					),
				'os' => array(), // otherShips
				'as' => array(), // asteroids
				'pu' => array(), // power ups
				'm' => array(), // minerals
			);

		$this->CI->load->model('Ships');
		$ships = $this->CI->Ships->get_target_lock_candidates($mainShip);

		if (is_array($ships)) {
			foreach ($ships as $ship) {
				if ($ship->id != $mainShip->id) {
					$data['os'][] = array(
						'id' => $ship->id,
						'angle' => $ship->angle,
						'shield' => $ship->shield,
						'max_shield' => $ship->max_shield,
						'x' => $ship->x,
						'y' => $ship->y
					);
				}
			}
		}

		if ($mainShip->target != null) {
			$ship = $this->CI->Ships->get($mainShip->target);
			$data['t'] = array(
					'id' => $ship->id,
					'angle' => $ship->angle,
					'shield' => $ship->shield,
					'max_shield' => $ship->max_shield,
					'x' => $ship->x,
					'y' => $ship->y
				);
		}

		$this->CI->load->model('Powerups');
		$powerups = $this->CI->Powerups->get_powerups_nearby($mainShip, 1);
		if (is_array($powerups)) {
			foreach ($powerups as $powerup) {
				$data['pu'][] = array(
					'rarity' => $powerup->rarity,
					'type' => $powerup->type,
					'x' => $powerup->x,
					'y' => $powerup->y
				);
			}
		}


		$this->CI->load->model('Minerals');
		$minerals = $this->CI->Minerals->get_minerals_nearby($mainShip, 1);
		if (is_array($minerals)) {
			foreach ($minerals as $mineral) {
				$data['m'][] = array(
					'type' => $mineral->type,
					'x' => $mineral->x,
					'y' => $mineral->y
				);
			}
		}

		$this->CI->load->model('Asteroids');
		$asteroids = $this->CI->Asteroids->get_asteroids_nearby($mainShip, 1);
		if (is_array($asteroids)) {
			foreach ($asteroids as $asteroid) {
				$data['as'][] = array(
					'x' => $asteroid->x,
					'y' => $asteroid->y
				);
			}
		}

		return $data;
	}

	function showStarports(&$base, $mainShip) {
		if ($mainShip->x <= 2 && $mainShip->y <= 2) {
			$mine = APPPATH."../imgs/map/mine.png";
			$this->addSquareXYSize($base, $mainShip, $mine, 1, 1, 0, 100);
		}
	}

	/**
	 * Adds the target pointer depending on the position of the targeted ship
	 */
	private function addMineralSaleIndicator(&$base, $mainShip) {
		if ($mainShip->minerals > 0 && !($mainShip->x == 1 && $mainShip->y == 1)) {
			$x = 0;
			$y = 0;
			$target_symbol = null;
			if ($mainShip->x == 1) {
				$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/mine_loc_bot.png");
				$x = 1;
				$y = 2;
			} else if ($mainShip->y == 1) {
				$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/mine_loc_left.png");
				$x = 0;
				$y = 1;
			} else {
				$target_symbol = imagecreatefrompng(APPPATH."../imgs/map/mine_loc_corner.png");
				$x = 0;
				$y = 2;
			}

			if ($target_symbol != null) {
				imagecopyresampled($base, $target_symbol, $x * $this->size, $y * $this->size, 0, 0, $this->size, $this->size, $this->size, $this->size);
			}
		}
	}

}
