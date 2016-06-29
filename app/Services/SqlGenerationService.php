<?php
namespace App\Services;

use App\Models\Virtual\Column;
use App\Models\Virtual\Constraint;
use App\Models\Virtual\Index;
use App\Models\Virtual\Table;
use App\Services\BaseService;

use StringHelper;

/**
 * Class SqlGenerationService: creates SQL statements
 * @package App\Services
 */
class SqlGenerationService extends BaseService
{


    /**
     *
     */
    private function removeLastChar($sql) {
        return substr($sql, 0, -1);
    }

    /**
     * The table close SQL
     * @param Table $table
     * @return string
     */
    private function tableClose(Table $table) {
        $charset = explode('_', $table->getCollation())[0];
        return sprintf(config('sql.table.close'), $charset, $table->getCollation(), $table->getRowFormat(), $table->getEngine()) . "\n";
    }

    /**
     * The CREATE TABLE sql
     * @param Table $table
     * @return string
     */
    public function createTable(Table $table) {
        $sql = "";

        $sql .= sprintf(config('sql.table.create'), $table->getName());

        foreach($table->getColumns() as $column_name => $column) {
            $sql .= "\n";
            $sql .= $this->column($column, [], false, $table->getIndices());
            $sql .= ",";
        }

        foreach($table->getIndices() as $name => $index) {
            $sql .= "\n";
            $sql .= $this->addIndexImplicit($index);
            $sql .= ",";
        }

        $sql = $this->removeLastChar($sql);

        $sql .= "\n";
        $sql .= ")";

        $sql .= $this->tableClose(($table));

        $sql .= "\n";
        $sql .= "\n";
        $sql .= "\n";

        return $sql;
    }

    /**
     * The DROP TABLE sql
     * @param Table $table
     * @return string
     */
    public function dropTable(Table $table) {
        $sql = '';

        $sql .= sprintf(config('sql.table.drop'), $table->getName());
        $sql .= ";";
        $sql .= "\n";
        return $sql;
    }

    /**
     * The SQL to add a column
     * @param Table $table
     * @param Column $column
     * @return string
     */
    public function addColumn(Table $table, Column $column) {
        $suffix = '';
        if($column->getAutoIncrement()) {
            $suffix = 'PRIMARY KEY';
        }
        $sql = sprintf(config('sql.column.add'), $table->getName(), $this->column($column), $suffix);
        $sql .= "\n";
        return $sql;
    }

    /**
     * Drop a column of the table SQL
     * @param Table $table
     * @param Column $column
     * @return string
     */
    public function dropColumn(Table $table, Column $column) {
        $sql = sprintf(config('sql.column.drop'), $table->getName(), $column->getName());
        $sql .= "\n";
        return $sql;
    }

    /**
     * Drop a index of the table SQL
     * @param $table
     * @param Index $index
     * @return string
     */
    public function dropIndex($table, Index $index) {
        if(!is_string($table)) {
            $table_name = $table->getName();
        } else {
            $table_name = $table;
        }
        $sql = sprintf(config('sql.index.drop'), $index->getName(), $table_name);
        $sql .= "\n";
        return $sql;
    }

    /**
     * Drop a constraint from the table SQL generation
     * @param $table
     * @param Constraint $constraint
     * @return string
     */
    public function dropConstraint($table, Constraint $constraint) {
        if(!is_string($table)) {
            $table_name = $table->getName();
        } else {
            $table_name = $table;
        }
        $sql = sprintf(config('sql.constraint.drop'), $table_name, $constraint->getName());
        $sql .= "\n";
        return $sql;
    }

    /**
     * The SQL of a column
     * @param Column $column
     * @param array $attributes
     * @param bool|false $table_name
     * @return string
     */
    public function column(Column $column, array $attributes = [], $table_name = false) {
        // $column is destination column.

        if(empty($attributes)) {
            $attributes = $column->getAttributes();
        }

        $defs = [];
        if(isset($attributes['new_name'])) {
            $defs['name'] = $attributes['name'];
            $defs['original_name'] = $attributes['new_name'];
        } else {
            $defs['name'] = $attributes['name'];
            $defs['original_name'] = $attributes['name'];
        }

        $defs['suffix'] = '';
        if(!$column->getAutoIncrement() && $attributes['auto_increment'] && !$column->isPrimaryKey()) {
            $defs['suffix'] = 'PRIMARY KEY';
        }

        $defs['table_name'] = $table_name;
        $defs['type'] = $attributes['type'];
        $defs['null'] = $attributes['notnull'] ? 'NOT NULL' : 'NULL';
        $defs['auto_increment'] = $attributes['auto_increment'] ? 'AUTO_INCREMENT' : '';
        $defs['comment'] = ($attributes['comment'] != null) ? sprintf("COMMENT '%s'", $attributes['comment']) : '';

        if($attributes['default'] == 'CURRENT_TIMESTAMP') {
            $defs['default'] = ' DEFAULT CURRENT_TIMESTAMP';
        } else if($attributes['default'] !== "" && $attributes['default'] != null) {
            $defs['default'] = sprintf("DEFAULT '%s'", $attributes['default']);
        } else if($attributes['default'] === '') {
            $defs['default'] = '';
        } else if($attributes['default'] == null && !$column->getNotnull()) {
            $defs['default'] = ' DEFAULT NULL';
        } else {
            $defs['default'] = '';
        }

        $sql = StringHelper::named(config('sql.table.definition'), $defs);

        if($table_name) {
            $prepend = StringHelper::named(config('sql.table.alter'), $defs);
            $sql = $prepend . ' ' . $sql;
        }

        return $sql;
    }

    /**
     * Alter column SQL
     * @param Column $column
     * @param $type_of_change
     * @param $new_value
     * @param $old_value
     * @param $attributes
     * @param $table_name
     * @return string
     */
    public function alterColumn(Column $column, $attributes, $table_name) {
        $attributes = (array) $attributes;

        $column_def = $this->column($column, (array) $attributes, $table_name)  . "; \n";

        return $column_def;
    }

    /**
     * Rename column SQL
     * @param Table $destination_table
     * @param Column $column
     * @param $new_name
     * @return string
     */
    public function renameColumn(Table $destination_table, Column $column, $new_name) {
        $attributes = $column->getAttributes();
        $attributes['new_name'] = $new_name;

        $column_def = $this->column($column, (array) $attributes, $destination_table->getName())  . "; \n";

        return $column_def;
    }

    /**
     * Rename table SQL
     * @param Table $destination_table
     * @param $new_name
     * @return string
     */
    public function renameTable(Table $destination_table, $new_name) {
        $old_name = $destination_table->getName();

        $column_def = sprintf(config('sql.table.rename'), $new_name, $old_name)  . "; \n";

        return $column_def;
    }

    /**
     * Alter index SQL
     * @param Column $column
     * @param $type_of_change
     * @param $new_value
     * @param $old_value
     * @param $attributes
     * @param $table_name
     * @return string
     */
    public function alterIndex(Index $source_index, Index $destination_index,  $table_name) {

        $columns = "(`".implode("`,`", $source_index->getColumns())."`)";

        if($source_index->getPrimary()) {
            $sql = sprintf(config('sql.index.alter_primary_key'), $table_name, $columns);
        } elseif($source_index->getUnique()) {
            $sql = sprintf(config('sql.index.alter_unique_key'), $table_name, $destination_index->getName(), $source_index->getName(), $columns);
        } else {
            $sql = sprintf(config('sql.index.alter_index'), $table_name, $destination_index->getName(), $source_index->getName(), $columns);
        }
        $sql .= "\n";

        return $sql;
    }

    /**
     * Alter constraint SQL
     * @param Constraint $source_constraint
     * @param Constraint $destination_constraint
     * @param $table_name
     * @return string
     */
    public function alterConstraint(Constraint $source_constraint, Constraint $destination_constraint,  $table_name) {
        // First drop the constraint
        $sql = $this->dropConstraint($table_name, $destination_constraint);
        // Then create a new constraint (there's no other way in MySQL)
        $sql .= $this->addConstraint($table_name, $source_constraint);
        return $sql;
    }

    /**
     * Alter a table option
     * @param Table $table
     * @param $option
     * @param $new_value
     * @return string with alter sql
     */
    public function alterOption(Table $table, $option, $new_value) {
        $sql = "";
        switch($option) {
            case 'collation':
                $collation = $new_value;
                $character_set = explode('_', $new_value)[0];
                $sql = sprintf(config('sql.table.option.alter_collation'), $table->getName(), $character_set, $collation);
                break;
            case 'row_format':
                $sql = sprintf(config('sql.table.option.alter_row_format'), $table->getName(), $new_value);
                break;
            case 'engine':
                $sql = sprintf(config('sql.table.option.alter_engine'), $table->getName(), $new_value);
                break;
        }
        $sql .= "\n";

        return $sql;
    }

    /**
     * Create a ADD INDEX (unique, primary or regular) key SQL
     * @param Table $table
     * @param Index $index
     * @return string with index sql
     */
    public function addIndex($table, Index $index) {
        if(!is_string($table)) {
            $table_name = $table->getName();
        } else {
            $table_name = $table;
        }

        $columns = "(`".implode("`,`", $index->getColumns())."`)";

        if($index->getPrimary()) {
            $sql = sprintf(config('sql.index.add_primary_key'), $table_name, $columns);
        } elseif($index->getUnique()) {
            $sql = sprintf(config('sql.index.add_unique_key'), $table_name, $index->getName(), $columns);
        } else {
            $sql = sprintf(config('sql.index.add_index'), $table_name, $index->getName(), $columns);
        }
        $sql .= "\n";

        return $sql;
    }

    /**
     * Add constraint to table SQL
     * @param $table
     * @param Constraint $constraint
     * @return string
     */
    public function addConstraint($table, Constraint $constraint) {
        if(!is_string($table)) {
            $table_name = $table->getName();
        } else {
            $table_name = $table;
        }

        $local_columns = "(`".implode("`,`", $constraint->getLocalColumns())."`)";
        $foreign_columns = "(`".implode("`,`", $constraint->getForeignColumns())."`)";

        $sql = sprintf(config('sql.constraint.add'),
            $table_name, $constraint->getName(), $local_columns, $constraint->getForeignTableName(), $foreign_columns, $constraint->getOnDelete(), $constraint->getOnUpdate());

        $sql .= "\n";

        return $sql;
    }

    /**
     * Add index IMPLICIT (part of CREATE TABLE) SQL.
     * @param Index $index
     * @return string with implicit index sql
     */
    public function addIndexImplicit(Index $index) {
        $columns = "(`".implode("`,`", $index->getColumns())."`)";

        if($index->getPrimary()) {
            $sql = sprintf(config('sql.index.implicit.primary'), $columns);
        } elseif($index->getUnique()) {
            $sql = sprintf(config('sql.index.implicit.unique'), $index->getName(), $columns);
        } else {
            $sql = sprintf(config('sql.index.implicit.key'), $index->getName(), $columns);
        }

        return $sql;
    }
}