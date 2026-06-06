<?php

namespace App\Http\Controllers;

use App\DTO\ClientInfoDTO;
use App\DTO\DatabaseInfoDTO;
use App\DTO\ServerInfoDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InfoController extends Controller
{
    // Метод для /info/server
    public function serverInfo(): ServerInfoDTO
    {
        // Собираем данные о PHP
        return new ServerInfoDTO(
            phpVersion: PHP_VERSION,        // глобальная константа PHP
            phpSapi: PHP_SAPI,              // тип интерфейса (cli, fpm)
            maxExecutionTime: (int) ini_get('max_execution_time'),
            memoryLimit: ini_get('memory_limit')
        );
    }
    
    // Метод для /info/client
    public function clientInfo(Request $request): ClientInfoDTO
    {
        // $request — объект HTTP-запроса (Laravel автоматически передает его)
        return new ClientInfoDTO(
            ipAddress: $request->ip(),          // получаем IP клиента
            userAgent: $request->userAgent()    // получаем User-Agent
        );
    }
    
    // Метод для /info/database
    public function databaseInfo(): DatabaseInfoDTO
    {
        // Получаем активное соединение с БД
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        // Получаем версию БД (зависит от драйвера)
        $version = match($driver) {
            'mysql' => $connection->selectOne('SELECT VERSION() as version')->version,
            'pgsql' => $connection->selectOne('SELECT VERSION() as version')->version,
            'sqlite' => 'SQLite ' . $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
            default => 'Unknown',
        };
        
        return new DatabaseInfoDTO(
            driver: $driver,
            serverVersion: $version,
            databaseName: $connection->getDatabaseName()
        );
    }
}