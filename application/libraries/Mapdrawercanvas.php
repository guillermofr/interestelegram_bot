<?php

/**
 * Mapdrawer class by Telemako for Interestelegram
 * 
 * In the game, ships will be held in a square defined by the coordinates 1,1 - mapSize,mapSize
 * The game sprites are created in two forms, straight, pointing top, and rotated, pointing 45 degrees clockwise.
 * PNG rotations of less than 90 degrees are blurry, so we rotate 90 degrees the rotated sprites, to avoid losing quality.
 * 
 */
class Mapdrawercanvas {

	private $CI = null;
	private $ships = array();
	private $asteroids = array();
	private $size = 100;
	private $mapSize = MAP_SIZE;
	private $shipscount = array();
	protected $mainShip = null;

	/**
	 * Contructs the library. Creates the folder that will hold the images.
	 */
	public function __construct() {
		$this->CI =& get_instance();
	}

	/**
	 * Generates a map relative to a ship that will be placed in the center
	 * The ship targeted by this ship will be highlighted too
	 * 
	 * TODO: $range does nothing at the moment
	 */
	public function generateShipMap($mainShip, $isScan = false, $isDead = false) {
		$debug = false;
		$mainShip = $this->refreshMainShip($mainShip);
		$data = $this->prepareData($mainShip);
		$data['scan'] = $isScan ? 1 : 0;
		$data['dead'] = $isDead ? 1 : 0;

		// Fast transformation to objects
		$data = json_encode($data);
		$data = json_decode($data);

		$this->mainShip = $mainShip;

		$centerX = $mainShip->x;
		$centerY = $mainShip->y;
		$initX = $mainShip->x -1;
		$initY = $mainShip->y -1;
		$finX = $mainShip->x +1;
		$finY = $mainShip->y +1;

		$drawnShips = array();
		$data->content = array();

		$data->content[] = $this->addImage('/imgs/map/background.png');

		$this->markForbidden($data, $mainShip);

		$this->showStarports($data, $mainShip);

		
		if ($isScan) $this->addRadar($data, $mainShip);

		$ships = $data->os;
		$asteroids = $data->as;
		$powerups = $data->pu;
		$minerals = $data->m;
		
		$this->addMinerals($data, $minerals);

		$target = null;
		if (is_array($ships)) {
			foreach ($ships as $ship) {
				if ($ship->id != $mainShip->id) {
					// Oculta naves dentro de campos de asteroides, salvo si son objetivos
					if (!$this->_coordinatesInSet($ship->x, $ship->y, $asteroids) || $ship->id == $mainShip->target) {
						$this->addShip($data, $ship);
					}
				}
				if ($ship->id == $mainShip->target) $target = $ship;
			}
		}
		
		$this->addShip($data, $mainShip, true);

		$this->addAsteroids($data, $asteroids);

		$this->addPowerups($data, $powerups);

		// Draw target markers
		if ($mainShip->target != null) {
			if ($target != null) {
				$this->addTargetMarker($data, $mainShip, $target->x, $target->y);
			} else {
				$target = $this->CI->Ships->get($mainShip->target);
				if (!empty($target)) $this->addTargetIndicator($data, $mainShip, $target);
			}			
		}
		$this->addMineralSaleIndicator($data, $mainShip);

		$this->addCounts($data, $mainShip);
		
		if ($isDead) $this->addDead($data, $mainShip);

		return $data;
	}

	public function addImage($path, $x=0, $y=0, $angle=0, $offset=0, $size=null) {
		if ($size == null) $size = $this->size;

		$obj = new StdClass();
		$obj->i = $path;
		$obj->x = ($x * $size) - $offset;
		$obj->y = ($y * $size) - $offset;

		$obj->a = $angle;
		if ($obj->a % 90 != 0) {
			$obj->a = $obj->a - 45;
		}
		return $obj;
	}

	public function addImageRelative($path, $x=0, $y=0, $angle=0, $offset=0, $size=null) {
		if ($size == null) $size = $this->size;

		$obj = new StdClass();
		$obj->i = $path;

		$obj->x = ($this->translate($this->mainShip->x, $x) * $size) - $offset;
		$obj->y = ($this->remapY($this->translate($this->mainShip->y, $y)) * $size) - $offset;

		$obj->a = $angle;
		if ($obj->a % 90 != 0) {
			$obj->a = $obj->a - 45;
		}
		return $obj;
	}

	/**
	 * If the ship is in the border of the available coordinates, draw the forbidden symbol
	 */
	private function markForbidden(&$data, $ship) {
		$forbidden = "/imgs/map/forbidden.png";
		if ($ship->y == 1) {
			$data->content[] = $this->addImage($forbidden, 0, 2);
			$data->content[] = $this->addImage($forbidden, 1, 2);
			$data->content[] = $this->addImage($forbidden, 2, 2);
		}
		if ($ship->y == $this->mapSize) {
			$data->content[] = $this->addImage($forbidden, 0, 0);
			$data->content[] = $this->addImage($forbidden, 1, 0);
			$data->content[] = $this->addImage($forbidden, 2, 0);
		}
		if ($ship->x == 1) {
			$data->content[] = $this->addImage($forbidden, 0, 0);
			$data->content[] = $this->addImage($forbidden, 0, 1);
			$data->content[] = $this->addImage($forbidden, 0, 2);
		}
		if ($ship->x == $this->mapSize) {
			$data->content[] = $this->addImage($forbidden, 2, 0);
			$data->content[] = $this->addImage($forbidden, 2, 1);
			$data->content[] = $this->addImage($forbidden, 2, 2);
		}
	}

	function showStarports(&$data, $mainShip) {
		if ($mainShip->x <= 2 && $mainShip->y <= 2) {
			$mine = "/imgs/map/mine.png";
			$data->content[] = $this->addImageRelative($mine, 1, 1, 0, 100);
			//$this->addImageRelativeXYSize($base, $mainShip, $mine, 1, 1, 0, 100);
		}
	}

	/**
	 * Adds a ship to the map.
	 * Gets the sprite and rotates it accordingly. Stores its position in shipscount.
	 */
	public function addShip(&$data, $currentShip, $special=false) {
		$this->shipscount[$currentShip->x.'-'.$currentShip->y] = isset($this->shipscount[$currentShip->x.'-'.$currentShip->y]) ? $this->shipscount[$currentShip->x.'-'.$currentShip->y]+1 : 1;

		$type = $currentShip->model;
		$offset = in_array($type, array(12, 22, 32)) ? 25 : 0; // Big models
		$specialAngle = $currentShip->angle % 90 == 0;

		$path = $specialAngle ? "/imgs/map/ship_type{$type}.png" : "/imgs/map/ship_type{$type}_rotated.png";

		$data->content[] = $this->addImageRelative($path, $currentShip->x, $currentShip->y, $currentShip->angle, $offset);
		if ($currentShip->shield > 0) {
			$shield = 'low';
			if ($currentShip->shield == $currentShip->max_shield) {
				$shield = 'full';
			} else if ($currentShip->shield >= 5) {
				$shield = 'med';
			}
			$shield = $specialAngle ? "/imgs/map/shield_{$shield}.png" : "/imgs/map/shield_{$shield}_rotated.png";
			$data->content[] = $this->addImageRelative($shield, $currentShip->x, $currentShip->y, $currentShip->angle);
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

	private function addRadar(&$data, $mainShip) {
		$specialAngle = $mainShip->angle % 90 == 0;
		$radar = ($specialAngle ? "/imgs/map/radar.png" : "/imgs/map/radar_rotated.png");

		$angle = ($specialAngle ? $mainShip->angle : ($mainShip->angle-45));

		$data->content[] = $this->addImage($radar, 0, 0, $angle);
	}

	/**
	 * Draws an array of minerals in the map
	 */
	private function addMinerals(&$data, $minerals) {
		if (!is_array($minerals)) return false;

		$this->CI->load->library('Calculations');

		foreach ($minerals as $mineral) {
			//$rarity = $this->CI->calculations->getPowerUpRarityString($mineral->rarity); //TODO, SPRITE DIFERENTE POR CANTIDAD QUE QUEDA?
			$type = $this->CI->calculations->getMineralTypeString($mineral->type);

			$image = "/imgs/map/m_{$type}.png";

			$data->content[] = $this->addImageRelative($image, $mineral->x, $mineral->y);
		}
	}


	private function addAsteroids(&$data, $asteroids) {
		if (!is_array($asteroids)) return false;

		$asteroid = "/imgs/map/asteroids_1_1.png";

		$target = null;
		if ($this->mainShip->target != null) {
			$target = $this->CI->Ships->get($this->mainShip->target);	
		}

		foreach ($asteroids as $astr) {
			$data->content[] = $this->addImageRelative($asteroid, $astr->x, $astr->y);
			if ($target != null && $astr->x == $target->x && $astr->y == $target->y) {
				$this->addTargetMarker($base, $this->mainShip, $target->x, $target->y);
			}
		}
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
	private function addPowerups(&$data, $powerups) {
		if (!is_array($powerups)) return false;

		$this->CI->load->library('Calculations');

		foreach ($powerups as $pwr) {
			$rarity = $this->CI->calculations->getPowerUpRarityString($pwr->rarity);
			$type = $this->CI->calculations->getPowerUpTypeString($pwr->type);

			$power = "/imgs/map/pu_{$rarity}_{$type}.png";

			$data->content[] = $this->addImageRelative($power, $pwr->x, $pwr->y);
		}
	}

	private function addTargetMarker(&$data, $mainShip, $x, $y) {
		$target_symbol = "/imgs/map/target.png";
		$data->content[] = $this->addImageRelative($target_symbol, $x, $y);
	}

	/**
	 * Adds the target pointer depending on the position of the targeted ship
	 */
	private function addTargetIndicator(&$data, $mainShip, $target) {
		$x = 0;
		$y = 0;
		$target_symbol = null;
		$angle = 0;
		if ($mainShip->x == $target->x) {
			$target_symbol = "/imgs/map/target_moved.png";
			if ($mainShip->y > $target->y) {
				$x = 1;
				$y = 2;
				$angle = 180;
			} else {
				$x = 1;
				$y = 0;
			}
		} else if ($mainShip->y == $target->y) {
			$target_symbol = "/imgs/map/target_moved.png";
			if ($mainShip->x > $target->x) {
				$x = 0;
				$y = 1;
				$angle = 270;
			} else {
				$x = 2;
				$y = 1;
				$angle = 90;
			}
		} else {
			$target_symbol = "/imgs/map/target_moved_rotated.png";
			if ($mainShip->x > $target->x) {
				if ($mainShip->y > $target->y) {
					$x = 0;
					$y = 2;
					$angle = 180;
				} else {
					$x = 0;
					$y = 0;
					$angle = 270;
				}
			} else {
				if ($mainShip->y > $target->y) {
					$x = 2;
					$y = 2;
					$angle = 90;
				} else {
					$x = 2;
					$y = 0;
				}
			}
		}

		if ($target_symbol != null) {
			$data->content[] = $this->addImage($target_symbol, $x, $y, $angle);
		}
	}

	/**
	 * Adds the target pointer depending on the position of the targeted ship
	 */
	private function addMineralSaleIndicator(&$data, $mainShip) {
		if ($mainShip->minerals > 0 && !($mainShip->x == 1 && $mainShip->y == 1)) {
			$x = 0;
			$y = 0;
			$target_symbol = null;
			if ($mainShip->x == 1) {
				$target_symbol = "/imgs/map/mine_loc_bot.png";
				$x = 1;
				$y = 2;
			} else if ($mainShip->y == 1) {
				$target_symbol = "/imgs/map/mine_loc_left.png";
				$x = 0;
				$y = 1;
			} else {
				$target_symbol = "/imgs/map/mine_loc_corner.png";
				$x = 0;
				$y = 2;
			}

			if ($target_symbol != null) {
				$data->content[] = $this->addImage($target_symbol, $x, $y);
			}
		}
	}


	/**
	 * Adds the count indicators to the positions where there are more than one ship
	 */
	private function addCounts(&$data, $mainShip) {
		foreach ($this->shipscount as $key => $value) {
			if ($value > 1) {
				$parts = explode('-', $key);
				if ($value > 4) $value = 5;
				$count = "/imgs/map/count{$value}.png";
				$data->content[] = $this->addImageRelative($count, $parts[0], $parts[1]);
			}
		}
	}

	private function addDead(&$data, $mainShip) {
		
		$explosion = "/imgs/map/self_destruction.png";

		$data->content[] = $this->addImage('/imgs/map/background.png');
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
						'model' => $mainShip->model,
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
						'name' => $ship->name,
						'angle' => $ship->angle,
						'model' => $ship->model,
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
					'model' => $ship->model,
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

	private function refreshMainShip($mainShip)
	{
		if (is_numeric($mainShip)) {
			$mainShip = $this->CI->Ships->get_ship($mainShip);
		} else if (is_object($mainShip) && isset($mainShip->id)) {
			$mainShip = $this->CI->Ships->get_ship($mainShip->id);
		} else return null;

		return $mainShip;
	}
}
