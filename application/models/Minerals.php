<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Minerals extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'minerals'; // you MUST mention the table name
    public $primary_key = 'id'; // you MUST mention the primary key
    public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
    public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update

    private $CI;

    public function __construct()
    {
        parent::__construct();

        $this->timestamps = FALSE;
    }

    /**
     * Obtiene un listado de minerals a distancia $range de una $ship.
     */
    public function get_minerals_nearby($ship=null, $range=1) {
        if ($ship === null) return array();

        $minX = $ship->x - $range;
        $maxX = $ship->x + $range;
        $minY = $ship->y - $range;
        $maxY = $ship->y + $range;

        return $this->where(array('x >=' => $minX, 'x <=' => $maxX, 'y >=' => $minY, 'y <=' => $maxY))->get_all();
    }

    public function ship_is_in_mineral($ship = null) {
        if (empty($ship)) return false;
        return ($this->where(array('x' => $ship->x, 'y' => $ship->y))->count() >= 1);
    }

    public function ship_over_minerals($ship = null) {
        if (empty($ship)) return false;
        return $this->where(array('x' => $ship->x, 'y' => $ship->y))->get_all();
    }

    public function ship_over_starport($ship = null) {
        if (empty($ship)) return false;
        return ($ship->x == 1 && $ship->y == 1);
    }

    public function count_by_type($type) {
        return $this->db->query("SELECT type, count(id) as 'count' FROM minerals WHERE `type` = {$type} GROUP BY `type`")->result_array();
    }

    public function consume($mineral=null,$minerals=0) {
        if ($mineral == null) return;

        $update_data = array('minerals' => 'minerals - '.$minerals);
		$this->update($update_data,array( 'id' => $mineral->id ),false);

		$updatedMineral = $this->where(array( 'id' =>$mineral->id ))->get_all();

		if ($updatedMineral[0]->minerals <= 0){
        	$this->delete($updatedMineral[0]->id);
		}

    }



}