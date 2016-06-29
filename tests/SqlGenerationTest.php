<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SqlGenerationTest extends TestCase
{
    private $sqlService;

    public function __construct() {

    }
    public function testCreateTable_ShouldReturn_ValidSQL()
    {
        // Test of de CREATE TABLE queries correct zijn.
        $table = Mockery::mock('alias:App\Models\Virtual\Table');


        $table->shouldReceive('addColumn')->withAnyArgs();
        $table->shouldReceive('getName')->andReturn('hallo');
        $table->shouldReceive('getCollation')->andReturn('myisam');
        $table->shouldReceive('getRowFormat')->andReturn('fixed');
        $table->shouldReceive('getEngine')->andReturn('innodb');

        // Add a column
            $column = Mockery::mock('alias:App\Models\Virtual\Column');
            $column->shouldReceive('getAutoIncrement')->withNoArgs();
            $column->name = 'test';
            $column->type = 'INT(11)';
            $column->notnull = true;
            $column->default = 'hoi';
            $column->auto_increment = false;
            $column->comment = '';

            $column->shouldReceive('getAttributes')->andReturn((array) get_object_vars($column));

            $columns = [];
            $columns[] = $column;

            $table->shouldReceive('getColumns')->andReturn($columns);

        // Add a index
            $index = Mockery::mock('alias:App\Models\Virtual\Index');
            $index->shouldReceive('getPrimary', 'getUnique')->andReturn(false);
            $index->shouldReceive('getName')->andReturn('test_index');

            $index->name = 'test_index';
            $index->columns = [];
            $index->unique = false;
            $index->primary = false;

            $index->shouldReceive('getColumns')->withNoArgs()->andReturn([]);

            $indexes = [];
            $indexes[] = $index;

            $table->shouldReceive('getIndices')->andReturn($indexes);

        $this->app->instance(App\Models\Virtual\Column::class, $column);
        $this->app->instance(App\Models\Virtual\Table::class, $table);

        $sql_expected = "CREATE TABLE IF NOT EXISTS `hallo` (
`test` INT(11) NOT NULL   DEFAULT 'hoi' ,
KEY `test_index` (``)
) CHARACTER SET myisam COLLATE myisam ROW_FORMAT=fixed  ENGINE=innodb;



";

        $this->assertEquals($sql_expected, Sql::createTable($table));
    }
    public function testDropTable_ValidSQL()
    {
        // Test of de CREATE TABLE queries correct zijn.
        $table = Mockery::mock('alias:App\Models\Virtual\Table');
        $table->shouldReceive('getName')->andReturn('table_name');

        $sql_expected = 'DROP TABLE `table_name`;
';

        $this->assertEquals($sql_expected, Sql::dropTable($table));

    }
    public function testaddColumn_ValidSQL() {
        // Test of de ADD COLUMN queries correct zijn.

        // Create a table
        $table = Mockery::mock('alias:App\Models\Virtual\Table');
        $table->shouldReceive('getName')->andReturn('table_name');

        // Create a column
        $column = Mockery::mock('alias:App\Models\Virtual\Column');
        $column->shouldReceive('getAutoIncrement')->withNoArgs();
        $column->name = 'test';
        $column->type = 'INT(11)';
        $column->notnull = true;
        $column->default = 'hoi';
        $column->auto_increment = false;
        $column->comment = '';

        $column->shouldReceive('getAttributes')->andReturn((array) get_object_vars($column));

        $expected_sql = "ALTER TABLE `table_name` ADD `test` INT(11) NOT NULL   DEFAULT 'hoi'  ;
";
        $this->assertEquals($expected_sql, Sql::addColumn($table, $column));
    }
    public function testAlterColumn_ValidSQL() {
        // Test of de ALTER COLUMN queries correct zijn.

        // Create a column
        $column = Mockery::mock('alias:App\Models\Virtual\Column');
        $column->shouldReceive('getAutoIncrement')->withNoArgs();
        $column->name = 'test';
        $column->type = 'INT(11)';
        $column->notnull = true;
        $column->default = 'hoi';
        $column->auto_increment = false;
        $column->comment = '';

        $column->shouldReceive('getAttributes')->andReturn((array) get_object_vars($column));

        $expected_sql = "ALTER TABLE `table_name` CHANGE `test` `test` INT(11) NOT NULL   DEFAULT 'hoi' ; \n";

        $attributes = (array) get_object_vars($column);

        $this->assertEquals($expected_sql, Sql::alterColumn($column, $attributes, 'table_name'));
    }
    public function testAddIndex_ValidSQL() {
        // Test of de ADD INDEX queries correct zijn. Hieronder vallen de queries voor de primary keys, unique keys en reguliere keys.

        // Test of de CREATE TABLE queries correct zijn.
        $table = Mockery::mock('alias:App\Models\Virtual\Table');


        $table->shouldReceive('addColumn')->withAnyArgs();
        $table->shouldReceive('getName')->andReturn('hallo');
        $table->shouldReceive('getCollation')->andReturn('myisam');
        $table->shouldReceive('getRowFormat')->andReturn('fixed');
        $table->shouldReceive('getEngine')->andReturn('innodb');

        // Add a index
        $index = Mockery::mock('alias:App\Models\Virtual\Index');
        $index->shouldReceive('getPrimary', 'getUnique')->andReturn(false);
        $index->shouldReceive('getName')->andReturn('test_index');

        $index->name = 'test_index';
        $index->columns = [];
        $index->unique = false;
        $index->primary = false;

        $index->shouldReceive('getColumns')->withNoArgs()->andReturn([]);


        $expected_sql = "ALTER TABLE `hallo` ADD KEY `test_index` (``);
";

        $this->assertEquals($expected_sql, Sql::addIndex($table, $index));

    }
}