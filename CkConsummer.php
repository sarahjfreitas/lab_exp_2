<?php

error_reporting(E_ERROR);
const CSV_DELIMITER = ';';

require_once __DIR__ . '\vendor\autoload.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'FileUtils.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'MathUtils.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'DbUtils.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RabbitConsumer.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'LogManager.php';

class CkConsummer extends RabbitConsumer {
    public function process($msg){

        try {
            $name = $msg->body;
            LogManager::debug('processando '.$name);
            $repositorio = DbUtils::getByName($name);
            $dirName = 'repo/'.str_replace('/','_',$name);
            
            DbUtils::changeStatus($name,RepositorioStatus::Processando);
            exec("java -jar ck.jar $dirName false 0 false");
            LogManager::debug('metricas coletadas');
            $classResult = FileUtils::readFullCsv('class.csv',',');

            if(empty($classResult)){
                LogManager::debug('Metricas vazias. Continuando.');
                DbUtils::changeStatus($name,RepositorioStatus::Processado);
            }
            else{
                $cbo = MathUtils::median(array_column($classResult,'cbo'));
                $dit = MathUtils::median(array_column($classResult,'dit'));
                $loc = array_sum(array_column($classResult,'loc'));
                $lcom = array_sum(array_column($classResult,'lcom'));

                DbUtils::saveResult($repositorio['id_repositorio'],$cbo,$dit,$loc,$lcom);
                LogManager::debug('resultado salvo');
            }

            unlink('class.csv');
            unlink('method.csv');
            FileUtils::rrmdir($dirName);
            LogManager::debug('limpeza executada');
        } catch (Exception $e) {
            LogManager::debug('ERRO INESPERADO');
            LogManager::debug($e->getMessage());
        }

    }

    public function run(){
        set_time_limit(0);
        while(true){
            $this->consumme('ckMessage',[$this, 'process']);
            sleep(1);
        }
    }
}

(new CkConsummer())->run();