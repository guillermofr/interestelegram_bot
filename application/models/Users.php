<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'users'; // you MUST mention the table name
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
    public function create_user($values=array()) {
        $keys = array_keys($values);
        if (!in_array('username', $keys) || !in_array('id', $keys)) {
            return false;
        }

        return ( $this->insert($values) ? $this->get_user($this->db->insert_id()) : false );
    }

    /**
     * Actualiza una nave
     */
    public function update_user($values=array(), $id=null) {
        if ($id == null) return false;
        return $this->_set($values, $id);
    }

    /**
     * Elimina una nave
     */
    public function delete_user($id=null) {
        if ($id == null) return false;
        // TODO: Eliminar caché
        return $this->delete($id);   
    }

    /**
     * Obtiene una nave en base a su id
     */
    public function get_user($id=null) {
        if ($id == null) return null;
        return $this->_get($id);
    }

    /**
     * Obtiene un capitan en base a su nave
     */
    public function get_name_by_id($captain_id=null) {
        if ($captain_id == null) return null;
        // TODO: Caché
        $user = $this->where('id', $captain_id)->get();
        return (!empty($user)) ? $user->username : '';
    }

    /**
    * Obtiene un capitan en base a su nave
    */
    public function get_id_by_name($captain_name=null) {
        if ($captain_name == null) return null;
        // TODO: Caché
        return $this->where('username', $captain_name)->get();
    }


    // get ships
    // get only my ships

}