<?php
/**
 * Pickup library for Interestelegram Bot
 * 	- This library should contain every method related to picking up elements from the map.
 * @author Nahún <telemako@gmail.com>
 * @version 0.0.1
 */
class Target {

	private $CI = null;

	private $mapSize = MAP_SIZE;

	public function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->model('Ships');
		$this->CI->load->library('Notifications');
	}

	public function targetIfValid($ship=null, $target=null)
	{
		if ($ship == null) return;
		if ($target == null) return;

		$targetShip = $this->CI->Ships->get_ship($target);
		$sectorShips = $this->CI->Ships->get_target_lock_candidates($ship);

		$output = array();

		if (in_array($targetShip, $sectorShips)){
			$this->CI->Ships->update_ship(array('target'=>$targetShip->id),$ship->id);
			$output[] = sprintf(_('Hemos fijado en el blanco a %s (%s escudo, %s casco restantes).'), $targetShip->name, $targetShip->shield, $targetShip->health);
			$this->CI->notifications->lockedAsTarget( $targetShip->captain, $ship->name );
		} else {
			$output[] = sprintf(_('No es posible fijar en el blanco a %s.'), $targetShip->name);
		}

		return $output;
	}
	

}