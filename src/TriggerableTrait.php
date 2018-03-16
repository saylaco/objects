<?php

namespace Sayla\Objects;

use Sayla\Objects\Exception\TriggerError;

trait TriggerableTrait
{
    private $triggers = [];

    /**
     * @param string $triggerKey
     * @param callable $callable
     */
    protected function addTrigger(string $triggerKey, callable $callable): void
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->triggers[$triggerKey][] = $callable;
    }

    /**
     * @param string $triggerKey
     * @param array $args
     * @return mixed
     * @throws \Sayla\Objects\Exception\TriggerError
     */
    protected function fireTriggers(string $triggerKey, array $args = [], int &$fired = null)
    {
        $fired = 0;
        $value = null;
        if (method_exists(static::class, $triggerKey . 'Trigger')) {
            array_unshift($args, $this);
            $value = forward_static_call_array(static::class . "::{$triggerKey}Trigger", $args);
        }
        if ($this->getTriggerCount($triggerKey) > 0) {
            $args['object'] = $this;
            foreach ($this->triggers[$triggerKey] as $i => $trigger) {
                try {
                    call_user_func_array($trigger, $args);
                    unset($this->triggers[$triggerKey][$i]);
                    $fired++;
                } catch (\Throwable $exception) {
                    throw (new TriggerError(static::class . '.' . self::TRIGGER_PREFIX . $triggerKey, $exception))
                        ->withExtra($exception->getMessage());
                }
            }
        }
        return $value;
    }

    /**
     * @param string $triggerKey
     * @return int
     */
    public function getTriggerCount(string $triggerKey): int
    {
        if (!isset($this->triggers[$triggerKey])) {
            return 0;
        }
        return count($this->triggers[$triggerKey]);
    }

}