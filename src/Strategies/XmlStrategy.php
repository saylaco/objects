<?php

namespace Sayla\Objects\Strategies;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Sabre\Xml\Writer;
use Sayla\Exception\RecordNotFound;
use Sayla\Objects\BaseStrategy;
use Sayla\Objects\DataModel;
use Sayla\Objects\ObjectCollection;
use Sayla\Util\JsonHelper;
use SimpleXMLElement;

class XmlStrategy extends BaseStrategy
{
    const RECORD_ELEMENT_PREFIX = 'record-';
    protected static $elements = [];
    private static $scope;
    private static $directory;
    /** @var string */
    private $primaryKeyName;
    private $xmlFilePath;
    private $name;

    /**
     * BlockRepository constructor.
     * @param string $collectionName
     * @param string $primaryKey
     * @param string|null $directory
     */
    public function __construct(string $collectionName, string $primaryKey, string $directory = null)
    {
        $this->name = $collectionName;
        $this->primaryKeyName = $primaryKey;
        $this->xmlFilePath = ($directory ?? self::getDirectory())
            . '/' . self::$scope . '/'
            . md5($collectionName) . '.xml';
    }

    /**
     * @return string
     */
    public static function getDirectory(): string
    {
        return self::$directory;
    }

    /**
     * @param string $directory
     */
    public static function setDirectory(string $directory)
    {
        self::$directory = $directory;
    }

    /**
     * @param mixed $scope
     */
    public static function setScope(string $scope)
    {
        self::$scope = $scope;
    }

    public function __destruct()
    {
        if (isset(self::$elements[$this->name])) {
            $xml = self::$elements[$this->name]->asXML();
            file_put_contents($this->xmlFilePath, $xml, LOCK_EX);
        }
    }

    public function __toString(): string
    {
        return 'XmlDataStore[' . $this->name . '#' . $this->primaryKeyName . ']';
    }

    public function create(DataModel $object): iterable
    {
        $record = $object->descriptor()->remapAttributesForStore($object);
        $id = $this->getNextId();
        $record[$this->primaryKeyName] = $id;
        $this->store($id, $record);
        return $record;
    }

    public function delete(DataModel $model)
    {
        $nodeName = self::RECORD_ELEMENT_PREFIX . $model->getKey();
        unset($this->xml()->{$nodeName});
    }

    public function update(DataModel $object): ?iterable
    {
        $identifier = $object->getKey();
        $record = $this->retrieve($identifier);
        if (!isset($record)) {
            throw new RecordNotFound($identifier);
        }
        $this->writeRecordProperty($record, $object->descriptor()->remapAttributesForStore($object));
    }

    /**
     * @return int
     */
    protected function getNextId(): int
    {
        $maxKey = $this->xml()->xpath($this->getXpathString('*[not(preceding-sibling::*/@key >= @key)'
            . ' and not(following-sibling::*/@key > @key)]/@key'));
        if (empty($maxKey)) {
            return 1;
        }
        return intval($maxKey[0]['key']) + 1;
    }

    /**
     * @return \SimpleXMLElement
     */
    protected function xml(): \SimpleXMLElement
    {
        if (!isset(self::$elements[$this->name])) {
            if (!is_dir(dirname($this->xmlFilePath))) {
                mkdir(dirname($this->xmlFilePath), 0775, true);
            }
            if (!file_exists($this->xmlFilePath) || filesize($this->xmlFilePath) == 0) {
                $writer = new Writer();
                $writer->openMemory();
                $writer->startDocument();
                $writer->startElement('records');
                $writer->writeAttribute('name', $this->name);
                if (isset(self::$scope)) {
                    $writer->writeAttribute('scope', self::$scope);
                }
                $writer->endElement();
                $writer->endDocument();
                $outputMemory = $writer->outputMemory();
                self::$elements[$this->name] = simplexml_load_string($outputMemory);
            } else {
                self::$elements[$this->name] = simplexml_load_file($this->xmlFilePath);
            }
        }
        return self::$elements[$this->name];
    }

    protected function getXpathString($path = '')
    {
        if (isset(self::$scope)) {
            return '/records[@scope=\'' . self::$scope . '\']/' . $path;
        }
        return '/records/' . $path;
    }

    /**
     * @param $identifier
     * @param $record
     */
    protected function store($identifier, $record): void
    {
        $record[$this->primaryKeyName] = $identifier;
        $xml = $this->xml();
        $nodeName = self::RECORD_ELEMENT_PREFIX . $identifier;
        if ($this->exists($identifier)) {
            $this->delete($identifier);
        }
        $child = $this->writeRecordProperty($xml->addChild($nodeName), $record);
        $child['key'] = $identifier;
    }

    public function exists($identifier)
    {
        $existing = $this->xml()->xpath($this->getXpathString(self::RECORD_ELEMENT_PREFIX . $identifier));
        return count($existing) > 0;
    }

    /**
     * @param \SimpleXMLElement $record
     * @param $attributes
     * @return \SimpleXMLElement
     */
    protected function writeRecordProperty(\SimpleXMLElement $record, array $attributes): SimpleXMLElement
    {
        foreach ($attributes as $k => $value) {
            if (isset($record->$k)) {
                unset($record->$k);
            }
            if ($value instanceof ObjectCollection) {
                $child = $record->addChild($k);
                $child->addAttribute('type', 'objectCollection');
                $child->addAttribute('size', count($value));
                $child->addAttribute('item-type', $value->getItemClass());
                $child->addAttribute('object-type', get_class($value));
                foreach ($value->all() as $itemKey => $item) {
                    $itemChild = $child->addChild('item');
                    $itemChild->addAttribute('type', 'object');
                    $itemChild->addAttribute('object-type', get_class($item));
                    $itemChild->addAttribute('key', $itemKey);
                    $this->writeRecordProperty($itemChild, $item->toArray());
                }
            } else {
                $v = $this->getElementValueFromAttribute($value);
                if (is_array($v)) {
                    $child = $record->addChild($k);
                    if (is_object($value)) {
                        $child->addAttribute('type', 'object');
                        $child->addAttribute('object-type', get_class($value));
                    } else {
                        $child->addAttribute('type', 'array');
                    }
                    $child->addAttribute('size', count($v));
                    $this->writeRecordProperty($child, $v);
                } else {
                    $child = $record->addChild($k, $v);
                    $child->addAttribute('type', gettype($value));
                }
            }
        }
        return $record;
    }

    protected function getElementValueFromAttribute($value)
    {
        if (is_bool($value)) {
            return intval($value);
        }
        if (is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }
        if ($value instanceof Jsonable) {
            return JsonHelper::decode($value->toJson(), true);
        }
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }
        return JsonHelper::decode(json_encode($value), true);
    }

    /**
     * @param $identifier
     * @return mixed
     */
    protected function retrieve($identifier): ?\SimpleXMLElement
    {
        $results = $this->xml()->xpath($this->getXpathString(self::RECORD_ELEMENT_PREFIX . $identifier));
        return head($results);
    }

    protected function getAttributeValueFromElement($v)
    {
        if (is_array($v)) {
            $properties = array_pull($v, '@attributes');
            if (isset($properties['type'])) {
                switch ($properties['type']) {
                    case 'NULL':
                        return null;
                    case 'integer':
                        return intval($v);
                    case 'float':
                        return floatval($v);
                    case 'boolean':
                        return boolval($v);
                }
            }
            $newValue = [];
            foreach ($v as $_k => $_v) {
                $newValue[$_k] = $this->getAttributeValueFromElement($_v);
            }
            return $newValue;
        }
        return $v;
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string $name
     * @param $valueMap
     * @return \SimpleXMLElement
     */
    protected function writeAttributeProperty(\SimpleXMLElement $element, string $name, $valueMap): SimpleXMLElement
    {
        if (is_array($valueMap)) {
            foreach ($valueMap as $k => $v) {
                $this->writeAttributeProperty($element, $k, $this->getElementValueFromAttribute($v));
            }
        } else {
            $element->addChild($name, $valueMap);
        }
        return $element;
    }


}