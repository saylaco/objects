<?php

namespace Sayla\Objects;


use Illuminate\Contracts\Events\Dispatcher;
use Sayla\Objects\Contract\Serializes;
use Sayla\Objects\Contract\SerializesTrait;

class ObjectDispatcher implements Serializes
{
    use SerializesTrait;
    /** @var Dispatcher */
    private $dispatcher;
    /** @var string */
    private $name;

    /**
     * EventDispatcher constructor.
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     * @param string $name
     */
    public function __construct(Dispatcher $dispatcher, string $name)
    {
        $this->dispatcher = $dispatcher;
        $this->name = $name;
    }

    /**
     * @return iterable|string[]|callable[]
     */
    public static function unserializableInstanceProperties(): iterable
    {
        return ['dispatcher'];
    }

    public function fire($event, $payload = [])
    {
        $this->dispatcher->dispatch($this->qualifyEventName($event), $payload);
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function on($event, $listener)
    {
        $this->dispatcher->listen($this->qualifyEventName($event), $listener);
        return $this;
    }

    /**
     * @param $event
     * @return string
     */
    public function qualifyEventName($event): string
    {
        if (is_object($event)) {
            $event = get_class($event);
        }
        return "{$this->name}.{$event}";
    }
}