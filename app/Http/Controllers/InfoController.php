<?php

namespace App\Http\Controllers;

use App\DTO\ClientInfoDTO;
use App\DTO\DatabaseInfoDTO;
use App\DTO\ServerInfoDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InfoController extends Controller
{
    public function serverInfo(): ServerInfoDTO
    {
        return new ServerInfoDTO(
            phpVersion: PHP_VERSION,
            phpSapi: PHP_SAPI,
            maxExecutionTime: (int) ini_get('max_execution_time'),
            memoryLimit: ini_get('memory_limit')
        );
    }

    public function clientInfo(Request $request): ClientInfoDTO
    {
        return new ClientInfoDTO(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );
    }

    public function databaseInfo(): DatabaseInfoDTO
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            
            $version = match($driver) {
                'mysql' => $connection->selectOne('SELECT VERSION() as version')->version,
                'pgsql' => $connection->selectOne('SELECT VERSION() as version')->version,
                'sqlite' => 'SQLite',
                default => 'Unknown',
            };
            
            return new DatabaseInfoDTO(
                driver: $driver,
                serverVersion: $version,
                databaseName: $connection->getDatabaseName()
            );
        } catch (\Exception $e) {
            return new DatabaseInfoDTO(
                driver: 'error',
                serverVersion: 'Connection failed',
                databaseName: 'Check database settings'
            );
        }
    }
}