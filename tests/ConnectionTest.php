<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConnectionTest extends TestCase
{
    public function testConnectToDatabase()
    {
        // Checkt of er verbinding kan worden gemaakt met een database
        $db = [
            'host' => '127.0.0.1',
            'database_name' => 'ht',
            'username' => 'root',
            'password' => 'root'
        ];

        $connection_result = Connect::connect('test', $db);
        $this->assertTrue($connection_result);

    }
    public function testResetDatabase()
    {
        // Checkt of de verbinding juist wordt gereset (teruggezet naar orginele database)
        Connect::reset();
        $current_db = Config::get('database.default');
        $this->assertEquals('mysql', $current_db);
    }
    public function testGetAdditionalDatabases() {

        $this->connectionMock = Mockery::mock('alias:App\Models\Connection');

        $this->connectionMock->shouldReceive('getAttribute')->withAnyArgs();
        $this->connectionMock->shouldReceive('setAttribute')->withAnyArgs();

        $this->connectionMock->host = '127.0.0.1';
        $this->connectionMock->username = 'root';
        $this->connectionMock->password = 'root';
        $this->connectionMock->database_name = 'ht';

        $this->app->instance(App\Models\Connection::class, $this->connectionMock);

        $databases = Connect::getOtherDatabases($this->connectionMock);

        $this->assertNotEmpty($databases);
    }
}