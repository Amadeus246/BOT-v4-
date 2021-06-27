<?php
    date_default_timezone_set("Europe/Moscow");
    $today = date("l");
    if (file_exists('rasp.pdf') && $today == "Sunday") {
        unlink('rasp.pdf');
    }