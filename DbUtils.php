<?php

const DB_HOST = "";
const DB_NAME = "";
const DB_USER = "";
const DB_PASSWORD = "";
require_once __DIR__.DIRECTORY_SEPARATOR.'RepositorioStatus.php';

class DbUtils
{
    private static function getConnection()
    {
        $pdoConfig  = "sqlsrv:Server=" . DB_HOST . ";Database=" . DB_NAME . ";";

        try {
            if (!isset($connection)) {
                $connection =  new PDO($pdoConfig, DB_USER, DB_PASSWORD);
                $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            return $connection;
        } catch (PDOException $e) {
            $mensagem = "Drivers disponiveis: " . implode(",", PDO::getAvailableDrivers());
            $mensagem .= "\nErro: " . $e->getMessage();
            throw new Exception($mensagem);
        }
    }

    public static function getAllRepositorios(){
        return self::getConnection()->query("SELECT * FROM repositorios")->fetchAll();
    }

    public static function getRepositoriosNaoFinalizados(){
        return self::getConnection()->query("SELECT * FROM repositorios where status != ".RepositorioStatus::Processado)->fetchAll();
    }

    public static function getByName($name){
        return self::getConnection()->query("SELECT * FROM repositorios where name_with_owner = '$name'")->fetch();
    }

    public static function changeStatus($name,$newStatus){
        $query = "UPDATE repositorios set status = '$newStatus' where name_with_owner = '$name'";
        self::getConnection()->prepare($query)->execute();
    }

    public static function saveResult($id, $cbo, $dit, $loc, $lcom){
        $query = "UPDATE repositorios set cbo=?,dit=?,loc=?,lcom=?,status=? where id_repositorio=?";
        self::getConnection()->prepare($query)->execute([$cbo,$dit,$loc,$lcom,RepositorioStatus::Processado,$id]);
    }
}
