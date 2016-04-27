<?php

use \Activerecord\Model;

namespace Activerecord\Relations;

/**
 * Interface for a table relationship.
 *
 * @package Activerecord
 */
interface iRelations
{

    public function __construct($options = []);

    public function build_association(Model $model, $attributes = [],
            $guard_attributes = true);

    public function create_association(Model $model, $attributes = [],
            $guard_attributes = true);
}
