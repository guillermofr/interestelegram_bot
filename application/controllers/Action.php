<?php

class Action extends CI_Controller
{

        private $ship = null;

        public function __construct()
        {
            parent::__construct();
            $this->load->add_package_path(APPPATH.'third_party/bitauth');
            $this->load->library('bitauth');
            $this->load->library('Mapdrawercanvas');
            $this->load->library('Movement');
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

        private function _checkDead(){
            if ($this->ship->health == 0){
                return true;
            }
            else return false;
        }

        public function move($direction)
        {
            if ($this->_checkDead()){
                $this->_response(array('dead'=>true));
            } else {
                $this->load->library('Movement');
                $this->load->model('Ships');

                switch ($direction) {
                    case 'turn':
                        $response = $this->movement->moveShip($this->ship, 0);
                        break;
                    case 'left':
                        $response = $this->movement->moveShip($this->ship, 1);
                        break;
                    case 'right':
                        $response = $this->movement->moveShip($this->ship, 3);
                        break;
                    default:
                        $response = $this->movement->moveShip($this->ship, 2);
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

                $data = array(
                        'messages' => $response
                    );

                $this->_response($data);
            }
        }

        public function target($ship=null)
        {
            if ($this->_checkDead()){
                $this->_response(array('dead'=>true));
            } else {
                $this->load->library('Target');

                $response = $this->target->targetIfValid($this->ship, $ship);

                $data = array(
                        'messages' => $response
                    );

                $this->_response($data);
            }
        }

        public function attack()
        {
            if ($this->_checkDead()){
                $this->_response(array('dead'=>true));
            } else {
                $this->load->library('Attack');

                $response = $this->attack->attackShip($this->ship, $this->ship->target);

                $data = array(
                        'messages' => $response
                    );

                $this->_response($data);
            }
        }

        public function revive()
        {
            if ($this->ship->health == 0){
                //active and place random
                $this->Ships->update_ship(
                    array(
                        'x' => $this->movement->generateRandomX(),
                        'y' => $this->movement->generateRandomY(),
                        'angle' => $this->movement->generateRandomAngle(),
                        'health' => 5,
                        'shield' => 0,
                        'active' => 1
                    ),
                    $this->ship->id
                );
            }
            redirect('/jugar');
        }
}