<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Votes extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'votes'; // you MUST mention the table name
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
    public function create_vote($values=array(), $msg = null) {
        $keys = array_keys($values);
        if (!in_array('action_id', $keys) || 
            !in_array('user_id', $keys) 
            ) {
            return false;
        }
        if (!$msg->isPrivate()) {
            if ($this->where(array('action_id' => $values['action_id'], 'user_id' => $values['user_id']))->count() > 0 ) return null;
        }

        return ( $this->insert($values) ? $this->get_vote($this->db->insert_id()) : false );
    }

    /**
     * Actualiza una acción
     */
    public function update_vote($values=array(), $id=null) {
        if ($id == null) return false;
        return $this->_set($values, $id);
    }

    /**
     * Elimina una acción
     */
    public function delete_vote($id=null) {
        if ($id == null) return false;
        // TODO: Eliminar caché
        return $this->delete($id);   
    }

    /**
     * Obtiene una acción en base a su id
     */
    public function get_vote($id=null) {
        if ($id == null) return null;
        return $this->_get($id);
    }

    // get ships
    // get only my ships

}