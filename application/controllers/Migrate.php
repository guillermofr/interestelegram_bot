<?php

class Migrate extends CI_Controller
{

        public function index()
        {
                $this->load->library('migration');

                if ($this->migration->current() === FALSE)
                {
                    show_error($this->migration->error_string());
                } else {
                    echo "Migraciones realizadas.";
                }
        }

        public function version($version)
        {
            $this->load->library('migration');
            
            if ($this->migration->version($version))
            {
                echo "Migraciones realizadas: Version ".$version;
            } else {
                show_error($this->migration->error_string());
            }
        }

}