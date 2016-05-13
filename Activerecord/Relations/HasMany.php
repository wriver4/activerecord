<?php

namespace Activerecord\Relations;

use Activerecord\Exceptions\ExceptionHasManyThroughAssociation;
use Activerecord\Inflector;
use Activerecord\Model;
use Activerecord\Relations\AbstractRelations;
use Activerecord\Relations\BelongsTo;
use Activerecord\Relations\HasMany;
use Activerecord\Table;

/**
 * Summary of file HasMany.
 *
 * Description of file HasMany.
 *
 *
 * @license
 *
 * @copyright
 *
 * @version
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */

/**
 * One-to-many relationship.
 *
 * <code>
 * # Table: people
 * # Primary key: id
 * # Foreign key: school_id
 * class Person extends Activerecord\Model {}
 *
 * # Table: schools
 * # Primary key: id
 * class School extends Activerecord\Model {
 *   static $has_many = array(
 *     array('people')
 *   );
 * });
 * </code>
 *
 * Example using options:
 *
 * <code>
 * class Payment extends Activerecord\Model {
 *   static $belongs_to = array(
 *     array('person'),
 *     array('order')
 *   );
 * }
 *
 * class Order extends Activerecord\Model {
 *   static $has_many = array(
 *     array('people',
 *           'through'    => 'payments',
 *           'select'     => 'people.*, payments.amount',
 *           'conditions' => 'payments.amount < 200')
 *     );
 * }
 * </code>
 *
 * @package Activerecord
 * @see http://www.phpActiverecord.org/guides/associations
 * @see valid_association_options
 */
class HasMany
        extends AbstractRelations
{

    /**
     * Valid options to use for a {@link HasMany} relationship.
     *
     * <ul>
     * <li><b>limit/offset:</b> limit the number of records</li>
     * <li><b>primary_key:</b> name of the primary_key of the association (defaults to "id")</li>
     * <li><b>group:</b> GROUP BY clause</li>
     * <li><b>order:</b> ORDER BY clause</li>
     * <li><b>through:</b> name of a model</li>
     * </ul>
     *
     * @var array
     */
    static protected $valid_association_options = [
        'primary_key',
        'order',
        'group',
        'having',
        'limit',
        'offset',
        'through',
        'source'];
    protected $primary_key;
    private $has_one = false;
    private $through;

    /**
     * Constructs a {@link HasMany} relationship.
     *
     * @param array $options Options for the association
     * @return HasMany
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($this->options['through']))
        {
            $this->through = $this->options['through'];

            if (isset($this->options['source']))
            {
                $this->setClassName($this->options['source']);
            }
        }

        if (!$this->primary_key && isset($this->options['primary_key']))
        {
            $this->primary_key = \is_array($this->options['primary_key']) ? $this->options['primary_key']
                        : [$this->options['primary_key']];
        }

        if (!$this->class_name)
        {
            $this->setInferredClassName();
        }
    }

    protected function setKeys($model_class_name, $override = false)
    {
        //infer from class_name
        if (!$this->foreign_key || $override)
        {
            $this->foreign_key = [Inflector::instance()->keyify($model_class_name)];
        }

        if (!$this->primary_key || $override)
        {
            $this->primary_key = Table::load($model_class_name)->pk;
        }
    }

    public function load(Model $model)
    {
        $class_name = $this->class_name;
        $this->setKeys(\get_class($model));

        // since through relationships depend on other relationships we can't do
        // this initiailization in the constructor since the other relationship
        // may not have been created yet and we only want this to run once
        if (!isset($this->initialized))
        {
            if ($this->through)
            {
                // verify through is a belongs_to or has_many for access of keys
                if (!($through_relationship = $this->getTable()->getRelationship($this->through)))
                {
                    throw new ExceptionHasManyThroughAssociation("Could not find the association $this->through in model ".\get_class($model));
                }

                if (!($through_relationship instanceof HasMany) && !($through_relationship instanceof BelongsTo))
                {
                    throw new ExceptionHasManyThroughAssociation('has_many through can only use a belongs_to or has_many association');
                }

                // save old keys as we will be reseting them below for inner join convenience
                $pk = $this->primary_key;
                $fk = $this->foreign_key;

                $this->setKeys($this->getTable()->class->getName(), true);

                $class = $this->class_name;
                $relation = $class::table()->getRelationship($this->through);
                $through_table = $relation->getTable();
                $this->options['joins'] = $this->constructInnerJoinSql($through_table,
                        true);

                // reset keys
                $this->primary_key = $pk;
                $this->foreign_key = $fk;
            }

            $this->initialized = true;
        }

        if (!($conditions = $this->createConditionsFromKeys($model,
                $this->foreign_key, $this->primary_key)))
        {
            return null;
        }

        $options = $this->unsetNonFinderOptions($this->options);
        $options['conditions'] = $conditions;
        return $class_name::find($this->poly_relationship ? 'all' : 'first',
                        $options);
    }

    /**
     * Get an array containing the key and value of the foreign key for the association
     *
     * @param Model $model
     * @access private
     * @return array
     */
    private function getForeignKeyForNewAssociation(Model $model)
    {
        $this->setKeys($model);
        $primary_key = Inflector::instance()->variablize($this->foreign_key[0]);

        return [$primary_key => $model->id,];
    }

    private function injectForeignKeyForNewAssociation(Model $model,
            &$attributes)
    {
        $primary_key = $this->getForeignKeyForNewAssociation($model);

        if (!isset($attributes[key($primary_key)]))
        {
            $attributes[key($primary_key)] = current($primary_key);
        }

        return $attributes;
    }

    public function buildAssociation(Model $model, $attributes = [],
            $guard_attributes = true)
    {
        $relationship_attributes = $this->getForeignKeyForNewAssociation($model);

        if ($guard_attributes)
        {
            // First build the record with just our relationship attributes (unguarded)
            $record = parent::buildAssociation($model, $relationship_attributes,
                            false);

            // Then, set our normal attributes (using guarding)
            $record->setAttributes($attributes);
        }
        else
        {
            // Merge our attributes
            $attributes = \array_merge($relationship_attributes, $attributes);

            // First build the record with just our relationship attributes (unguarded)
            $record = parent::buildAssociation($model, $attributes,
                            $guard_attributes);
        }

        return $record;
    }

    public function createAssociation(Model $model, $attributes = [],
            $guard_attributes = true)
    {
        $relationship_attributes = $this->getForeignKeyForNewAssociation($model);

        if ($guard_attributes)
        {
            // First build the record with just our relationship attributes (unguarded)
            $record = parent::buildAssociation($model, $relationship_attributes,
                            false);

            // Then, set our normal attributes (using guarding)
            $record->setAttributes($attributes);

            // Save our model, as a "create" instantly saves after building
            $record->save();
        }
        else
        {
            // Merge our attributes
            $attributes = \array_merge($relationship_attributes, $attributes);

            // First build the record with just our relationship attributes (unguarded)
            $record = parent::createAssociation($model, $attributes,
                            $guard_attributes);
        }

        return $record;
    }

    public function loadEagerly($includes, Table $table, $models = [],
            $attributes = [])
    {
        $this->setKeys($table->class->name);
        $this->queryAndAttachRelatedModelsEagerly($table, $models, $attributes,
                $includes, $this->foreign_key, $table->pk);
    }

}
