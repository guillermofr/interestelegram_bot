<?php
/**
 * Telegram Bot Class.
 * @author Telemako
 */

class Telegram {
	private $bot_id = "";
	private $data = array();
	private $updates = array();
	
	public function __construct($params) {
		$this->bot_id = $params[0];
		$this->data = $this->getData();
	}
	
	public function endpoint($api, array $content, $post = TRUE) {
		$attachmentMethods = array('sendPhoto');
		$url = 'https://api.telegram.org/bot' . $this->bot_id . '/' . $api;
		if ($post) {
			return $this->sendAPIRequest($url, $content, TRUE, in_array($api, $attachmentMethods));
		} else {
			return $this->sendAPIRequest($url, array(), FALSE);
		}
	}
	
	public function getMe() {
		return $this->endpoint("getMe", array(), FALSE);
	}
	
	public function sendMessage(array $content) {
		return $this->endpoint("sendMessage", $content);
	}
	
	public function forwardMessage(array $content) {
		return $this->endpoint("forwardMessage", $content);
	}
	
	public function sendPhoto(array $content) {
		return $this->endpoint("sendPhoto", $content);
	}
	
	public function sendAudio(array $content) {
		return $this->endpoint("sendAudio", $content);
	}
	
	public function sendDocument(array $content) {
		return $this->endpoint("sendDocument", $content);
	}
	
	public function sendSticker(array $content) {
		return $this->endpoint("sendSticker", $content);
	}
	
	public function sendVideo(array $content) {
		return $this->endpoint("sendVideo", $content);
	}
	
	public function sendVoice(array $content) {
		return $this->endpoint("sendVoice", $content);
	}
	
	public function sendLocation(array $content) {
		return $this->endpoint("sendLocation", $content);
	}
	
	public function sendChatAction(array $content) {
		return $this->endpoint("sendChatAction", $content);
	}
	
	public function getUserProfilePhotos(array $content) {
		return $this->endpoint("getUserProfilePhotos", $content);
	}
	
	public function setWebhook($url, $certificate = "") {
		if ($certificate == "") {
			$content = array('url' => $url);
		} else {
			$content = array('url' => $url, 'certificate' => $certificate, true);
		}
		return $this->endpoint("setWebhook", $content);
	}
	
	public function getData() {
		if (empty($this->data)) {
			$rawData = file_get_contents("php://input");
			return json_decode($rawData, TRUE);
		} else {
			return $this->data;
		}
	}
	
	public function setData(array $data) {
		$this->data = data;
	}
	
	public function Text() {
		return $this->data["message"] ["text"];
	}
	
	public function ChatID() {
		return $this->data["message"]["chat"]["id"];
	}
	
	public function Date() {
		return $this->data["message"]["date"];
	}
	
	public function FirstName() {
		return $this->data["message"]["from"]["first_name"];
	}
	
	public function LastName() {
		return $this->data["message"]["from"]["last_name"];
	}
	
	public function Username() {
		return $this->data["message"]["from"]["username"];
	}
	
	public function Location() {
		return $this->data["message"]["location"];
	}
	
	public function UpdateID() {
		return $this->data["update_id"];
	}
	
	public function UpdateCount() {
		return count($this->updates["result"]);
	}
	
	public function messageFromGroup() {
		if ($this->data["message"]["chat"]["title"] == "") {
			return FALSE;
		}
		return TRUE;
	}
	
	public function buildKeyBoard(array $options, $onetime = TRUE, $resize = TRUE, $selective = TRUE) {
		$replyMarkup = array(
			'keyboard' => $options,
			'one_time_keyboard' => $onetime,
			'resize_keyboard' => $resize,
			'selective' => $selective
		);
		$encodedMarkup = json_encode($replyMarkup, TRUE);
		return $encodedMarkup;
	}
	
	public function buildKeyBoardHide($selective = TRUE) {
		$replyMarkup = array(
			'hide_keyboard' => TRUE,
			'selective' => $selective
		);
		$encodedMarkup = json_encode($replyMarkup, TRUE);
		return $encodedMarkup;
	}
	
	public function buildForceReply($selective = TRUE) {
		$replyMarkup = array(
			'force_reply' => TRUE,
			'selective' => $selective
		);
		$encodedMarkup = json_encode($replyMarkup, TRUE);
		return $encodedMarkup;
	}
	
	public function getUpdates($offset = 0, $limit = 100, $timeout = 0, $update = FALSE) {
		$content = array('offset' => $offset, 'limit' => $limit, 'timeout' => $timeout);
		$reply = $this->endpoint("getUpdates", $content);
		$this->updates = json_decode($reply, TRUE);
		if ($update) {
			$last_element = array_pop($this->updates["result"]);
			$last_element_id = $last_element["update_id"] + 1;
			$content = array('offset' => $last_element_id, 'limit' => "1", 'timeout' => $timeout);
			$this->endpoint("getUpdates", $content);
		}
		return $this->updates;
	}
	
	public function serveUpdate($update) {
		$this->data = $this->updates["result"][$update];
	}

	private function sendAPIRequest($url, array $content, $post = TRUE, $attachments = FALSE) {
		if (!$attachments) {
			foreach ($content as $key => $value) { // Fix mentions
				if (is_string($value) && strpos($value, '@') === 0) $content[$key] = ' '.$value;
			}
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if ($attachments) curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));

		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		}
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$result = curl_exec($ch);
		/*
		var_dump($content);
		var_dump(curl_error($ch));
		var_dump($result);
		*/
		curl_close($ch);
		return $result;
	}

	public function prepareImage($path) {
		$CI =& get_instance();
		$CI->load->model('Images_cache');
		$cache = $CI->Images_cache->get_by_path($path);
		if ($cache != null && is_object($cache)) {
			if (!empty($cache->telegram_id)) {
				return $cache->telegram_id;
			}
		} else {
			$CI->Images_cache->add_image($path);
		}
		require_once(APPPATH.'libraries/CURLFile.php');
		$filename = realpath($path);
		return new CURLFile($filename, 'image/png', basename($path));
	}

	public function updateImage($path, $output) {
		if (strpos($path, '.png') === false) return;
		if (!is_object($output)) $output = json_decode($output);
		$CI =& get_instance();
		$CI->load->model('Images_cache');
		if (isset($output->result) && isset($output->result->photo)) {
			$image_id = $output->result->photo[count($output->result->photo)-1]->file_id;
			$CI->Images_cache->set_telegram_id($path, $image_id);
		}
	}

	public function prepareCert($path) {
		require_once(APPPATH.'libraries/CURLFile.php');
		$filename = realpath($path);
		return new CURLFile($filename, 'application/x-pem-file', basename($path));
	}
}

?>