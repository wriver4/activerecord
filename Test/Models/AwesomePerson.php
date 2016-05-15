<?php
class AwesomePerson extends Activerecord\Model
{
	static $belongs_to = array('author');
}
?>
