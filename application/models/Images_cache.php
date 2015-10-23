<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Images_cache extends MY_Model
{
    //https://github.com/avenirer/CodeIgniter-MY_Model

    public $table = 'images_cache'; // you MUST mention the table name
    public $primary_key = 'id'; // you MUST mention the primary key
    public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
    public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update

    private $CI;

    public function __construct()
    {
        parent::__construct();

        $this->timestamps = FALSE;
    }

    public function add_image($path=null) {
        if ($path == null) return null;
        return $this->insert(array('path' => $path));
    }

    public function get_by_path($path=null) {
        if ($path == null) return null;
        return $this->get(array('path' => $path));
    }

    public function set_telegram_id($path=null, $telegram_id=null) {
        if ($path == null) return null;
        if ($telegram_id == null) return null;
        return $this->update(array('telegram_id' => $telegram_id), array('path' => $path));
    }

}