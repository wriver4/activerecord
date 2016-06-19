<?php

namespace Activerecord\Relations;

use Activerecord\Relations\AbstractRelations;

/**
 * Summary of file HasOne.
 *
 * Description of file HasOne.
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
 * One-to-one relationship.
 *
 * <code>
 * # Table name: states
 * # Primary key: id
 * class State extends Activerecord\Model {}
 *
 * # Table name: people
 * # Foreign key: state_id
 * class Person extends Activerecord\Model {
 *   static $has_one = array(array('state'));
 * }
 * </code>
 *
 * @package Activerecord
 * @see http://www.phpActiverecord.org/guides/associations
 */
class HasOne
        extends AbstractRelations
{

    public function __construct($options = [])
    {

    }

    public function load(\Activerecord\Model $model)
    {

    }

}