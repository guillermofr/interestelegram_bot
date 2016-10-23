<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {

	function __construct()
    {
        parent::__construct();
        //$this->load->spark('twiggy/0.8.5');
        $this->load->library('email');
        $this->load->library('Twig');
        $this->load->add_package_path(APPPATH.'third_party/bitauth');
        $this->load->library('bitauth');
    }

    public function index(){

    	if ($this->input->post('email')){
    		
	    	$config['charset'] = 'iso-8859-1';
			$config['mailtype'] = 'html';
			$this->email->initialize($config);

			$this->email->from('www-data@inter.es', 'NMV');
			$this->email->to($this->input->post('email')); 
			
				/* aquí hay que ver si el correo enviado existe
				en la base de datos.

				si existe , generamos un token nuevo y le enviamos el link

				si no existe, creamos el usuario, ver si se puede traer el 
				nick y clan desde la mlp y creamos el token igualmente*/

			$email = $this->db->escape($this->input->post('email'));
			$search = $this->db->query("select * from bitauth_users where username = $email");
			if (!$search->num_rows()){
				//obtener datos de la mlp para registrarlo o registrarlo sin nick

				//$this->load->helper('inti');
				//$url = 'http://murcialanparty.com/mlp14/jsonrpc.php';

			    //$client = new JsonIntiClient($url);
				// Params request

			    $mlp_data = array('nick'=>'','clan'=>'');
			    /*$rtn = $client->sendRequest('getNickClanByEmail', $this->input->post('email'));
			    if (!$rtn->isError()){
			    	$t = $rtn->getResult();
			    	if (isset($t['nick']))
			    	 	$mlp_data = $rtn->getResult();
			    }*/
			    	
				$user = array(
					'fullname' => $mlp_data['nick'],
					'clan' => $mlp_data['clan'],
					'participante' => ($mlp_data['nick'] != '')?1:0,
				    'username' => $this->input->post('email'),
				    'password' => 'mipeneesmipassword'
				);	
				$new_user = $this->bitauth->add_user($user);
			} else {
				$new_user = $search->result();
				$new_user = $new_user[0];
			}
			//obtener link de login

			$code = $this->bitauth->forgot_password($new_user->user_id);

			$this->email->subject('Link de acceso');
			$this->email->message("Pincha en el enlace para entrar en la web a jugar: <a href='".site_url()."login/in/$code'>".site_url()."login/in/$code</a>");	
			//if (site_url() == 'http://vitaminados.local/'){
				echo "Pincha en el siguiente enlace para entrar a jugar, en este servidor no funciona el env&iacute;o de correos<br><a href='/login/in/$code'>$code</a>"; exit;
			//}

			if ($this->email->send()){
				$this->twig->display('info/enviado.twig',array('enviado'=>true));
			} else {
	    		$this->twig->display('info/enviado.twig',array('enviado'=>false));
			}
    	} else {
	    	$this->twig->display('/jugar.twig',array('enviado'=>false));
    		
    	}
    	
		
    }

    public function in($token=''){

    	$query = $this->db->where('forgot_code', $token)->get('bitauth_users');
		if($query->num_rows())
		{
			//el usuario existe y procedemos a borrarle el token y a loguearlo
			$user = $query->row();
			
			if($this->bitauth->login($user->username, NULL, FALSE, FALSE, $token))
			{
				//funcionó el login
				redirect('/jugar');
			} else {
				redirect('/jugar');
			}
		} else 
		//no valido mando al login
		redirect('/jugar');

    }

    public function setNick(){

    	if ($this->input->post('nick')){
    		if($this->bitauth->logged_in()){
    			$this->bitauth->update_user($this->bitauth->user_id,array('fullname'=>$this->input->post('nick')));
    		}
    	} 
    	redirect('/jugar');
    }

    public function out(){
    	$this->bitauth->logout();
    	redirect('/jugar');
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */