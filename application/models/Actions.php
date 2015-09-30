<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Actions extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'actions'; // you MUST mention the table name
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
     * Crea una acción
     */
    public function create_action($values=array()) {
        $keys = array_keys($values);
        if (!in_array('chat_id', $keys) || 
            !in_array('ship_id', $keys) || 
            !in_array('captain_id', $keys) ||
            !in_array('command', $keys)
            ) {
            return false;
        }

        $last_action = $this->get_last_action($values['ship_id']);
        if ($last_action){
            $this->update_action( array('closedAt' => Date('Y-m-d H:i:s', time()) ), $last_action->id );
        }

        return ( $this->insert($values) ? $this->get_action($this->db->insert_id()) : false );
    }

    /**
     * Actualiza una acción
     */
    public function update_action($values=array(), $id=null) {
        if ($id == null) return false;
        return $this->_set($values, $id);
    }

    /**
     * Elimina una acción
     */
    public function delete_action($id=null) {
        if ($id == null) return false;
        // TODO: Eliminar caché
        return $this->delete($id);   
    }

    /**
     * Obtiene una acción en base a su id
     */
    public function get_action($id=null) {
        if ($id == null) return null;
        return $this->_get($id);
    }


    public function get_last_action($ship_id=null) {
        if (is_null($ship_id)) return null;
        else {
            $action = $this->where(array( 'ship_id' => $ship_id ))->order_by('id', 'DESC')->limit(1)->get();
            return $action;
        }
    }

    // get ships
    // get only my ships

}