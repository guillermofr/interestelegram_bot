<?php

class Message {
	// CI reference
	private $CI = null;
	// Bot config information
	private $botToken = null;
	private $botUsername = null;
	// Parse operation/action/command
	private $isReply = false;
	private $isJoin = false;
	private $isLeave = false;
	private $isBotJoin = false;
	private $isBotLeave = false;
	private $joiner = null;
	private $leaver = null;
	private $isBot = false;
	private $isFromBot = false;
	private $isCommand = false;
	private $text = null;
	private $command = null;
	private $params = null;
	private $replyId = null;
	private $updateId = null;
	// Message basic information
	private $messageId = null;
	private $fromId = null;
	private $fromUsername = null;
	private $fromFirstName = null;
	// Chat information. Check if is a private message or a group message.
	private $date = null;			// message date.
	private $chatId = null;			// chat id is alwais there.
	private $isPrivate = false;		// if is private message (normally positive chat id)
	private $chatUsername = null;	// - username and firstname are relative to privates.
	private $chatFirstName = null;
	private $isGroup = false;		// if is a group message (normally negative chat id)
	private $chatTitle = null;		// - chat title are relative to group messages.

	// regular expressions
	private $regExp_Command = '/^\/(\w*)(.*)/';

	public function __construct($msg=array()) {
		$this->CI =& get_instance();

		$this->CI->config->load('bot');

		$this->botToken = $this->CI->config->item('botToken');
		$this->botUsername = $this->CI->config->item('botUsername');

		$this->parse($msg);
	}

	/**
	 * _clean_msg
	 * - private method to clean the actual status of the message.
	 * - set message attributes to null or false.
	 */
	private function _clean_msg() {
		// booleans to false.
		$this->isJoin = $this->isLeave = $this->isBotJoin = $this->isBotLeave = $this->isReply =
		$this->isBot = $this->isCommand = $this->isPrivate = $this->isGroup = false;
		// anyting else to null.
		$this->text = $this->command = $this->params = $this->replyId = $this->updateId = $this->messageId = 
		$this->fromId = $this->fromUsername = $this->fromFirstName = $this->date = $this->chatId = 
		$this->chatUsername = $this->chatFirstName = $this->chatTitle = $this->joiner = $this->leaver = null;
	}

	/**
	 * parse
	 * - this method will do the parsing process of the message.
	 * - parsing the message will set all the attributes to their corresponding values.
	 * - a previous cleaning is required to avoid errors.
	 */
	public function parse($msg = array()) {

		$this->_clean_msg();

		if (empty($msg)) return false;

		$this->updateId = $msg['update_id'];
		$this->messageId = $msg['message']['message_id'];
		if (isset($msg['message']['date'])) $this->date = $msg['message']['date'];

		$this->_parseFrom($msg['message']);
		$this->_parseChat($msg['message']);
		$this->_parseJoin($msg['message']);
		$this->_parseLeave($msg['message']);
		$this->_parseText($msg['message']);

	}

	/**
	 * _parseFrom
	 * - this method will parse the from array and set the related attributes of the message.
	 * - is there is no information on from, then this attributes will be null.
	 */
	private function _parseFrom(& $message) {
		if (isset($message['from']) && !empty($message['from']) ){
			$this->fromId = $message['from']['id'];
			$this->fromUsername = $message['from']['username'];
			$this->fromFirstName = $message['from']['first_name'];
			if ($this->fromUsername == $this->botUsername) {
				$this->isFromBot = true;
			}
		}
	}

	/**
	 * _parseChat
	 * - this method will parse the chat array and set the related attributes of the message.
	 * - is there is no information on chat, then this attributes will be null.
	 * - chats can be private or from a group. Depending on that many things change.
	 */
	private function _parseChat(& $message) {
		if (isset($message['chat']) && !empty($message['chat']) ){
			$this->chatId = $message['chat']['id'];
			if ($this->chatId >= 0) {
				$this->isPrivate = true;
				$this->chatUsername = $message['chat']['username'];
				$this->chatFirstName = $message['chat']['first_name'];
			}
			else {
				$this->isGroup = true;
				$this->chatTitle = $message['chat']['title'];
			}
		}
	}

	/**
	 * _parseJoin
	 * - this method will parse the new_chat_participant array and set the related attributes of the message.
	 * - is there is no information on new_chat_participant, then this attributes will be null.
	 * - each participant will be parsed as an object with the same attributes and values as the original array.
	 * - in case the array contains the bot username, this user record will not be stored as a crew member.
	 */
	private function _parseJoin(& $message) {
		if (isset($message['new_chat_participant']) && !empty($message['new_chat_participant'])) {
			$this->isJoin = true;
			$this->joiner = null;
			if ($message['new_chat_participant']['username'] == $this->botUsername) $this->isBotJoin = true;
			else {
				$this->joiner = (object)array(
					'id' => $message['new_chat_participant']['id'],
					'first_name' => $message['new_chat_participant']['first_name'],
					'username' => $message['new_chat_participant']['username']
				);
			}
		}
	}

	/**
	 * _parseLeave
	 * - this method will parse the lave_chat_participant array and set the related attributes of the message.
	 * - is there is no information on lave_chat_participant, then this attributes will be null.
	 * - each participant will be parsed as an object with the same attributes and values as the original array.
	 * - in case the array contains the bot username, this user record will not be stored as a crew member.
	 */
	private function _parseLeave(& $message) {
		if (isset($message['left_chat_participant']) && !empty($message['left_chat_participant'])) {
			$this->isLeave = true;
			$this->leaver = null;
			if ($message['left_chat_participant']['username'] == $this->botUsername) $this->isBotLeave = true;
			else {
				$this->leaver = (object)array(
					'id' => $message['left_chat_participant']['id'],
					'first_name' => $message['left_chat_participant']['first_name'],
					'username' => $message['left_chat_participant']['username']
				);
			}
		}
	}

	/**
	 * _parseText
	 * - this method will parse the text field of the message if there is some.
	 * - the text could be a command, so first we should try to check if the text is a command
	 * - if the text is a command, then separate the command from possible params
	 */
	private function _parseText(& $message) {
		if (isset($message['text']) && !empty($message['text']) && mb_strlen($message['text']) > 0) {
			$this->text = $message['text'];
			if (preg_match($this->regExp_Command, $message['text'], $matches)) {
				$this->isCommand = true;
				$this->command = $matches[1];
				$this->params = $matches[2];
			}
		}
	}

	/* getters */

	public function isJoin() { return $this->isJoin; }
	public function isLeave() { return $this->isLeave; }
	public function isBotJoin() { return $this->isBotJoin; }
	public function isBotLeave() { return $this->isBotLeave; }
	public function joiner() { return $this->joiner; }
	public function leaver() { return $this->leaver; }

	public function isReply() { return $this->isReply; }
	public function replyId() { return $this->replyId; }

	public function isBot() { return $this->isBot; }
	public function isFromBot() { return $this->isFromBot; }
	
	public function date() { return $this->date; }
	public function updateId() { return $this->updateId; }
	public function messageId() { return $this->messageId; }
	
	public function text() { return $this->text; }
	public function isCommand() { return $this->isCommand; }
	public function command() { return $this->command; }
	public function params() { return $this->params; }
	
	public function fromId() { return $this->fromId; }
	public function fromUsername() { return $this->fromUsername; }
	public function fromFirstName() { return $this->fromFirstName; }
	
	public function chatId() { return $this->chatId; }
	
	public function isPrivate() { return $this->isPrivate; }
	public function chatUsername() { return $this->chatUsername; }
	public function chatFirstName() { return $this->chatFirstName; }
	
	public function isGroup() { return $this->isGroup; }
	public function chatTitle() { return $this->chatTitle; }

}
