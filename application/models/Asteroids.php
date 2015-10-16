<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Asteroids extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'asteroids'; // you MUST mention the table name
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
     * Obtiene un listado de asteroides a distancia $range de una $ship.
     */
    public function get_asteroids_nearby($ship=null, $range=1) {
        if ($ship === null) return array();

        $minX = $ship->x - $range;
        $maxX = $ship->x + $range;
        $minY = $ship->y - $range;
        $maxY = $ship->y + $range;

        return $this->where(array('x >=' => $minX, 'x <=' => $maxX, 'y >=' => $minY, 'y <=' => $maxY, 'size >' => 0))->get_all();
    }

    public function hide_ships_in_asteroids($mainShip, $range, $ships=null) {
        if (empty($ships)) return array();

        $asteroids = $this->get_asteroids_nearby($mainShip, $range);

        if (empty($asteroids)) return $ships;

        $this->load->library('Calculations');
        foreach ($ships as $key => $ship) {
            if ($this->calculations->coordinatesInArray($ship->x, $ship->y, $asteroids)) {
                unset($ships[$key]);
            }
        }

        return $ships;
    }

    public function ship_is_in_asteroid($ship = null) {
        if (empty($ship)) return false;
        return ($this->where(array('x' => $ship->x, 'y' => $ship->y))->count() >= 1);
    }

    public function select_moved_since($timestamp) {
        $timestamp = intval($timestamp);
        return $this->db->query('SELECT * FROM `asteroids` WHERE `timestamp` <= '.$timestamp.' OR `timestamp` IS NULL')->result_array();
    }

}