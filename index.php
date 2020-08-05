<?php
/*
	error code
	10001 - Неверная версия плагина
	10002 - Данный сервер отсутсвует в списке разрешенных
	10003 - Не указан IP адрес игрока
	10004 - Сервис Proxy не отвечает
	
*/

$config_vpn = [ // Массив конфига
	'version' => '2.0', // Текущая версия плагина
];

// ============================================================================================================================================== //

define('ROOT', dirname(__FILE__) . '/');

include('config.php');
include('functions.php');
include('classes/vk.class.php');

$vk = new VKAPI($config['VK_KEY'], $config['VERSION']); 
$data = json_decode(file_get_contents('php://input')); 

if ($data->type === 'confirmation') { 
    exit($config['ACCESS_KEY']); 
}

if(@$_GET['ok'] == '1') {
	$vk->sendOK();
}

$method = @$_GET['m'];
switch($method) {
	default: break;
	
	case 'cv':
		$g_srvip = $_GET['sip']; // Получаем ип сервера
		$g_srvport = $_GET['sp']; // Получаем порт сервера
		$srv_adress = $g_srvip.':'.$g_srvport; // Форматируем в строковое представление IP:PORT для сравнения
		
		$g_version = $_GET['v']; // Получаем версию плагина
		if($g_version != $config_vpn['version']) { // Проверяем актуальность версии плагина
			echo json_encode(array( 'status' => 'error', 'error_code' => '10001' )); // Выводим ошибку
			$message = 'Внимание! Данный сервер использует неактуальную версию плагина (ver. '.$g_version.') AntiVPN недоступен <br>IP:PORT сервера - '.$srv_adress;
			$vk->sendMessage($message, 2000000004);
			$notver = true; // Неактуальная версия
		}
		
		$servers = [ // Список разрешенных IP:PORT адресов, для работы с AntiVPN
			1 => '127.0.0.1:27213',
		];
		
		if(!$notver) {
			if (array_search ($srv_adress, $servers)) { // Делаем сверку на вхождение адреса сервера в наш список
			$isactivated = true; // Сервер прошел проверку
			} else {
				$isactivated = false; // Сервер не прошел проверку
				$message = 'Внимание! Был выполнен запрос подключения с сервера, который не имеет лицензии!<br>IP:PORT сервера - '.$srv_adress;
				$vk->sendMessage($message, 2000000004);
				echo json_encode(array( 'status' => 'error', 'error_code' => '10002' )); // Выводим ошибку
			}
		}
		
	break;
	
	case 'checkVPN':
		$g_playerip = $_GET['pip']; // Получаем IP адрес игрока
		$g_playerauthid = $_GET['auid']; // Получаем STEAMID  игрока

		
		if (!$g_playerip) { // Сервер прошел проверку, но не удалось получить IP адрес игрока
			$message = 'Внимание! Не удалось проверить игрока из-за отсутствия его IP адреса. <br>IP:PORT сервера - '.$srv_adress;
			$vk->sendMessage($message, 2000000004);
			echo json_encode(array( 'status' => 'error', 'error_code' => '10003' )); // Выводим ошибку
		}
			
		$wlist_ipaddr = [ // Белый лист VPN (Актуально для игроков с Украины, которые используют VPN)
			1 => '127.0.0.1', 
		];

		$keys = array( // Ключи доступа для сервиса проверки Proxy
			"181064-l84jy0-2g4z82-85899v", 
			"y55916-0867mr-p09794-08r4a8", 
			"45796l-10j903-7982p4-o18a2w", 
		);

		shuffle($keys); // Мешаем наш массив с ключами для рандомизации
		$key = $keys[0]; // Выбираем первый элемент из перемешанного массива

		if (!array_search ($g_playerip, $wlist_ipaddr)) { // Делаем сверку на вхождение IP адреса игрока в белый лист VPN
			$content = json_decode(file_get_contents("https://site.ru/".$g_playerip."?vpn=1&asn=1&key=".$key.""), true); // Запрос к сервису с указанием IP игрока и ключом доступа

			if($content['status'] == 'denied'){ // Если наш сервис Proxy не отвечает - уведомляем в беседу ВК
				echo json_encode(array( 'status' => 'error', 'error_code' => '10004' )); // Выводим ошибку
			} 

		} else {
			$content[$g_playerip]['proxy'] = 'no'; // Явно указали, что данный игрок не использует VPN, т.к. он находится в нашем Белом списке VPN
		}
		
		if($content[$g_playerip]['proxy'] == 'yes') { // Если наш сервис говорит, что игрок использует VPN
			if($g_playerauthid != 'BOT') { // Проверка на бота из самой игры
				echo json_encode(array( 'status' => "true", 'message' => 'VPN Detected')); // Выводим json ответ, что данного игрока необходимо кикнуть
				$message = 'Внимание!<br> Обнаружен VPN! IP адрес игрока - '.$g_playerip.'';
				$vk->sendMessage($message, 2000000004);
				
			}
		} else {
			echo json_encode(array( 'status' => "false", 'message' => 'VPN Undetected' )); // Выводим json ответ, что данный игрок не использует VPN
			
		}
	break;
	
}
// ============================================================================================================================================== //
	