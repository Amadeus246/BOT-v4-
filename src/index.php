<?php
//    include("pages/header.html");
    require_once("vendor/autoload.php");
    use Smalot\PdfParser\Parser;
    //создаём объект класса Parser из библиотеки pdfParser
    $parser = new Parser();
    //реагируем только на запросы, иначе код не выполняется
    if (!isset($_REQUEST)) {
        return;
    }
    date_default_timezone_set("Europe/Moscow");
    $today = date("l");
    $file = 'rasp.pdf';
	$confirm_string = "a73b2508";
	$access_token = '4a24b35a69022212dd90fa48e1bb3dfaa7dc4f17c97eb79f23188ae3cea5be72a1d870f56cfaf99452e8d';
    $got_ex_num_group =  "";
    $bells = "photo-200668000_457325792";
    $get_page = file_get_contents("https://narfu.ru/ltk/obrazovatelnaya/raspisanie/");
    $url_narfu = "https://narfu.ru";
    preg_match_all('/<a[^>]*class="[^"]*link-to-pdf[^"]*"[^>]*>[^<]*<\/a>/', $get_page, $match);
    $src_rasp = preg_match_all('/href="([^"]+)"/', $match[0][2], $link);
    $rasp = file_get_contents($url_narfu.implode("", $link[1]));
    //загружаем данные в созданный pdf-файл
    file_put_contents($file, $rasp);
    //выбираем файл, который будем парсить и получаем из него текст
    $rasp_txt = $parser->parseFile('rasp.pdf')->getText();
    preg_match_all("/Ы.4.+н/", $rasp_txt, $num_groups);
    $num_groups = preg_replace("/Ы./","", $num_groups[0]);
    $num_groups = preg_replace("/.н/","", $num_groups);
    $num_groups = preg_replace("/\t/","", $num_groups);
    $str_num_groups = implode(", ", $num_groups);

    //функция ответа
    function vk_msg_send($peer_id, $msg_reply, $token, $attach){
        $request_params_msg = array(
            'random_id' => rand().time(),
            'peer_id' => $peer_id,
            'attachment' => $attach,
            'message' => $msg_reply,
            'group_id' => '200668000',
            'access_token' => $token,
            'v' => '5.122'
        );
        //преобразуем в строку и добавляем между параметрами амперсанд &
        $get_params_msg = http_build_query($request_params_msg);
        //отправляем ответ (ссылку) VK API
        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params_msg);
    }
    function vk_keybrd_send($peer_id, $msg_reply, $token, $n_group, $attach){
        $request_params_msg = array(
            'random_id' => rand().time(),
            'peer_id' => $peer_id,
            'attachment' => $attach,
            'message' => $msg_reply,
            'group_id' => '200668000',
            'keyboard' => '{
    		                "one_time": false,
    		                "buttons": [
    		                     [{
    		                        "action": {
    		                            "type": "text",
    		                            "payload": "{\"button\": \"1\"}",
    		                            "label": "сегодня|'.$n_group.'"
    		                        },
    		                        "color": "positive"
    		                     },
    		                     {
    		                        "action": {
    		                            "type": "text",
    		                            "payload": "{\"button\": \"2\"}",
    		                            "label": "завтра|'.$n_group.'"
    		                        },
    		                        "color": "negative"
    		                     },
    		                     {
    		                        "action": {
    		                            "type": "text",
    		                            "payload": "{\"button\": \"3\"}",
    		                            "label": "неделя|'.$n_group.'"
    		                        },
    		                        "color": "primary"
    		                     }
    		                    ]
    		                 ]
    		                }',
            'access_token' => $token,
            'v' => '5.122'
        );
        $get_params_msg = http_build_query($request_params_msg);
        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params_msg);
    }

//    получаем запрос от VK API, декодируем в php код
    $data = json_decode(file_get_contents('php://input'));

//    смотрим какой тип уведомления в поле "type"
    switch ($data->type) {
        //если уведомление подтверждения адреса сервера
        case 'confirmation':
            //то отправляем строку подтверждения
            echo $confirm_string;
        break;

        //если уведомление о новом сообщении
        case 'message_new':
            //получаем текст пользователя
            $msg_txt = $data -> object -> message -> text;
            //получаем id пользователя
            $user_id = $data -> object -> message -> peer_id;
            //получаем id сообщения
            $id_msg = $data -> object -> message -> conversation_message_id;

            $check_name = preg_match("/Бот! /iu", $msg_txt);
            if ($id_msg % 9 == 0 && $check_name == 0 && preg_match("/сегодня|завтра|неделя|начать/iu",$msg_txt) == null) {
                vk_msg_send($user_id, "Чтобы обратиться к боту, напиши 'Бот!' без кавычек. Не забудь поставить пробел между обращением и твоим сообщением. Если хочешь узнать расписание, то пиши -\n'Бот! расп №группы', чтобы выбрать нужную тебе группу.\n\nПример: Бот! расп 4016. Чтобы узнать полный список команд, пиши 'Бот! команды'.", $access_token, "");
            }

            //    получаем номер группы
            for ($i=0; $i <= count($num_groups); $i++) {
                if (preg_match("/\b$num_groups[$i]\b/", $msg_txt, $num[$i])) {
                    $got_ex_num_group = $num[$i][0];
                    break;
                }
            }
            if ($got_ex_num_group == "") {
                $got_ex_num_group = "0";
            }
            $next_group = $num_groups[$i+1];
            if (preg_match("/4\d\t\d\d/",$rasp_txt,$new_nmb)) {
                $new_nmb = preg_replace("/\t/","",$new_nmb[0]);
                $rasp_txt = preg_replace("/4\d\t\d\d/","$new_nmb",$rasp_txt);
            }

            //ищем расписание группы с таким номером и сохраняем в переменную "rasp_txt"
            if ($next_group == null) {
                $rasp_txt = trim(preg_match("/г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+\n(.+\n)+.+|г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+.+|г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+\n.+/iu",$rasp_txt, $value));
                $rasp_txt = $value[0];
            }
            else {
                preg_match("/г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+\n(.+\n)+.+г.?р.?у.?п.?п.?ы.$next_group|г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+.+г.?р.?у.?п.?п.?ы.$next_group|г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+\n.+г.?р.?у.?п.?п.?ы.$next_group|г.?р.?у.?п.?п.?ы.$got_ex_num_group(.+\n)+\n.+$next_group/iu", $rasp_txt, $value);
                $rasp_txt = $value[0];
                $rasp_txt = trim(preg_replace("/р.+г.+.$next_group/iu","", $rasp_txt));
            }

            $rasp_txt = preg_split("/\d\d:\d\d/is", $rasp_txt);
            array_splice($rasp_txt,0,1);
            for ($i = 0; $i < count($rasp_txt); $i++) {
                if (!(preg_match_all("/[а-я].?\t-\t/is",$rasp_txt[$i]))) {
                    $rasp_txt[$i] = preg_replace("/\t-\t/", "@", $rasp_txt[$i]);
                }
                preg_match_all("/ауд|-----/is", $rasp_txt[$i], $str);
                preg_match_all("/ауд/is", $rasp_txt[$i], $str2);
                preg_match_all("/-------/is", $rasp_txt[$i], $str3);
                preg_match_all("/\t?\s\t\s\t/is", $rasp_txt[$i], $str4);

                if (preg_match_all("/ауд|-----/is", $rasp_txt[$i], $str) && preg_match_all("/ауд/is", $rasp_txt[$i], $str2) && preg_match_all("/\t?\s\t\s\t/is", $rasp_txt[$i], $str4)) {
                    if (count((array)$str[0]) < 6) {
                        $rasp_txt[$i] = preg_replace("/\t?\s\t\s\t/is", "%%", $rasp_txt[$i]);
                        $rasp_txt[$i] = preg_split("/%/is", $rasp_txt[$i]);
                    }
                }
                else if (preg_match_all("/ауд|-----/is", $rasp_txt[$i], $str) && !(preg_match_all("/-------/is", $rasp_txt[$i], $str3))) {
                    if (count((array)$str[0]) < 6 && !(preg_match_all("/[а-я]/is",implode("", $rasp_txt[$i-1])))) {
                        $rasp_txt[$i] = preg_split("/\t?\s\t\s\t/is", $rasp_txt[$i]);
                    }
                }

                if ((gettype($rasp_txt[$i])) == "string") {
                    $rasp_txt[$i] = preg_split("/\t\s|\s\t/is", $rasp_txt[$i]);
                }
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match_all("/\n\d/is", $rasp_txt[$i][$j]) && preg_match_all("/ауд|---/is", $rasp_txt[$i][$j])) {
                        $rasp_txt[$i][$j] = preg_replace("/\n\d/is", "", $rasp_txt[$i][$j]);
                    }
                    else {
                        $rasp_txt[$i][$j] = preg_replace("/\n\d/is", "@", $rasp_txt[$i][$j]);
                    }
                    if (preg_match_all("/р.?а.?с.?п.?и.?с.?а.?н.?и.?е.+/is", $rasp_txt[$i][$j]) && preg_match_all("/ауд|---/is", $rasp_txt[$i][$j])) {
                        $rasp_txt[$i][$j] = preg_replace("/р.?а.?с.?п.?и.?с.?а.?н.?и.?е.+/is", "", $rasp_txt[$i][$j]);
                    }
                    else {
                        $rasp_txt[$i][$j] = preg_replace("/р.?а.?с.?п.?и.?с.?а.?н.?и.?е.+/is","@",$rasp_txt[$i][$j]);
                    }
                    if (!preg_match_all("/ауд/is",$rasp_txt[$i][$j]) && preg_match_all("/[а-я]+/is",$rasp_txt[$i][$j]) && $rasp_txt[$i][$j+1] && preg_match_all("/ауд/is",$rasp_txt[$i][$j+1])) {
                        $rasp_txt[$i][$j] = $rasp_txt[$i][$j].$rasp_txt[$i][$j+1];
                        $rasp_txt[$i][$j+1] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j+1]);
                    }
                    if (!(preg_match_all("/[а-я].+ауд/is",$rasp_txt[$i][$j])) && preg_match_all("/ауд/is",$rasp_txt[$i][$j])) {
                        $rasp_txt[$i][$j-1] = $rasp_txt[$i][$j-1].$rasp_txt[$i][$j];
                        $rasp_txt[$i][$j] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j]);
                    }
                }
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (!preg_match_all("/ауд/is", $rasp_txt[$i][$j]) && preg_match_all("/[а-я]+/is", $rasp_txt[$i][$j]) && $rasp_txt[$i][$j+1] && preg_match_all("/ауд/is", $rasp_txt[$i][$j+1])) {
                        $rasp_txt[$i][$j] = $rasp_txt[$i][$j].$rasp_txt[$i][$j+1];
                        $rasp_txt[$i][$j+1] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j+1]);
                    }
                }
                $noEmptyCells = 0;
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match_all("/[а-я]|-/is", $rasp_txt[$i][$j])){
                        $noEmptyCells++;
                    }
                }
                if ($noEmptyCells == 7) {
                    for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                        if (preg_match_all("/\/.?$/is", $rasp_txt[$i][$j]) && $rasp_txt[$i][$j + 1] && preg_match_all("/[а-я]/is", $rasp_txt[$i][$j + 1]) && !(preg_match_all("/\//is", $rasp_txt[$i][$j + 1]))) {
                            $rasp_txt[$i][$j] = $rasp_txt[$i][$j].$rasp_txt[$i][$j + 1];
                            $rasp_txt[$i][$j + 1] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j + 1]);
                        }
                        if (preg_match_all("/\/.?$/is", $rasp_txt[$i][$j]) && !(preg_match_all("/[а-я]/is", $rasp_txt[$i][$j + 1])) && $rasp_txt[$i][$j + 2] && preg_match_all("/[а-я]/is", $rasp_txt[$i][$j + 2])) {
                            $rasp_txt[$i][$j] = $rasp_txt[$i][$j].$rasp_txt[$i][$j + 2];
                            $rasp_txt[$i][$j + 2] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j + 2]);
                        }
                        if (preg_match_all("/^.?\//is", $rasp_txt[$i][$j]) && preg_match_all("/[а-я]/is", $rasp_txt[$i][$j - 1])) {
                            $rasp_txt[$i][$j - 1] = $rasp_txt[$i][$j - 1].$rasp_txt[$i][$j];
                            $rasp_txt[$i][$j] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j]);
                        }
                        if (!preg_match_all("/[а-я]|-/is", $rasp_txt[$i][$j])) {
                            $rasp_txt[$i][$j] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j]);
                        }
                    }
                }
                if ($noEmptyCells == 6) {
                    for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                        if (!(preg_match_all("/[а-я]|-/is", $rasp_txt[$i][$j]))) {
                            $rasp_txt[$i][$j] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j]);
                        }
                    }
                }
                if ($noEmptyCells == 5) {
                    for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                        if (preg_match_all("/\//is", $rasp_txt[$i][$j])) {
                            preg_match_all("/^.+\//is", $rasp_txt[$i][$j], $txt);
                            preg_match_all("/\/.+$/is", $rasp_txt[$i][$j], $txt2);
                            $txt = preg_replace("/\//is","", $txt[0]);
                            $txt2 = preg_replace("/\//is","", $txt2[0]);
                            $rasp_txt[$i][$j] = $txt[0];
                            array_splice($rasp_txt[$i],$j+1,0, $txt2[0]);
                        }
                    }
                    for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                        if (!(preg_match_all("/[а-я]|-/is",$rasp_txt[$i][$j]))) {
                            $rasp_txt[$i][$j] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j]);
                        }
                    }
                }
                if ($noEmptyCells < 5) {
                    if (preg_match_all("/[а-я]/is",implode("", $rasp_txt[$i])) && !(preg_match_all("/ауд/is", implode("", $rasp_txt[$i-1]))) && preg_match_all("/[а-я]/is", implode("", $rasp_txt[$i-1]))) {
                        for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                            if (!(preg_match_all("/[а-я]|-/is", $rasp_txt[$i][$j]))) {
                                $rasp_txt[$i][$j] = preg_replace("/.*/is", "@", $rasp_txt[$i][$j]);
                            }
                        }
                        $v = 0;
                        $txtStr = array();
                        $txt = array();
                        for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                            if (preg_match_all("/[а-я]/is", $rasp_txt[$i][$j])) {
                                array_push($txtStr, $rasp_txt[$i][$j]);
                            }
                        }
                        for ($j = 0; $j < count($rasp_txt[$i-1]); $j++) {
                            if (preg_match_all("/---/is", $rasp_txt[$i-1][$j])) {
                                array_push($txt,"#");
                            }
                            if (preg_match_all("/[а-я]/is", $rasp_txt[$i-1][$j])) {
                                array_push($txt, $txtStr[$v]);
                                $v++;
                            }
                        }
                        $rasp_txt[$i] = $txt;

                    } else if (preg_match_all("/[а-я]/is", implode("", $rasp_txt[$i]))) {
                        $v = 0;
                        $txt = array();
                        for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                            if (preg_match_all("/[а-я]/is", $rasp_txt[$i][$j])) {
                                $v++;
                            }
                        }
                        for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                            if (preg_match_all("/[а-я]/is", $rasp_txt[$i][$j]) && $v) {
                                array_push($txt, $rasp_txt[$i][$j]);
                                $v--;
                            }
                            if (!(preg_match_all("/[а-я]|-----/is", $rasp_txt[$i][$j])) && $v) {
                                array_push($txt, "-------");
                            }
                        }
                        $rasp_txt[$i] = $txt;
                        $len = count($txt);

                        if ($len < 6) {
                            for ($j = 0; $j < 6; $j++) {
                                if ($j < $len) {
                                    $gg = "hello";
                                } else {
                                    $rasp_txt[$i][$j] = '-------';
                                }
                            }
                        }
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                if (count($rasp_txt[$i]) == 1) {
                    array_splice($rasp_txt, $i,1);
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match_all("/@/is", $rasp_txt[$i][$j])) {
                        array_splice($rasp_txt[$i], $j,1);
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match_all("/@/is", $rasp_txt[$i][$j])) {
                        array_splice($rasp_txt[$i], $j,1);
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (!preg_match_all("/ауд/is",$rasp_txt[$i][$j]) && preg_match_all("/[а-я]+/is",$rasp_txt[$i][$j]) && $rasp_txt[$i+1][$j] && preg_match_all("/ауд/is", $rasp_txt[$i+1][$j])) {
                        $rasp_txt[$i][$j] = $rasp_txt[$i][$j].$rasp_txt[$i+1][$j];
                        $rasp_txt[$i+1][$j] = "@";
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match_all("/@/is", $rasp_txt[$i][$j]) || preg_match_all("/#/is", $rasp_txt[$i][$j])) {
                        array_splice($rasp_txt[$i], $j,1);
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match_all("/@/is", $rasp_txt[$i][$j]) || preg_match_all("/#/is", $rasp_txt[$i][$j])) {
                        array_splice($rasp_txt[$i], $j,1);
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                if (count($rasp_txt[$i]) == 1) {
                    array_splice($rasp_txt, $i,1);
                }
            }
            $monday = $tuesday = $wednesday = $thursday = $friday = $saturday = array();
            for ($i = 0; $i < count($rasp_txt); $i++) {
                for ($j = 0; $j < count($rasp_txt[$i]); $j++) {
                    if (preg_match("/\t|\n/",$rasp_txt[$i][$j])) {
                        $rasp_txt[$i][$j] = preg_replace("/\t|\n/","", $rasp_txt[$i][$j]);
                    }
                }
            }
            for ($i = 0; $i < count($rasp_txt); $i++) {
                array_push($monday, ($i+1)." ".trim($rasp_txt[$i][0]));
                array_push($tuesday, ($i+1)." ".trim($rasp_txt[$i][1]));
                array_push($wednesday,($i+1)." ".trim($rasp_txt[$i][2]));
                array_push($thursday, ($i+1)." ".trim($rasp_txt[$i][3]));
                array_push($friday, ($i+1)." ".trim($rasp_txt[$i][4]));
                array_push($saturday, ($i+1)." ".trim($rasp_txt[$i][5]));
            }
            $mon = "******************\nПОНЕДЕЛЬНИК\n******************\n".implode(PHP_EOL, preg_replace("/-/","—", $monday));
            $tue = "***********\nВТОРНИК\n***********\n".implode(PHP_EOL, preg_replace("/-/","—", $tuesday));
            $wed = "********\nСРЕДА\n********\n".implode(PHP_EOL, preg_replace("/-/","—", $wednesday));
            $thu = "**********\nЧЕТВЕРГ\n**********\n".implode(PHP_EOL, preg_replace("/-/","—", $thursday));
            $fri = "***********\nПЯТНИЦА\n***********\n".implode(PHP_EOL, preg_replace("/-/","—", $friday));
            $sat = "***********\nСУББОТА\n***********\n".implode(PHP_EOL, preg_replace("/-/","—", $saturday));
            $sun = 'photo-200668000_457325793';
            $week = $mon."\n".$tue."\n".$wed."\n".$thu."\n".$fri."\n".$sat;

            //функция рандомных фраз
            function random_phrase(): string
            {
                $phrases = array('Хорошая погодка сегодня, не правда ли?', 'Воспользуйся клавиатурой', 'Попробуй изъясняться по-другому', 'У меня нет ответа...',
                    'Прости, я тебя не понимаю...', 'На это я могу ответить лишь "..."', 'Я даже не знаю что тебе ответить на это...',
                    'Сори, я ничего не поняв', 'Эмм...?', 'Просто представь эти глазки из шрека (0^0)', 'Я бы тебе помог, но у меня лапки', 'Прмоаоап',
                    'Хорошо пообщались', '是的', 'Ну, допустим');
                return $phrases[array_rand($phrases)];
            }
            //функция рандомных привет
            function random_hello(): string
            {
                $phrases = array('Привет!', 'Здравствуйте', 'Привет', 'Приветствую!', 'Привет, ты человек?');
                return $phrases[array_rand($phrases)];
            }
            //функция рандомных пока
            function random_bye(): string {
                $phrases = array('До свидания', 'До встречи', 'Пока', 'Я буду скучать', 'Бай!');
                return $phrases[array_rand($phrases)];
            }

            //функция выбора расписания на сегодня
            function select_rasp_today($today, $mon, $tue, $wed, $thu, $fri, $sat, $sun) {
                if ($today == "Monday") {
                    return $mon;
                }
                else if ($today == "Tuesday") {
                    return $tue;
                }
                else if ($today == "Wednesday") {
                    return $wed;
                }
                else if ($today == "Thursday") {
                    return $thu;
                }
                else if ($today == "Friday") {
                    return $fri;
                }
                else if ($today == "Saturday") {
                    return $sat;
                }
                else {
                    return $sun;
                }
            }
            //функция выбора расписания на завтра
            function select_rasp_tomorrow($today, $mon, $tue, $wed, $thu, $fri, $sat, $sun) {
                if ($today == "Monday") {
                    return $tue;
                }
                else if ($today == "Tuesday") {
                    return $wed;
                }
                else if ($today == "Wednesday") {
                    return $thu;
                }
                else if ($today == "Thursday") {
                    return $fri;
                }
                else if ($today == "Friday") {
                    return $sat;
                }
                else if ($today == "Saturday") {
                    return $sun;
                }
                else {
                    return $mon;
                }
            }
            //сравниваем полученный текст с нашими строками
            if (preg_match("/^Начать/iu", $msg_txt)) {
                vk_msg_send($user_id, "Привет! Чтобы обратиться к боту, напиши 'Бот!' без кавычек. Не забудь поставить пробел между обращением и твоим сообщением. Если хочешь узнать расписание, то пиши -\n'Бот! расп №группы', чтобы выбрать нужную тебе группу.\n\nПример: Бот! расп 4016. Чтобы узнать полный список команд, пиши 'Бот! команды'.", $access_token, "");
            }
            else if (preg_match("/сегодня\|$got_ex_num_group/iu", $msg_txt) && $got_ex_num_group != "0") {
                vk_keybrd_send($user_id, select_rasp_today($today, $mon, $tue, $wed, $thu, $fri, $sat, ""), $access_token, $got_ex_num_group, select_rasp_today($today, $mon, $tue, $wed, $thu, $fri, $sat, $sun));
            }
            else if (preg_match("/завтра\|$got_ex_num_group/iu", $msg_txt) && $got_ex_num_group != "0") {
                vk_keybrd_send($user_id, select_rasp_tomorrow($today, $mon, $tue, $wed, $thu, $fri, $sat, ""), $access_token, $got_ex_num_group, select_rasp_tomorrow($today, $mon, $tue, $wed, $thu, $fri, $sat, $sun));
            }
            else if (preg_match("/неделя\|$got_ex_num_group/iu", $msg_txt) && $got_ex_num_group != "0") {
                vk_keybrd_send($user_id, $week, $access_token, $got_ex_num_group, "");
            }
            if ($check_name != 0){
                //удаляем обращение, чтобы легче было сравнивать текст
                $msg_txt = preg_replace("/Бот! /iu","", $msg_txt);
                if (preg_match("/Команды/iu", $msg_txt)) {
                    //вызываем функцию и отправляем в неё полученные значения
                    vk_msg_send($user_id, "1. 'Бот! расп №группы' - выбор нужной группы, чтобы узнать расписание.\n2. 'сегодня, завтра или неделя|№группы' - отправляет расписание нужной группы на сегодня, завтра или неделю.\n3. 'Бот! звонки' - отправляет расписание звонков.\n4. 'Бот! группы' - выводит список групп.", $access_token, "");
                }
                else if (preg_match("/Привет/iu", $msg_txt)) {
                    //вызываем функцию и отправляем в неё полученные значения
                    vk_msg_send($user_id, random_hello(), $access_token, "");
                }
                else if (preg_match("/Пока/iu", $msg_txt)) {
                    //вызываем функцию и отправляем в неё полученные значения
                    vk_msg_send($user_id, random_bye(), $access_token, "");
                }
                else if (preg_match("/(Расписание|Расп) $got_ex_num_group/iu", $msg_txt) && $got_ex_num_group != "0") {
                    vk_keybrd_send($user_id, "Теперь воспользуйся клавиатурой, чтобы узнать расписание на сегодня, завтра или неделю. Клавиатура может не работать в сторонних приложениях, пользуйтесь официальным приложением VK, установив его актуальную версию.\n\nЕсли у тебя всё ещё не отображается клавиатура, то можешь воспользоваться текстовой командой без обращения, \nуказав расписание на сегодня, завтра или неделю -\n'неделя|№группы'.\n\nПример: неделя|4016. Чтобы узнать полный список команд, пиши 'Бот! команды'.", $access_token, $got_ex_num_group, "");
                }
                else if (preg_match("/(Расписание|Расп) [0-9]+/iu", $msg_txt) && $got_ex_num_group == "0") {
                    vk_msg_send($user_id, "Группа не найдена. Если группа была добавлена, напишите в поддержку.", $access_token, "");
                }
                else if (preg_match("/Звонки/iu", $msg_txt)) {
                    vk_msg_send($user_id, "", $access_token, $bells);
                }
                else if (preg_match("/Группы/iu", $msg_txt)) {
                    vk_msg_send($user_id, $str_num_groups, $access_token, "");
                }
                else if (preg_match("/Начать/iu", $msg_txt)) {
                    vk_msg_send($user_id, "Привет! Чтобы обратиться к боту, напиши 'Бот!' без кавычек. Не забудь поставить пробел между обращением и твоим сообщением. Если хочешь узнать расписание, то пиши -\n'Бот! расп №группы', чтобы выбрать нужную тебе группу.\n\nПример: Бот! расп 4016. Чтобы узнать полный список команд, пиши 'Бот! команды'.", $access_token, "");
                }
                else {
                    vk_msg_send($user_id, random_phrase(), $access_token, "");
                }
            }
            echo 'ok';
        break;
    }


















