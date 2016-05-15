<?php
class Host extends Activerecord\Model
{
	static $has_many = array(
		'events',
		array('venues', 'through' => 'events')
	);
}
?>
