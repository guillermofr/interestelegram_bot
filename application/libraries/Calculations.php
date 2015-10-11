<?php
/**
 * Calculations library for Interestelegram Bot
 * 	- This library should contain every method to do the relative calculations between entities.
 * @author Gustavo <a.gustavo.soto@gmail.com>
 * @version 0.1
 */
class Calculations {

	private $CI = null;

	public function __construct() {
		$this->CI =& get_instance();
	}

	/* 
	 * SHIP CALCULATION METHODS 
	 * - The ship calculations methods will require the Ship model entitiy and not its id.
	 */

	private function _chance($chance) {
		$rand = rand(0, 100);
		log_message('error', 'chance '.$rand.' <= '.$chance);
		return ($rand <= $chance);
	}

	public function attack_success($attackerShip, $defenderShip) {
		if (empty($attackerShip)) return false;
		if (empty($defenderShip)) return false;
		$baseAgility = 45;
		$multiplier = 5;
		$diffCrew = $attackerShip->total_crew - $defenderShip->total_crew;

		log_message('error', $attackerShip->total_crew.' vs '.$defenderShip->total_crew.' = '.($baseAgility + ($diffCrew * $multiplier)));
		return (! $this->_chance($baseAgility + ($diffCrew * $multiplier)));
	}

	/**
	 * ship_damage
	 * - The ship damage
	 * @param $ship 			<Ship Model Entity> 	The ship that is doing the damage.
	 * @param $target_ship 		<Ship Model Entity> 	The ship that will recieve the damage.
	 */
	public function ship_damage( $ship ) {}


	/**
	 * ship_resistance
	 * - The ship resistance
	 * @param $ship 			<Ship Model Entity> 	The ship that is doing the damage.
	 * @param $target_ship 		<Ship Model Entity> 	The ship that will recieve the damage.
	 */
	public function ship_resistance( $ship ) {}


	/**
	 * ship_dodge
	 * - The ship dodge
	 * @param $ship 			<Ship Model Entity> 	The ship that is doing the damage.
	 * @param $target_ship 		<Ship Model Entity> 	The ship that will recieve the damage.
	 */
	public function ship_dodge( $ship ) {}


	/**
	 * ship_damage_vs_ship
	 * - The ship damage vs another ship.
	 * @param $ship 			<Ship Model Entity> 	The ship that is doing the damage.
	 * @param $target_ship 		<Ship Model Entity> 	The ship that will recieve the damage.
	 */
	public function ship_damage_vs_ship( $ship, $target_ship ) {}


	/**
	 * ship_resistance_vs_ship
	 * - The ship resistance vs another ship
	 * @param $ship 			<Ship Model Entity> 	The ship that is going to resist the damage.
	 * @param $damagin_ship 	<Ship Model Entity>		The ship tiat is doing damage.
	 */
	public function ship_resistance_vs_ship( $ship, $damagin_ship ) {}


	/**
	 * ship_dodge_vs_ship
	 * - The ship dodge vs another ship
	 * @param $ship 			<Ship Model Entity> 	The ship that is going to resist the damage.
	 * @param $damagin_ship 	<Ship Model Entity>		The ship tiat is doing damage.
	 */
	public function ship_dodge_vs_ship( $ship, $damagin_ship ) {}


	// concepts
	public function ship_damage_vs_other( $ship, $other, $other_type ) {}
	public function ship_resistance_vs_other( $ship, $other, $other_type ) {}
	public function ship_dodge_vs_other( $ship, $other, $other_type ) {}



}

/* EOF */