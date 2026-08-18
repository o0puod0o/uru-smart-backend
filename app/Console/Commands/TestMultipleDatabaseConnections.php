<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestMultipleDatabaseConnections extends Command
{
    protected $signature = 'db:test-multiple-connections';
    protected $description = 'Verify the primary and second MySQL database connections.';

    public function handle(): int
    {
        $primary = DB::connection('mysql')->selectOne('select database() as name');
        $second = DB::connection('mysql_second')->selectOne('select database() as name');

        DB::connection('mysql_second')->statement(
            'create table if not exists connection_test (id int auto_increment primary key, label varchar(100) not null, created_at timestamp default current_timestamp)'
        );

        DB::connection('mysql_second')
            ->table('connection_test')
            ->insert(['label' => 'mysql_second']);

        $count = DB::connection('mysql_second')->table('connection_test')->count();

        $this->info('Primary mysql database: '.$primary->name);
        $this->info('Second mysql database: '.$second->name);
        $this->info('Rows in mysql_second.connection_test: '.$count);

        return self::SUCCESS;
    }
}
