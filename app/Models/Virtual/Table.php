<?php
namespace App\Models\Virtual;

use App\Difference;
use App\Models\Deploy;
use App\Services\SqlGenerationService;
use SqlGeneration;

use App\Models\Change;

use Sql;

/**
 * Table representation
 * @package App\Models\Virtual
 */
class Table
{
    /**
     * @var string $name Name of the table
     */
    private $name;
    /**
     * @var string $row_format Row Format of the table
     */
    private $row_format;

    /**
     * @var string $engine Engine of the table
     */
    private $engine;
    /**
     * @var string $collation Collation of the table
     */
    private $collation;
    /**
     * @var integer $auto_increment value of the table. Not used for diffing.
     */
    private $auto_increment;
    /**
     * @var integer $avg_row_length Average row length of the table.
     */
    private $avg_row_length;

    /**
     * @var array $indices The indices belonging to this table (all kinds)
     */
    private $indices = [];
    /**
     * @var array $columns The columns belonging to this table
     */
    private $columns = [];
    /**
     * @var array $constraints The constraints (foreign keys) belonging to this table
     */
    private $constraints = [];

    /**
     * Get the constraints for this table
     * @return array
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * Set the constraints for this table
     * @param array $constraints
     */
    public function setConstraints($constraints)
    {
        $this->constraints = $constraints;
    }

    /**
     * Get the attributes of this table
     * @return array
     */
    public function getAttributes() {
        // Get attributes using get_object_vars
        $attributes = (array) get_object_vars($this);

        // Return attributes
        return $attributes;
    }

    /**
     * Check if the table has a column with the provided information from the source table. Used for renaming instead adding/deleting column
     * @param Column $column
     * @param Table $table
     * @return bool|string returns false when no column has been found, returns the name of the column when it is the same.
     */
    public function hasSameColumn(Column $column, Table $table) {
        // Get attributes of the source table
        $attributes_from_source = $column->getAttributes();

        // Remove the name and table attributes. They should not be used for this check.
        unset($attributes_from_source['name']);
        unset($attributes_from_source['table']);

        // Loop the columns of the destination table
        foreach($this->getColumns() as $c) {

            // If the source column has a column with the same name, then stop the check of this column
            if(isset($table->getColumns()[$c->getName()]))
                continue;

            // Get the attributes of the column
            $a = $c->getAttributes();

            // Remove the name and table attributes. They should not be used for this check.
            unset($a['name']);
            unset($a['table']);

            // By default the attribute is the same
            $same = true;

            // Loop through the source attributes. If one of the attributes is NOT the same as the destination attribute, the column is not the same.
            foreach($attributes_from_source as $attribute_name => $at) {
                if($at != $a[$attribute_name]) {
                    $same = false;
                }
            }
            // If the column is the same, return the name of the column
            if($same)
                return $c->getName();
        }
        return false;
    }

    /**
     * Get the name of the table
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the table
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the engine of the table
     * @return mixed
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Set the engine of the table
     * @param mixed $engine
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * Get the row_format of this table
     * @return mixed
     */
    public function getRowFormat()
    {
        return $this->row_format;
    }

    /**
     * Set the row_format of this table
     * @param mixed $row_format
     */
    public function setRowFormat($row_format)
    {
        $this->row_format = $row_format;
    }

    /**
     * Get the collation of this table
     * @return mixed
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * Set the collation of this table
     * @param mixed $collation
     */
    public function setCollation($collation)
    {
        $this->collation = $collation;
    }

    /**
     * Get the auto_increment value of this table
     * @return mixed
     */
    public function getAutoIncrement()
    {
        return $this->auto_increment;
    }

    /**
     * Set the auto_increment value of this table
     * @param mixed $auto_increment
     */
    public function setAutoIncrement($auto_increment)
    {
        $this->auto_increment = $auto_increment;
    }

    /**
     * Get the avg row length value of this table
     * @return mixed
     */
    public function getAvgRowLength()
    {
        return $this->avg_row_length;
    }

    /**
     * Set the avg row length value of this table
     * @param mixed $avg_row_length
     */
    public function setAvgRowLength($avg_row_length)
    {
        $this->avg_row_length = $avg_row_length;
    }

    /**
     * Construct the table and set the attributes
     * @param $table_name the name of the table
     * @param $status the parameters
     */
    public function __construct($table_name, $status) {
        $this->name = $table_name;
        $this->engine = $status->Engine;
        $this->row_format = $status->Row_format;
        $this->collation = $status->Collation;
        $this->auto_increment = $status->Auto_increment;
        $this->avg_row_length = $status->Avg_row_length;
        $this->comment = $status->Comment;
    }

    /**
     * Add a index to this table using the DBAL Index
     * @param \Doctrine\DBAL\Schema\Index $index
     */
    public function addIndex(\Doctrine\DBAL\Schema\Index $index) {
        // Get the name of this index, this is used as the key
        $name = $index->getName();

        // Add a DifferDB index object to the table
        $this->indices[$name] = new Index($name, $index->isUnique(), $index->isPrimary(), $index->getColumns());
    }

    /**
     * Add a constraint to this table using the DBAL ForeignKeyConstraint object
     * @param \Doctrine\DBAL\Schema\ForeignKeyConstraint $constraint
     */
    public function addConstraint(\Doctrine\DBAL\Schema\ForeignKeyConstraint $constraint) {
        // Get the constraint DBAL info
        $name = $constraint->getName();
        $local_columns = $constraint->getLocalColumns();
        $foreign_table_name = $constraint->getForeignTableName();
        $foreign_columns = $constraint->getForeignColumns();
        $on_delete = $constraint->getOption('onDelete');
        $on_update = $constraint->getOption('onUpdate');

        // Add a DifferDB constraint object to the table
        $this->constraints[$name] = new Constraint($name, $local_columns, $foreign_table_name, $foreign_columns, $on_delete, $on_update);
    }

    /**
     * Add a column to this table
     * @param $name
     * @param $type
     * @param $notnull
     * @param $default
     * @param $autoincrement
     * @param $comment
     */
    public function addColumn($name, $type, $notnull, $default, $autoincrement, $comment) {
        $this->columns[$name] = new Column($name, $type, $notnull, $default, $autoincrement, $comment, $this);
    }

    /**
     * Get the columns of this table
     * @return array
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * Get the indices of this table
     * @return array
     */
    public function getIndices() {
        return $this->indices;
    }

    /**
     * Diff the table!
     * @param Table $destination_table The destination table
     * @param Change $database_change The parent change
     * @throws \Exception
     */
    public function diff(Table $destination_table, Change $database_change) {
        // Create an alias for the source table (so its clear when we use the source)
        $source_table = &$this;

        // Add a child change and return the parent change
        $parent_change = $database_change->addChange($destination_table->name, 'table_altered', 'table', '');

        // Diff the options of this table

        $option_changes = [];

        $options = ['collation', 'comment', 'row_format', 'engine'];

        foreach($options as $option_name) {
            if($destination_table->{$option_name} != $this->{$option_name}) {
                $parent_change->addChange($option_name, 'option_altered', 'option', Sql::alterOption($destination_table, $option_name, $this->{$option_name}));
            }
        }

        // IF: constraint exists in source, but not in destination
        // THEN: generate add constraint statement

        foreach($source_table->constraints as $constraint_name => $constraint) {
            if(!isset($destination_table->constraints[$constraint_name])) {
                $parent_change->addChange($constraint_name, 'constraint_added', 'constraint', Sql::addConstraint($destination_table->getName(), $constraint));
            }
        }

        // IF: constraint exists in destination, but not in source
        // THEN: generate remove constraint statement

        foreach($destination_table->constraints as $constraint_name => $constraint) {
            if(!isset($source_table->constraints[$constraint_name])) {
                $parent_change->addChange($constraint_name, 'constraint_removed', 'constraint', Sql::dropConstraint($destination_table, $constraint));
            }
        }

        // IF: constraint exists in both source and destination
        // THEN: diff the constraint using the diff() function of the constraint

        foreach($source_table->constraints as $constraint_name => $constraint) {
            if(isset($destination_table->constraints[$constraint_name])) {
                // Go deeper and diff the constraint
                $constraint->diff($destination_table->constraints[$constraint_name], $parent_change);
            }
        }


        // IF: index exists in source, but not in destination
        // THEN: generate add index statement

        foreach($source_table->indices as $index_name => $index) {
            if(!isset($destination_table->indices[$index_name])) {
                $add_info = $index->getAttributes();
                $parent_change->addChange($index_name, 'index_added', 'index', Sql::addIndex($destination_table->getName(), $index), null, $add_info);
            }
        }

        // IF: index exists in destination, but not in source
        // THEN: generate remove index statement

        foreach($destination_table->indices as $index_name => $index) {
            if(!isset($source_table->indices[$index_name])) {
                $parent_change->addChange($index_name, 'index_removed', 'index', Sql::dropIndex($destination_table, $index));
            }
        }

        // IF: index exists in both source and destination
        // THEN: diff the index using the diff() function of the index

        foreach($source_table->getIndices() as $index_name => $index) {
            if(isset($destination_table->getIndices()[$index_name])) {
                // Go deeper and diff the index
                $index->diff($destination_table->getIndices()[$index_name], $parent_change);
            }
        }

        $renamed_columns = [];

        // IF: column exists in source, but not in destination
        // THEN: generate add column statement

        foreach($source_table->columns as $column_name => $column) {
            if(!isset($destination_table->columns[$column_name])) {
                // Check if the destination table has a column EXACTY LIKE this source column.
                $same_column_name = $destination_table->hasSameColumn($column, $source_table);

                // When a same column is found, and not yet processed
                if($same_column_name && !in_array($same_column_name, $renamed_columns)) {
                    // Add the name of the destination column to this array, so we can skip it in the next loop (otherwise a column remove statement is created)
                    $renamed_columns[] = $same_column_name;
                    $parent_change->addChange($column_name, 'column_renamed', 'column', Sql::renameColumn($destination_table, $column, $same_column_name));
                } else {
                    $parent_change->addChange($column_name, 'column_added', 'column', Sql::addColumn($destination_table, $column));
                }
            }
        }

        // IF: column exists in destination, but not in source
        // THEN: generate drop column statement

        foreach($destination_table->columns as $column_name => $column) {
            if(!isset($source_table->columns[$column_name])) {
                if(!in_array($column_name, $renamed_columns)) {
                    $parent_change->addChange($column_name, 'column_removed', 'column', Sql::dropColumn($destination_table, $column));
                }
            }
        }

        // IF: column exists in both source and destination
        // THEN: diff the column using the diff() function of the column

        foreach($source_table->columns as $column_name => $column) {
            if(isset($destination_table->columns[$column_name])) {
                // Go deeper and diff the column
                $column->diff($destination_table->columns[$column_name], $parent_change);
            }
        }

        // If there are no changes found, remove the parent change
        if(!$parent_change->children()->count()) {
            $parent_change->delete();
        }
    }
}