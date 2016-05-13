<?php
/**
 * @package Activerecord
 */

namespace Activerecord\Serializers;

/**
 * XML serializer.
 *
 * @package Activerecord
 */
class SerializeXml
        extends AbstractSerialize
{

    private $writer;

    public function __construct(Model $model, &$options)
    {
        $this->includes_with_class_name_element = true;
        parent::__construct($model, $options);
    }

    public function toString()
    {
        return $this->xml_encode();
    }

    private function xmlEncode()
    {
        $this->writer = new XmlWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->startElement(\strtolower(denamespace(($this->model))));
        $this->write($this->toArray());
        $this->writer->endElement();
        $this->writer->endDocument();
        $xml = $this->writer->outputMemory(true);

        if (@$this->options['skip_instruct'] == true)
        {
            $xml = \preg_replace('/<\?xml version.*?\?>/', '', $xml);
        }

        return $xml;
    }

    private function write($data, $tag = null)
    {
        foreach ($data as $attr => $value)
        {
            if ($tag != null)
            {
                $attr = $tag;
            }

            if (\is_array($value) || \is_object($value))
            {
                if (!\is_int(key($value)))
                {
                    $this->writer->startElement($attr);
                    $this->write($value);
                    $this->writer->endElement();
                }
                else
                {
                    $this->write($value, $attr);
                }

                continue;
            }

            $this->writer->writeElement($attr, $value);
        }
    }

}
