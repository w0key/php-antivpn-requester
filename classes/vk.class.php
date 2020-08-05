<?php

class VKAPI {
    /**
     * Токен
     * @var string
     */
    private $token = '';
    private $v = '';
    /**
     * @param string $token Токен
     */
    public function __construct($token, $v){
        $this->token = $token;
        $this->v = $v;
    }
	
    public function sendMessage($message, $sendID){
        if ($sendID != 0 and $sendID != '0') {
            return $this->request('messages.send', array('message'=>$message, 'peer_id'=> $sendID, 'random_id' => rand(-2147483648, 2147483647), 'title' => 'ЫЫЫЫ'));
        } else {
            return true;
        }
	 } 


    public function sendOK(){
        echo 'ok';
        $response_length = ob_get_length();
 
        ignore_user_abort(true);

        ob_start();
        $serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
        header($serverProtocole.' 200 OK');
        header('Content-Encoding: none');
        header('Content-Length: '. $response_length);
        header('Connection: close');

        ob_end_flush();
        ob_flush();
        flush();
    }
	
	public function sendButton($sendID, $message, $gl_massiv = [], $one_time = False) {
        $buttons = [];
        $i = 0;
        foreach ($gl_massiv as $button_str) {
            $j = 0;
            foreach ($button_str as $button) {
                $color = $this->replaceColor($button[2]);
                $buttons[$i][$j]["action"]["type"] = "text";
                if ($button[0] != null)
                    $buttons[$i][$j]["action"]["payload"] = json_encode($button[0], JSON_UNESCAPED_UNICODE);
                $buttons[$i][$j]["action"]["label"] = $button[1];
                $buttons[$i][$j]["color"] = $color;
                $j++;
            }
            $i++;
        }
        $buttons = array(
            "one_time" => $one_time,
            "buttons" => $buttons);
        $buttons = json_encode($buttons, JSON_UNESCAPED_UNICODE);
        //echo $buttons;
        return $this->request('messages.send',array('message'=>$message, 'peer_id'=>$sendID, 'keyboard'=>$buttons, 'random_id' => rand(-2147483648, 2147483647)));
    }
	
	private function replaceColor($color) {
        switch ($color) {
            case 'red':
                $color = 'negative';
                break;
            case 'green':
                $color = 'positive';
                break;
            case 'white':
                $color = 'default';
                break;
            case 'blue':
                $color = 'primary';
                break;

            default:
                # code...
                break;
        }
        return $color;
    }

    public function savePhoto($photo, $server, $hash){
        return $this->request('photos.saveMessagesPhoto',array('photo'=>$photo, 'server'=>$server, 'hash' => $hash));
    }
	
	 public function sendDocuments($sendID, $selector = 'doc'){
        if ($selector == 'doc')
            return $this->request('docs.getMessagesUploadServer',array('type'=>'doc','peer_id'=>$sendID));
        else
            return $this->request('photos.getMessagesUploadServer',array('peer_id'=>$sendID));
    }

    public function request($method,$params=array()){
        $url = 'https://api.vk.com/method/'.$method;
        $params['access_token']=$this->token;
        $params['v']=$this->v;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type:multipart/form-data"
            ));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $result = json_decode(curl_exec($ch), True);
            curl_close($ch);
        } else {
            $result = json_decode(file_get_contents($url, true, stream_context_create(array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($params)
                )
            ))), true);
        }
        if (isset($result['response']))
            return $result['response'];
        else
            return $result;
    }

	public function sendDocMessage($id, $local_file_path, $title = null, $params = []) {
        $upload_file = current($this->uploadDocsMessages($id, $local_file_path, $title));
        if ($id != 0 and $id != '0') {
            return $this->request('messages.send', array('attachment'=> 'doc' . $upload_file['owner_id'] . "_" . $upload_file['id'], 'peer_id' => $id, 'random_id' => '0', 
			'random_id' => rand(-2147483648, 2147483647)));
        } else {
            return true;
        }
    }
	
	private function uploadDocsMessages($id, $local_file_path, $title = null) {
        if (!isset($title))
            $title = preg_replace("!.*?/!", '', $local_file_path);
        $upload_url = $this->getUploadServerMessages($id)['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], $title);
        return $upload_file;
    }
	
	private function saveDocuments($file, $title) {
        return $this->request('docs.save', ['file' => $file, 'title' => $title]);
    }
	
	private function getUploadServerMessages($peer_id, $selector = 'doc') {
        $result = null;
        if ($selector == 'doc')
            $result = $this->request('docs.getMessagesUploadServer', ['type' => 'doc', 'peer_id' => $peer_id]);
        else if ($selector == 'photo')
            $result = $this->request('photos.getMessagesUploadServer', ['peer_id' => $peer_id]);
        else if ($selector == 'audio_message')
            $result = $this->request('docs.getMessagesUploadServer', ['type' => 'audio_message', 'peer_id' => $peer_id]);
        return $result;
    }
	
	private function uploadDocs($id, $local_file_path, $title = null) {
        if (!isset($title))
            $title = preg_replace("!.*?/!", '', $local_file_path);
        $upload_url = $this->getUploadServerPost($id)['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], $title);
        return $upload_file;
    }
	
    private function sendFiles($url, $local_file_path, $type = 'file') {
        $post_fields = array(
            $type => new CURLFile(realpath($local_file_path))
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type:multipart/form-data"
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        $output = curl_exec($ch);
        return $output;
    }
	
    public function sendTextAndImage($peer_id, $message, $local_file_path)
    {
        $upload_url = $this->sendDocuments($peer_id, 'photo')['upload_url'];

        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);

        $upload_file = $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);

        $this->request('messages.send', array('message'=>$message, 'attachment' => "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'], 'peer_id' => $peer_id, 'random_id' => rand(-2147483648, 2147483647)));
		
        return true;
    }
	
	public function getAlias($id, $n = null) { //получить обращение к юзеру или группе
        if (!is_numeric($id)) { //если короткая ссылка
            $obj = $this->request('utils.resolveScreenName', ['screen_name' => $id]); //узнаем, кому принадлежит, сообществу или юзеру
            $id = ($obj["type"] == 'group') ? -$obj['object_id'] : $obj['object_id'];
        }
        if (isset($n)) {
            if (is_string($n)) {
                if ($id < 0)
                    return "@club" . ($id * -1) . "($n)";
                else
                    return "@id{$id}($n)";
            } else {
                if ($id < 0) {
                    $id = -$id;
                    $group_name = $this->request('groups.getById', ['group_id' => $id])[0]['name'];
                    return "@club{$id}({$group_name})";
                } else {
                    $info = $this->userInfo($id);
                    if ($n)
                        return "@id{$id}($info[first_name] $info[last_name])";
                    else
                        return "@id{$id}($info[first_name])";
                }
            }
        } else {
            if ($id < 0)
                return "@club" . ($id * -1);
            else
                return "@id{$id}";
        }
    }
}
