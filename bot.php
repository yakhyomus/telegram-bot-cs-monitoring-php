<?php
/* Settings start */
$TOKEN = "YOU BOT TOKEN"; // Create your Telegram bot with @BotFather and get token
$command = '/info'; // Command to monitor
$url = 'https://example.com/maps/'; // The domain where map screenshots are located
$servers = [ // Enter the IP address of your server(s)
    '/server1' => '111.222.333.444:27015',
	/*
        '/server2' => '111.222.333.444:27016',
        '/server3' => '111.222.333.444:27017',
    */
];
/* Settings end */
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['message']['chat']['id'])) exit();
function sendTelegram($method, $response) {
    global $TOKEN;
	$ch = curl_init('https://api.telegram.org/bot' . $TOKEN . '/' . $method);  
	curl_setopt($ch, CURLOPT_POST, 1);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);
	curl_close($ch);
	return $res;
}

if (isset($data['message']['new_chat_members'])) {
	$user = $data['message']['from'];
	sendTelegram(
		'sendMessage', 
		array(
			'chat_id'               => $data['message']['chat']['id'],
			'reply_to_message_id'	=> $data['message']['message_id'],
			'parse_mode'	        => "HTML",
			'text'                  => "Welcome <a href='tg://user?id=" . $user['id'] . "'>" . $user['first_name'] . " " . $user['last_name'] . "</a>!\n",
		)
	);
    exit();
}

if (!empty($data['message']['text'])) {
    $text = $data['message']['text'];
    require_once './SourceQuery.php';
    $info = '';
    foreach($servers as $key => $server) {
		if (mb_stripos($text, $command) !== false) {
            $query = new SourceQuery(strstr($server, ':', true), str_replace(':', '', strstr($server, ':')));
            $infos = $query->getInfos();            
            $info .= $infos['name'] . "\nIP: <code>" . $infos['ip'] . ":" . $infos['port'] . "</code>\nMap: " . $infos['map'] . "\nPlayers: " . $infos['players'] . " из " . $infos['places'] . " " . $key . "\n\n";
        } else if(mb_stripos($text, $key) !== false) {
            $query = new SourceQuery(strstr($server, ':', true), str_replace(':', '', strstr($server, ':')));
            $infos = $query->getInfos();
			$players = $query->getPlayers();
			$playersInfos = $query->getPlayers();
			$caption = "Server: <code>" . $infos['name'] . "</code>\nIP: <code>" . $infos['ip'] . ":" . $infos['port'] . "</code>\nMap: <code>" . $infos['map'] . "</code>\n\n";
			if ($infos['players'] > 0) {
				$caption .= "<strong>Nick (Frags | Time)</strong>\n";
				foreach($playersInfos as $players) {
					$caption .= $players[ 'id' ] . ". 👤 <code>" . $players['name'] . "</code> (<code>" . $players[ 'score' ] . "</code> | <code>" . date("H:i:s", $players[ 'time' ]) . "</code>)\n";
				}
			} else $caption .= '<code>There are currently no online players on the server</code>';
            sendTelegram(
				'sendPhoto', 
				array(
					'chat_id'                   => $data['message']['chat']['id'],
					'photo'                     => $url . $infos['map'].'.jpg',
					'caption'                   => $caption,
					'parse_mode'                => "HTML",
					'disable_web_page_preview'  => true,
					'reply_to_message_id'	    => $data['message']['message_id']
				)
			);
            exit();
        }
    }
    sendTelegram(
        'sendMessage', 
        array(
            'chat_id'                   => $data['message']['chat']['id'],
            'text'                      => $info,
            'parse_mode'                => "HTML",
            'disable_web_page_preview'  => true,
            'reply_to_message_id'	    => $data['message']['message_id']
        )
    );
    exit();
}