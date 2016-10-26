<?php
/**
 * Pickup library for Interestelegram Bot
 * 	- This library should contain every method related to picking up elements from the map.
 * @author Nahún <telemako@gmail.com>
 * @version 0.0.1
 */
class Pickup {

	private $CI = null;

	private $mapSize = MAP_SIZE;

	public function __construct() {
		$this->CI =& get_instance();
	}

	public function pick($ship=null)
	{
		if ($ship == null) return;
		$powerups = $this->_pickUpPowerups($ship);

		return array_merge($powerups);
	}

	private function _pickUpPowerups($ship)
	{
		$this->CI->load->model('Powerups');
		$this->CI->load->library('Calculations');
		$powerups = $this->CI->Powerups->ship_over_powerups($ship);

		$output = array();

		if (is_array($powerups) && count($powerups)) {
			// TODO: Powerups need to be separate classes
			$this->CI->calculations->consume_powerups($ship, $powerups);
			foreach ($powerups as $pwr) {
				switch ($pwr->type) {
					case 0: // shield
						$shield = ($pwr->rarity + 1) * 5;
						$output[] = sprintf(_('Hemos ganado %s puntos de escudo.'), $shield);
						break;
					case 1: // health
						$health = ($pwr->rarity + 1) * 5;
						$output[] = sprintf(_('Hemos ganado %s puntos de casco.'), $health);
						break;
					case 2: // points
						$score = 100 * ($pwr->rarity + 1);
						$output[] = sprintf(_('Hemos ganado %s créditos.'), $score);
						break;
				}
			}
		}

		return $output;
	}
	

}