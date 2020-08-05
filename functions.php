<?php

class vpnFunc {
	public function getUserID($data) {
		return $data->object->from_id;
	}

	public function getPeerID($data) {
		return $data->object->peer_id;
	}

	public function getNewMessages($data) {
		return $data->object->text;
	}
	
	public function getReplyUserID($data) {
		return $data->object->reply_message->from_id;
	}
	
	public function ValidateSteamID($authid)
	{
		return preg_match('#STEAM_1:0:[0-9]{5,12}#', $authid) ? true : false;
	}
}
