<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'DbUtils.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RepositorioStatus.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RabbitMessager.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'LogManager.php';

(new Start())->run();

class Start{
    public function run(){
        LogManager::debug('Iniciando processamento');
        $repositorios = DbUtils::getRepositoriosNaoFinalizados();
        LogManager::debug('Repositórios buscados.');

        foreach($repositorios as $repositorio){
            switch ($repositorio['status']) {
                case RepositorioStatus::Pendente:
                    RabbitMessager::downloadMessage($repositorio['name_with_owner']);
                    LogManager::debug($repositorio['name_with_owner'].' enviado para download');
                    break;
                case RepositorioStatus::Baixado:
                    RabbitMessager::ckMessage($repositorio['name_with_owner']);
                    LogManager::debug($repositorio['name_with_owner'].' enviado para processamento');
                    break;
            }
        }

        LogManager::debug('Todas as filas foram criadas.');
    }
}
?>