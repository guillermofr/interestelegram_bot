<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ships extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'ships'; // you MUST mention the table name
    public $primary_key = 'id'; // you MUST mention the primary key
    public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
    public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update

    public function __construct()
    {
        parent::__construct();

        $this->timestamps = FALSE;
    }

    /**
     * Wrappers para el uso de cache
     * Utilizar siempre para obtener un registro
     */
    private function _get($id=null) {
        if ($id == null) return null;
        // TODO: Buscar en caché y devolver objeto
        return $this->get($id);
    }

    /**
     * Utilizar siempre para actualizar un registro
     */
    private function _set($values=array(), $id=null) {
        if ($id == null) return null;
        // TODO: Eliminar caché
        return $this->update($values, $id);
    }

    /**
     * Crea una nave, comprueba que al menos el campo chat_id y captain existen en los valores que recibe
     */
    public function create_ship($values=array()) {
        $keys = array_keys($values);
        if (!in_array('chat_id', $keys) || !in_array('captain', $keys)) {
            return false;
        }

        return ( $this->insert($values) ? $this->get_ship($this->db->insert_id()) : false );
    }

    /**
     * Actualiza una nave
     */
    public function update_ship($values=array(), $id=null) {
        if ($id == null) return false;
        return $this->_set($values, $id);
    }

    /**
     * Elimina una nave
     */
    public function delete_ship($id=null) {
        if ($id == null) return false;
        // TODO: Eliminar caché
        return $this->delete($id);   
    }

    /**
     * Obtiene una nave en base a su id
     */
    public function get_ship($id=null) {
        if ($id == null) return null;
        return $this->_get($id);
    }

    /**
     * Obtiene una nave en base a su chat_id
     */
    public function get_ship_by_chat_id($chat_id=null) {
        if ($chat_id == null) return null;
        // TODO: Caché
        return $this->where('chat_id', $chat_id)->get();
    }

    /**
     * Obtiene una nave en base a su captain
     */
    public function get_ship_by_captain($captain=null) {
        if ($captain == null) return null;
        // TODO: Caché
        return $this->where('captain', $captain)->get();
    }

    /**
     * Obtiene una nave en base a su chat_id
     */
    public function get_ships_by_xy($x=null,$y=null,$chat_id=null) {
        if ($x === null) return null;
        if ($y === null) return null;
        if ($chat_id === null) return null;
        // TODO: Caché
        return $this->where(array('x' => $x,'y' => $y, 'chat_id !=' => $chat_id, 'active' => 1))->get_all();
    }



    // get crew
    // get crew but captain

}