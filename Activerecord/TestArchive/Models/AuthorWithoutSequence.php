<?php

namespace Test\Models;

class AuthorWithoutSequence
        extends \Activerecord\Model
{

    static $table = 'authors';
    static $sequence = 'invalid_seq';

}