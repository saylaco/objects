<?php

namespace Sayla\Objects;

use Illuminate\Contracts\Events\Dispatcher;

class SimpleEventDispatcher implements Dispatcher
{
    const PUSH_PREFIX = '__pushed:';
    protected $listeners = [];

    /**
     * Dispatch an event and call the listeners.
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @param  bool $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        $eventName = is_object($event) ? get_class($event) : $event;
        $results = [];
        if ($this->hasListeners($eventName)) {
            foreach ($this->listeners[$eventName] as $listener) {
                $result = $listener(...$payload);
                if ($result !== null) {
                    if ($halt) {
                        return $result;
                    } else {
                        $results[] = $result;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Flush a set of pushed events.
     *
     * @param  string $event
     * @return void
     */
    public function flush($event)
    {
        if (!$this->hasListeners(self::PUSH_PREFIX . $event)) {
            return;
        }
        foreach ($this->listeners[self::PUSH_PREFIX . $event] as $i => $listener) {
            $listener();
            unset($this->listeners[self::PUSH_PREFIX . $event][$i]);
        }
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string $event
     * @return void
     */
    public function forget($event)
    {
        unset($this->listeners[$event]);
    }

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed()
    {
        collect($this->listeners)->keys()->each(function ($eventName) {
            if (starts_with($eventName, self::PUSH_PREFIX)) {
                unset($this->listeners[$eventName]);
            }
        });
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]) && filled($this->listeners[$eventName]);
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array $events
     * @param  mixed $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        foreach ((array)$events as $eventName)
            $this->listeners[$eventName][] = $listener;
    }

    /**
     * Register an event and payload to be fired later.
     *
     * @param  string $event
     * @param  array $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        $this->listeners[self::PUSH_PREFIX . $event][] = function () use ($event, $payload) {
            $this->dispatch($event, $payload);
        };
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        throw new \BadMethodCallException('Unsupported');
    }

    /**
     * Dispatch an event until the first non-null response is returned.
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @return array|null
     */
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }
}