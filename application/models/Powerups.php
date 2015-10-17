<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Powerups extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'powerups'; // you MUST mention the table name
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
     * Obtiene un listado de powerups a distancia $range de una $ship.
     */
    public function get_powerups_nearby($ship=null, $range=1) {
        if ($ship === null) return array();

        $minX = $ship->x - $range;
        $maxX = $ship->x + $range;
        $minY = $ship->y - $range;
        $maxY = $ship->y + $range;

        return $this->where(array('x >=' => $minX, 'x <=' => $maxX, 'y >=' => $minY, 'y <=' => $maxY))->get_all();
    }

    public function ship_is_in_powerup($ship = null) {
        if (empty($ship)) return false;
        return ($this->where(array('x' => $ship->x, 'y' => $ship->y))->count() >= 1);
    }

    public function ship_over_powerups($ship = null) {
        if (empty($ship)) return false;
        return $this->where(array('x' => $ship->x, 'y' => $ship->y))->get_all();
    }

    public function count_by_type($type) {
        return $this->db->query("SELECT type, count(id) as 'count' FROM powerups WHERE `type` = {$type} GROUP BY `type`")->result_array();
    }

    public function consume($id=null) {
        if ($id == null) return;
        $this->delete($id);
    }

}