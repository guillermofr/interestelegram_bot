<?php
/**
 * Movement library for Interestelegram Bot
 * 	- This library should contain every method related to movement.
 * @author Nahún <telemako@gmail.com>
 * @version 0.0.1
 */
class Movement {

	private $CI = null;

	private $mapSize = 6;

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
	 * Defines the Telegram array for keyboard building
	 *
	 *  \xE2\x86\x96 (NO) \xE2\xAC\x86 (N) \xE2\x86\x97 (NE)
	 *  \xE2\xAC\x85 (O)                   \xE2\x9E\xA1 (E)
	 *  \xE2\x86\x99 (SO) \xE2\xAC\x87 (S) \xE2\x86\x98 (SE)
	 *  \xE2\x86\xAA (turn)  \xF0\x9F\x9A\xAB  (cancel)
	 *  \xF0\x9F\x94\xB9 (empty)
	 *
	 */
	private $movementKeyboard = array(
			  0 => array(
			  		array("\xE2\x86\x96","\xE2\xAC\x86","\xE2\x86\x97"),
			  		array("\xF0\x9F\x94\xB9","\xF0\x9F\x9A\xAB","\xF0\x9F\x94\xB9"),
			  		array("\xF0\x9F\x94\xB9","\xE2\x86\xAA","\xF0\x9F\x94\xB9")
			  	),
			 45 => array(
			 		array("\xF0\x9F\x94\xB9","\xE2\xAC\x86","\xE2\x86\x97"),
			 		array("\xF0\x9F\x94\xB9","\xF0\x9F\x9A\xAB","\xE2\x9E\xA1"),
			 		array("\xE2\x86\xAA","\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9")
			 	),
			 90 => array(
			 		array("\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9","\xE2\x86\x97"),
			 		array("\xE2\x86\xAA","\xF0\x9F\x9A\xAB","\xE2\x9E\xA1"),
			 		array("\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9","\xE2\x86\x98")
			 	),
			135 => array(
					array("\xE2\x86\xAA","\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9"),
					array("\xF0\x9F\x94\xB9","\xF0\x9F\x9A\xAB","\xE2\x9E\xA1"),
					array("\xF0\x9F\x94\xB9","\xE2\xAC\x87","\xE2\x86\x98")
				),
			180 => array(
					array("\xF0\x9F\x94\xB9","\xE2\x86\xAA","\xF0\x9F\x94\xB9"),
					array("\xF0\x9F\x94\xB9","\xF0\x9F\x9A\xAB","\xF0\x9F\x94\xB9"),
					array("\xE2\x86\x99","\xE2\xAC\x87","\xE2\x86\x98")
				),
			225 => array(
					array("\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9","\xE2\x86\xAA"),
					array("\xE2\xAC\x85","\xF0\x9F\x9A\xAB","\xF0\x9F\x94\xB9"),
					array("\xE2\x86\x99","\xE2\xAC\x87","\xF0\x9F\x94\xB9")
				),
			270 => array(
					array("\xE2\x86\x96","\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9"),
					array("\xE2\xAC\x85","\xF0\x9F\x9A\xAB","\xE2\x86\xAA"),
					array("\xE2\x86\xAA","\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9")
				),
			315 => array(
					array("\xE2\x86\x96","\xE2\x86\xAA","\xF0\x9F\x94\xB9"),
					array("\xE2\xAC\x85","\xF0\x9F\x9A\xAB","\xF0\x9F\x94\xB9"),
					array("\xF0\x9F\x94\xB9","\xF0\x9F\x94\xB9","\xE2\x86\xAA")
				)
		);
	
	/**
	 * Translates icons to movements
	 */
	private $iconMovementEquivalence = array(
		1 => "\xE2\x86\x96",
		2 => "\xE2\xAC\x86",
		3 => "\xE2\x86\x97",
		4 => "\xE2\xAC\x85",
		5 => "\xE2\x86\xAA", // turn
		6 => "\xE2\x9E\xA1",
		7 => "\xE2\x86\x99",
		8 => "\xE2\xAC\x87",
		9 => "\xE2\x86\x98"
	);

	/**
	 * Defines the modification applied to coordinates when a moved is performed
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

	public function __construct() {
		$this->CI =& get_instance();
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
	public function moveShip(& $ship, $move) {
		$move = array_search($move, $this->iconMovementEquivalence);
		
		// Cancelation icon
		if ($move === false) return 0;
		if ($move == 5) {
			$movement = 3;
		} else {
			$movement = array_search($move, $this->movementDials[$ship->angle]);
		}
		// Movement is not in the available dial, return error
		if ($movement === false) return -1;

		$coords = null;
		if ($movement >= 0) {
			switch ($movement) {
				case 0:
					$ship->angle = $this->modifyAngle($ship->angle, -45);
					$coords = $this->coordinatesModifiers;
					break;
				case 1:
					$ship->angle = $this->modifyAngle($ship->angle,   0);
					$coords = $this->coordinatesModifiers;
					break;
				case 2:
					$ship->angle = $this->modifyAngle($ship->angle,  45);
					$coords = $this->coordinatesModifiers;
					break;
				case 3:
					$ship->angle = $this->modifyAngle($ship->angle, 180);
					$coords = $this->turnCoordinatesModifiers;
					break;
			}

			$ship->x = $ship->x + $coords[$move][0];
			$ship->y = $ship->y + $coords[$move][1];

			// Keep movement inside bounds
			if ($ship->x <= 0) $ship->x = 1;
			if ($ship->x > $this->mapSize) $ship->x = $this->mapSize;
			if ($ship->y <= 0) $ship->y = 1;
			if ($ship->y > $this->mapSize) $ship->y = $this->mapSize;

			// Return success
			return 1;
		} else {
			// Return error
			return -1;
		}
	}

	/**
	 * Returns a Telegram keyboard with valid movements given a ship
	 */
	public function generateKeyboard($ship) {
		return $this->movementKeyboard[$ship->angle];
	}

}