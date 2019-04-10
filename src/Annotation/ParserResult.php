<?php

namespace Sayla\Objects\Annotation;

use ArrayIterator;
use Illuminate\Support\Collection;
use Sayla\Helper\Data\BaseList;

class ParserResult extends BaseList
{
    public function collect()
    {
        return new Collection($this->items);
    }

    /**
     * @param string $name
     * @return \Sayla\Objects\Annotation\ParserResult
     */
    public function get(string $name)
    {
        $result = new self();
        $result->items = $this->collect()->where('name', $name)->all();
        return $result;
    }

    /**
     * @return AnnoEntry[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function push(AnnoEntry $value)
    {
        $this->items[] = $value;
    }
}