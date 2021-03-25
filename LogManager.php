<?php

class LogManager{
    public static function debug($msg){
        //date_default_timezone_set('America\Brasilia');
        echo date('Y-m-d H:i:s'). ' - ' . $msg . PHP_EOL;
    }
}