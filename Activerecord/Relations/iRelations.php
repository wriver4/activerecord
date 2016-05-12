<?php

namespace Activerecord\Relations;

use Activerecord\Model;

/**
 * Interface for a table relationship.
 *
 * @package Activerecord
 */
interface iRelations
{

    public function __construct($options = []);

    public function buildAssociation(Model $model, $attributes = [],
            $guard_attributes = true);

    public function createAssociation(Model $model, $attributes = [],
            $guard_attributes = true);
}
