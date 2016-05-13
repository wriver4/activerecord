<?php

Namespace Activerecord\Relations;

use Activerecord\Model;
use Activerecord\Relations\AbstractRelations;

/**
 * Summary of file HasAndBelongsToMany.
 *
 * Description of file HasAndBelongsToMany.
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
 * @todo implement me
 * @package Activerecord
 * @see http://www.phpActiverecord.org/guides/associations
 */
class HasAndBelongsToMany
        extends AbstractRelations
{

    public function __construct($options = [])
    {
        /* options =>
         *   join_table - name of the join table if not in lexical order
         *   foreign_key -
         *   association_foreign_key - default is {assoc_class}_id
         *   uniq - if true duplicate assoc objects will be ignored
         *   validate
         */
    }

    public function load(Model $model)
    {

    }

}
