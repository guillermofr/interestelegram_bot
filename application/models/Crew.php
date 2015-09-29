<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Crew extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'crew'; // you MUST mention the table name
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
    private function _get($ship_id=null, $user_id=null) {
        if ($ship_id == null) return null;
        // TODO: Buscar en caché y devolver objeto
        $where = array('ship_id' => $ship_id);
        if (!is_null($user_id)) $where['user_id'] = $user_id;
        return $this->get($where);
    }

    /**
     * Utilizar siempre para actualizar un registro
     */
    private function _set($values=array(), $ship_id=null, $user_id=null) {
        if ( is_null($ship_id) || is_null($user_id) ) return null;
        // TODO: Eliminar caché
        $where = array('ship_id' => $ship_id, 'user_id' => $user_id);
        return $this->where($where)->update($values);
    }

    /**
     * Crea una nave, comprueba que al menos el campo chat_id y captain existen en los valores que recibe
     */
    public function create_crew($values=array()) {
        $keys = array_keys($values);
        if (!in_array('ship_id', $keys) || !in_array('user_id', $keys)) return false;

        return $this->insert($values);
    }

    /**
     * Actualiza una nave
     */
    public function update_crew($values=array(), $ship_id=null, $user_id=null) {
        if (is_null($ship_id) || is_null($user_id)) return false;
        return $this->_set($values, $id);
    }

    /**
     * Elimina un tripulante
     */
    public function delete_crew($ship_id=null, $user_id=null) {
        if (is_null($ship_id) || is_null($user_id)) return false;
        // TODO: Eliminar caché
        $where = array('ship_id' => $ship_id, 'user_id' => $user_id);
        return $this->delete($where);   
    }

    /**
     * Obtiene una nave en base a su id
     */
    public function get_crew_member($ship_id=null, $user_id=null) {
        if (is_null($ship_id) || is_null($user_id)) return null;
        return $this->_get($ship_id, $user_id);
    }

    /**
     * Obtiene la tripulación completa de una nave. incluye al capitan
     */
    public function get_crew_members_by_ship($ship_id=null){
        if ( is_null($ship_id) ) return null;
        return $this->_get($ship_id);
    }

}