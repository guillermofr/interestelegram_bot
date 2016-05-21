<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shoutout extends CI_Controller {

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
		$this->load->view('shoutout');
	}

	public function post()
	{
		$text = $this->input->post('body');
		$pass = $this->input->post('pass');

		if ($pass != 'password2') {
			echo '<pre>Quien eres?! Quien te envía?!';
			return;
		}

		$this->load->model('Users');
		$this->config->load('bot');
		$params = array( $this->config->item('botToken') );
		$this->load->library('Telegram', $params);

		$users = $this->Users->get_all();

		foreach ($users as $user) {
			$output = array(
				'chat_id' => $user->id,
				'text' => $text."\n\nIf you dont want to receive more messages, type /olvidar"
			);
			$this->telegram->sendMessage($output);
		}

		echo '<pre>Mensaje enviado!';
	}

}