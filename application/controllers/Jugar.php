<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Jugar extends CI_Controller {

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

	function __construct()
    {
        parent::__construct();
        $this->load->add_package_path(APPPATH.'third_party/bitauth');
        $this->load->library('bitauth');
        $this->load->library('Twig');

        if (!$this->bitauth->logged_in()){
        	$data = array();
			echo $this->twig->render('jugar.twig');
        	exit;
        }

    }

    public function index(){

		if ($this->bitauth->logged_in()){
			$data['logueado'] = $this->bitauth->logged_in();
			$data['user'] = ($data['logueado'])?$this->bitauth->get_user_by_id($this->bitauth->user_id):false;
			//echo "<pre>";print_r($data);

			$this->load->library('Mapdrawercanvas');
			$this->load->model('Ships');
			$ship = $this->Ships->get(1);
			$mapdata = $this->mapdrawercanvas->generateShipMap($ship);
			$data['data'] = json_encode($mapdata);

			$this->twig->display('canvas.twig',$data);
		} else {
			$data = array('type' => '');
			$data['logueado'] = $this->bitauth->logged_in();
			$data['user'] = ($data['logueado'])?$this->bitauth->get_user_by_id($this->bitauth->user_id):false;
			echo $this->twig->render('jugar.twig',$data);
		}

    }

	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */