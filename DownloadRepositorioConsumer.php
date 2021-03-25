<?php

require_once __DIR__ . '\vendor\autoload.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'DbUtils.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RepositorioStatus.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RabbitConsumer.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RabbitMessager.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'FileUtils.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'LogManager.php';


const GITHUB_ROOT = 'https://github.com/';

class DownloadRepositorioConsumer extends RabbitConsumer{
    public function download($msg){
        try {
            $name = $msg->body;
            $dirName = 'repo/'.str_replace('/','_',$name);
            LogManager::debug('iniciando download de '.$name);
            DbUtils::changeStatus($name,RepositorioStatus::Baixando);
            \Cz\Git\GitRepository::cloneRepository(GITHUB_ROOT.$name,__DIR__.DIRECTORY_SEPARATOR.$dirName);
            LogManager::debug('download finalizado');
            DbUtils::changeStatus($name,RepositorioStatus::Baixado);
            RabbitMessager::ckMessage($name);
        } catch (Exception $e) {
            try {
                FileUtils::rrmdir($dirName);
                LogManager::debug($e->getMessage());
            } catch (\Throwable $th) {
                LogManager::debug('Falha ao limpar');
            }
        }
    }

    public function run(){
        set_time_limit(0);
        while(true){
            $this->consumme('downloadMessage',[$this, 'download']);
            sleep(1);
        }
    }
}

(new DownloadRepositorioConsumer)->run();