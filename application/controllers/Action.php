<?php

class Action extends CI_Controller
{

        private $ship = null;

        public function __construct()
        {
            parent::__construct();
            $this->load->library('Mapdrawercanvas');
            $this->load->model('Ships');
            $this->ship = $this->Ships->get_ship(1);
        }

        public function index()
        {
            echo "You must be lost...";
        }

        private function _response($data=array())
        {
            $map = $this->mapdrawercanvas->generateShipMap($this->ship);

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('data' => $data, 'map' => $map)));
        }

        public function move($direction)
        {
            $this->load->library('Movement');
            $this->load->model('Ships');

            switch ($direction) {
                case 'turn':
                    $this->movement->moveShip($this->ship, 0);
                    break;
                case 'left':
                    $this->movement->moveShip($this->ship, 1);
                    break;
                case 'right':
                    $this->movement->moveShip($this->ship, 3);
                    break;
                default:
                    $this->movement->moveShip($this->ship, 2);
                    break;
            }
            $this->Ships->update_ship(
                    array(
                        'x' => $this->ship->x,
                        'y' => $this->ship->y,
                        'angle' => $this->ship->angle
                    ),
                    $this->ship->id
                );

            $this->_response();
        }
}