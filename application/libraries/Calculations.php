<?php
/**
 * Calculations library for Interestelegram Bot
 * 	- This library should contain every method to do the relative calculations between entities.
 * @author Gustavo <a.gustavo.soto@gmail.com>
 * @version 0.1
 */
class Calculations {

	private $CI = null;
	private $baseAgility = 55;
	private $agilityMultiplier = 5;
	private $healthCrewIncrement = 5;

	public function __construct() {
		$this->CI =& get_instance();
	}

	/* 
	 * SHIP CALCULATION METHODS 
	 * - The ship calculations methods will require the Ship model entitiy and not its id.
	 */

	private function _chance($chance, $min=0, $max=100) {
		$rand = rand(0, 100);
		if ($chance < $min) $chance = $min;
		if ($chance > $max) $chance = $max;
		log_message('error', '($rand'.$rand.' <= $chance'.$chance.')');
		return ($rand <= $chance);
	}


	public function attack_success($attackerShip, $defenderShip) {
		if (empty($attackerShip)) return false;
		if (empty($defenderShip)) return false;
		$diffCrew = $defenderShip->total_crew - $attackerShip->total_crew;

		$chance = $this->baseAgility + ($diffCrew * $this->agilityMultiplier);

		$this->CI->load->model('Asteroids');
		if ($this->CI->Asteroids->ship_is_in_asteroid($attackerShip)) {
			$chance -= 60;
		}

		if ($this->CI->Asteroids->ship_is_in_asteroid($defenderShip)) {
			$chance -= 40;
		}

		return $this->_chance($chance, 5, 95);
	}


	/**
	 * Ship_health
	 * - this method will return the new ship health and max health for a ship based on its crew.
	 * @param $ship 	Object 		The ship model object
	 * @param $crew 	Integer 	Number of new crew members, could be a negative number.
 	 */
	public function ship_health($ship, $crewIncrement) {

		$actualCrew = $ship->total_crew;
		$actualHP = $ship->health;
		$actualMAXHP = $ship->max_health;
		$increment = ( $crewIncrement * $this->healthCrewIncrement );

		$max_health = $actualMAXHP + $increment;
		$health = ( ( ( $actualCrew + $crewIncrement ) * $this->healthCrewIncrement ) - ( $actualCrew * $this->healthCrewIncrement ) + $actualHP );

		return array(
			'max_shield' => ( ( $actualCrew + $crewIncrement ) * $this->healthCrewIncrement ),
			'max_health' => ( $max_health <= 0 ) ? 1 : $max_health,
			'health' => ( $health <= 0 ) ? 1 : $health
		);

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

	/**



	//ajustar formula




	*/
	public function ship_dodge($mainShip) {
		if (empty($mainShip)) return false;

		$dodge = 50;

		$this->CI->load->model(array('Ships'));
		$targetedBy = $this->CI->Ships->where(array('target' => $mainShip->id))->count();

		return (! $this->_chance((50 - $targetedBy*10), 5, 50));
	}


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


	public function coordinatesInArray($x, $y, $set) {
		if (!is_array($set)) return false;
		$flag = false;
		foreach ($set as $value) {
			$flag = $flag || ($value->x == $x && $value->y == $y);
		}
		return $flag;
	}

	public function scanFailsOnAsteroids() {
		return $this->_chance(85);
	}

	public function moveAsteroid($hoursPast) {
		$base = 100;

		return $this->_chance($base + ($base * $hoursPast));
	}

	public function getPowerUpRarityString($rarity) {
		switch ($rarity) {
			case 0:
				return 'common';
				break;
			case 1:
				return 'rare';
				break;
			default:
				return 'common';
				break;
		}
	}

	public function getPowerUpTypeString($type) {
		switch ($type) {
			case 0:
				return 'shield';
				break;
			case 1:
				return 'health';
				break;
			case 2:
				return 'points';
				break;
			default:
				return 'shield';
				break;
		}
	}

	public function consume_powerups($ship, $powerups) {
		$this->CI->load->model(array('Ships', 'Powerups'));
		foreach ($powerups as $pwr) {
			$this->CI->Powerups->consume($pwr->id);
			switch ($pwr->type) {
				case 0: // shield
					$shield = ($pwr->rarity + 1) * 5;
					$ship->shield += $shield;
					if ($ship->shield > $ship->max_shield) $ship->shield = $ship->max_shield;
					$this->CI->Ships->update(array('shield' => $ship->shield), $ship->id);
					break;
				case 1: // health
					$health = ($pwr->rarity + 1) * 5;
					$ship->health += $health;
					if ($ship->health > $ship->max_health) $ship->health = $ship->max_health;
					$this->CI->Ships->update(array('health' => $ship->health), $ship->id);
					break;
				case 2: // points
					$score = 100 * ($pwr->rarity + 1);
					$ship->score += $score;
					$this->CI->Ships->update(array('score' => $ship->score), $ship->id);
					break;
			}
		}
	}

}

/* EOF */