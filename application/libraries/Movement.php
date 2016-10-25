<?php
/**
 * Movement library for Interestelegram Bot
 * 	- This library should contain every method related to movement.
 * @author Nahún <telemako@gmail.com>
 * @version 0.0.1
 */
class Movement {

	private $CI = null;

	private $mapSize = MAP_SIZE;

	/**
	 * Defines the available movements for each angle of rotation
	 */
	private $movementDials = array(
			  0 => array(1,2,3,8),
			 45 => array(2,3,6,7),
			 90 => array(3,6,9,4),
			135 => array(6,9,8,1),
			180 => array(9,8,7,2),
			225 => array(8,7,4,3),
			270 => array(7,4,1,6),
			315 => array(4,1,2,9)
		);

	/**
	 * Defines the modification applied to coordinates when a move is performed
	 */
	private $coordinatesModifiers = array(
			1 => array(-1, 1),
			2 => array( 0, 1),
			3 => array( 1, 1),
			4 => array(-1, 0),
			5 => array( 0, 0),
			6 => array( 1, 0),
			7 => array(-1,-1),
			8 => array( 0,-1),
			9 => array( 1,-1)
		);

	/**
	 * Defines the modification applied to coordinates when a turn is performed
	 */
	private $turnCoordinatesModifiers = array(
			7 => array( 2, 2),
			8 => array( 0, 2),
			9 => array(-2, 2),
			6 => array(-2, 0),
			5 => array( 0, 0),
			4 => array( 2, 0),
			1 => array( 2,-2),
			2 => array( 0,-2),
			3 => array(-2,-2)
		);

	private $angleCoordinatesModifiers = array(
			  0 => array(
			  	1 => array(-1, 1),
			  	2 => array( 0, 1),
			  	3 => array( 1, 1),
			  ),
			 45 => array(
			  	1 => array( 0, 1),
			  	2 => array( 1, 1),
			  	3 => array( 1, 0),
			  ),
			 90 => array(
			  	1 => array( 1, 1),
			  	2 => array( 1, 0),
			  	3 => array( 1,-1),
			  ),
			135 => array(
			  	1 => array( 1, 0),
			  	2 => array( 1,-1),
			  	3 => array( 0,-1),
			  ),
			180 => array(
			  	1 => array( 1,-1),
			  	2 => array( 0,-1),
			  	3 => array(-1,-1),
			  ),
			225 => array(
			  	1 => array( 0,-1),
			  	2 => array(-1,-1),
			  	3 => array(-1, 0),
			  ),
			270 => array(
			  	1 => array(-1,-1),
			  	2 => array(-1, 0),
			  	3 => array(-1, 1),
			  ),
			315 => array(
			  	1 => array(-1, 0),
			  	2 => array(-1, 1),
			  	3 => array( 0, 1),
			  ),
		);

	private $angleCoordinatesModifiersTurn = array(
			  0 => array(array( 0, 2)),
			 45 => array(array( 2, 2)),
			 90 => array(array( 2, 0)),
			135 => array(array( 2,-2)),
			180 => array(array( 0,-2)),
			225 => array(array(-2,-2)),
			270 => array(array(-2, 0)),
			315 => array(array(-2, 2))
		);

	public function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->library('Pickup');
		$this->CI->load->library('Mine');
	}

	/**
	 * Modifies a given angle applying a modificator. Rounds to 360.
	 */
	private function modifyAngle($angle, $modifier) {
		$angle += $modifier;
		if ($angle < 0) $angle = $angle + 360;
		$angle = $angle % 360;
		return $angle;
	}

	/**
	 * Applies a defined movement to a ship. Adds angle to its rotation and changes its coordinates.
	 * Returns true on success, false otherwise
	 */
	public function moveShip(& $ship, $movement=false) {

		// Cancelation icon
		if ($movement === false) return 0;

		$coords = null;
		if ($movement >= 0) {
			switch ($movement) {
				case 0:
					$coords = $this->angleCoordinatesModifiersTurn[$ship->angle];
					$ship->angle = $this->modifyAngle($ship->angle, 180);
					break;
				case 1:
					$coords = $this->angleCoordinatesModifiers[$ship->angle];
					$ship->angle = $this->modifyAngle($ship->angle, -45);
					break;
				case 2:
					$coords = $this->angleCoordinatesModifiers[$ship->angle];
					$ship->angle = $this->modifyAngle($ship->angle,  0);
					break;
				case 3:
					$coords = $this->angleCoordinatesModifiers[$ship->angle];
					$ship->angle = $this->modifyAngle($ship->angle, 45);
					break;
			}

			$ship->x = $ship->x + $coords[$movement][0];
			$ship->y = $ship->y + $coords[$movement][1];

			// Keep movement inside bounds
			if ($ship->x <= 0) $ship->x = 1;
			if ($ship->x > $this->mapSize) $ship->x = $this->mapSize;
			if ($ship->y <= 0) $ship->y = 1;
			if ($ship->y > $this->mapSize) $ship->y = $this->mapSize;

			$output[] = $this->CI->pickup->pick($ship);
			$output[] = $this->CI->mine->checkMineralsActions($ship);

			// Return success
			return $output;
		} else {
			// Return error
			return array();
		}
	}

	/**
	* Returns a random position x
	*/
	public function generateRandomX() {
		return rand(1,$this->mapSize);
	}

	/**
	* Returns a random position y
	*/
	public function generateRandomY() {
		return rand(1,$this->mapSize);
	}

	/**
	* Returns a random position y
	*/
	public function generateRandomAngle() {
		$movementDialsKeys = array_keys($this->movementDials);
		return $movementDialsKeys[rand(0,count($movementDialsKeys)-1)];
	}


}