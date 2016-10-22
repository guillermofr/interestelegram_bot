<?php
/**
 * Pickup library for Interestelegram Bot
 * 	- This library should contain every method related to picking up elements from the map.
 * @author Nahún <telemako@gmail.com>
 * @version 0.0.1
 */
class Attack {

	private $CI = null;

	private $mapSize = MAP_SIZE;

	public function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->model('Ships');
		$this->CI->load->library('Calculations');
	}

	public function attackShip($ship=null, $target=null, $strength=1)
	{
		if ($ship == null) return;
		if ($target == null) return;

		$targetShip = $this->CI->Ships->get_ship($target);

		$distance = $this->CI->calculations->distance($ship, $targetShip);

		$impact = false;
		if ($distance < 3) {
			$impact = true;
		} else if ($distance < 5) {
			$impact = $this->CI->calculations->chance(65);
		} else {
			$impact = $this->CI->calculations->chance(35);
		}

		$output = array();

		if ($impact){
			$targetShip = $this->CI->Ships->deal_damage($targetShip, $strength);
			if ($targetShip->health == 0) {
				$output[] = sprintf(_('Hemos destruído a la %s !!!'), $targetShip->name);
				$this->CI->Ships->kill($targetShip);
			} else {
				$output[] = sprintf(_('Hemos impactado a %s (%s escudo, %s casco restantes).'), $targetShip->name, $targetShip->shield, $targetShip->health);
			}
		} else {
			$output[] = sprintf(_('El ataque a %s ha fallado (%s escudo, %s casco restantes).'), $targetShip->name, $targetShip->shield, $targetShip->health);
		}

		return $output;
	}
	

}