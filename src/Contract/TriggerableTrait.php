<?php

namespace Sayla\Objects\Contract;

use Closure;
use Sayla\Objects\Exception\TriggerError;
use Throwable;

trait TriggerableTrait
{
    private static $calledTriggers = [];
    private $triggers = [];


    public static function clearTriggerCallCount(string $dataType)
    {
        self::$calledTriggers[$dataType] = [];
    }

    public static function getTriggerCallCount(string $dataType, string $triggerName): int
    {
        return self::$calledTriggers[$dataType][$triggerName] ?? 0;
    }

    public static function triggerHasBeenCalled(string $dataType, string $triggerName): bool
    {
        return self::getTriggerCallCount($dataType, $triggerName) >= 0;
    }

    /**
     * @param string $name name of trigger
     * @param mixed ...$args
     * @return int
     * @throws \Sayla\Objects\Exception\TriggerError
     */
    public function __invoke(string $name, ...$args)
    {
        $dataTypeName = $this->dataTypeName();
        if (!isset(self::$calledTriggers[$dataTypeName][$name])) {
            self::$calledTriggers[$dataTypeName][$name] = 1;
        } else {
            ++self::$calledTriggers[$dataTypeName][$name];
        }
        $fired = 0;
        $this->dispatchTrigger($name, $this);
        if ($this->getTriggerCount($name) > 0) {
            $args['object'] = $this;
            $triggers = $this->triggers[$name];
            foreach ($triggers as $i => $trigger) {
                try {
                    call_user_func_array($trigger, $args);
                    unset($this->triggers[$name][$i]);
                    $fired++;
                } catch (Throwable $exception) {
                    $fullTriggerName = $dataTypeName . '.*' . $name;
                    throw new TriggerError($fullTriggerName, $exception);
                }
            }
        }
        return $fired;
    }

    /**
     * @param string $triggerName
     * @param callable $callable
     */
    protected function addTrigger(string $triggerName, callable $callable): void
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->triggers[$triggerName][] = $callable;
    }

    /**
     * @param string $triggerName
     * @param static $instance
     */
    protected function dispatchTrigger(string $triggerName, $instance)
    {
        $triggerMethod = self::TRIGGER_PREFIX . $triggerName;
        if (method_exists($instance, $triggerMethod)) {
            $instance::$triggerMethod($instance);
        }
        $this->descriptor()->dispatcher()->fire($triggerName, [$this]);
    }

    /**
     * @param string $triggerName
     * @return int
     */
    public function getTriggerCount(string $triggerName): int
    {
        if (!isset($this->triggers[$triggerName])) {
            return 0;
        }
        return count($this->triggers[$triggerName]);
    }

}