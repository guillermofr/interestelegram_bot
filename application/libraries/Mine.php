<?php
/**
 * Minerals library for Interestelegram Bot
 * 	- This library should contain every method related to mining map.
 * @author Nahún <telemako@gmail.com>
 * @version 0.0.1
 */
class Mine {

	private $CI = null;

	private $mapSize = MAP_SIZE;

	public function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->model('Minerals');
		$this->CI->load->model('Ships');

	}

	public function checkMineralsActions($ship=null)
	{
		if ($ship == null) return;
		//if you move into a mineral
		$mine = $this->_onMine($ship);
		//if you move into starport
		$starport = $this->_onStarport($ship);

		$minerals = array();
		if ($mine) $minerals[] = $mine;
		if ($starport) $minerals[] = $starport;


		return array_merge($minerals);
	}



	private function _onMine($ship){

		$minerals = $this->CI->Minerals->ship_over_minerals($ship);
		$minerals_text = '';
		if (is_array($minerals) && count($minerals)) {
			foreach ($minerals as $mineral) {
				switch ($mineral->type) {
					case 0: // interestelegraminium
						$minerals_text .= "\xF0\x9F\x92\x8E\xF0\x9F\x9A\xBF Has empezado a minar un asteroide de interestelegraminium!\n";
						break;
				}
			}
		}
		return $minerals_text;
	}

	private function _onStarport($ship){

		$starport = $this->CI->Minerals->ship_over_starport($ship);
		$starport_text = '';
		if ($starport) {
			$starport_text .= "Aterrizas en el comercio de minerales!\n";
			$obtainedMoney = $this->CI->Ships->vender_todo($ship);
			if ($obtainedMoney){
				$starport_text .= "Has cambiado tus minerales por $obtainedMoney dineros!\n";
			} else {
				$starport_text .= "Aquí parece que pagan si les traes minerales de interestelegraminium, busca unos cuantos y vuelve.\n";
			}
		}

		return $starport_text;
	}





	private function _pickUpPowerups($ship)
	{
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