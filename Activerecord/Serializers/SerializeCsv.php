<?php
/**
 * @package Activerecord
 */

namespace Activerecord\Serializers;

/**
 * CSV serializer.
 *
 * @package Activerecord
 */
class SerializeCsv
        extends AbstractSerialize
{

    public static $delimiter = ',';
    public static $enclosure = '"';

    public function toString()
    {
        if (@$this->options['only_header'] == true)
        {
            return $this->header();
        }
        return $this->row();
    }

    private function header()
    {
        return $this->toCsv(array_keys($this->toArray()));
    }

    private function row()
    {
        return $this->toCsv($this->toArray());
    }

    private function toCsv($arr)
    {
        $outstream = \fopen('php://temp', 'w');
        \fputcsv($outstream, $arr, self::$delimiter, self::$enclosure);
        \rewind($outstream);
        $buffer = \trim(\stream_get_contents($outstream));
        \fclose($outstream);
        return $buffer;
    }

}
