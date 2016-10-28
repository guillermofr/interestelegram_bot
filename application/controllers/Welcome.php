<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Welcome extends CI_Controller {

	function __construct()
    {
        parent::__construct();
        $this->load->library('Twig');
        $this->load->add_package_path(APPPATH.'third_party/bitauth');
        $this->load->library('bitauth');
    }

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->twig->display('index.twig');
	}

	public function help(){
		$this->twig->display('info/help.twig');
	}

	public function ranking(){
		$this->load->view('ranking');
	}

	public function contact(){
		$this->twig->display('info/contact.twig');
	}

	public function canvas(){
		$this->load->library('Mapdrawercanvas');
		$this->load->model('Ships');
		$ship = $this->Ships->get(1);
		$data = $this->mapdrawercanvas->generateShipMap($ship);

		$this->load->view('canvas', array(
				'data' => $data
			));
	}

}
