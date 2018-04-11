<?php namespace Sayla\Objects\Transformers\Transformer;

use Illuminate\Support\Carbon;
use Sayla\Objects\Transformers\ValueTransformer;
use Sayla\Objects\Transformers\ValueTransformerTrait;

class DatetimeTransformer implements ValueTransformer
{
    use ValueTransformerTrait;
    protected static $defaultTimezone = null;

    /**
     * @return string
     */
    public static function getDefaultTimezone()
    {
        return self::$defaultTimezone;
    }

    /**
     * @param string $defaultTimezone
     */
    public static function setDefaultTimezone($defaultTimezone)
    {
        self::$defaultTimezone = $defaultTimezone;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function build($value)
    {
        if (is_null($value)) {
            return null;
        } elseif (is_array($value)) {
            $carbon = $this->newDateObject();
            if (isset($value['date'])) {
                $carbon->setDate($value['date']['year'], $value['date']['month'], $value['date']['day']);
            }
            if (isset($value['time'])) {
                $carbon->setTime(
                    $value['time']['hour'] ?? 0,
                    $value['time']['minute'] ?? 0,
                    $value['time']['second'] ?? 0
                );
            }
            return $carbon;
        } elseif ($value instanceof \DateTime) {
            return $this->newDateFromTimestamp($value->getTimestamp(), $value->getTimezone());
        } elseif (is_numeric($value)) {
            return $this->newDateFromTimestamp($value);
        } elseif (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return $this->newDateObject($value, 'Y-m-d');
        } elseif (($timestamp = strtotime($value)) !== false) {
            return $this->newDateFromTimestamp($timestamp);
        }
        return $this->newDateObject($value, $this->options->get('format'));
    }

    public function getScalarType(): ?string
    {
        return 'string';
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function smash($value)
    {
        $format = $this->options->get('format', 'Y-m-d H:i:s');
        if (empty($value) && !$this->options->auto) {
            return null;
        } elseif (empty($value)) {
            return Carbon::now()->format($format);
        }
        if ($value instanceof \DateTime) {
            $datetime = $value;
        } elseif (is_string($value)) {
            $datetime = Carbon::parse($value, $this->normalizeTimezone());
        } elseif (is_numeric($value)) {
            $datetime = $this->newDateFromTimestamp($value);
        } elseif (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $datetime = $this->newDateObject($value, 'Y-m-d');
        }
        if (isset($datetime) && $datetime instanceof \DateTime) {
            $smashFormat = $this->options->get('smashFormat') ?: $format;
            if ($smashFormat == 'timestamp') {
                return $datetime->getTimestamp();
            }
            return $datetime->format($smashFormat);
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @param string $format
     * @param string $timezone
     * @return \Carbon\Carbon
     */
    protected function newDateObject($value = null, $format = null, $timezone = null)
    {
        if ($value instanceof Carbon) return $value;
        if ($format) return Carbon::createFromFormat($format, $value, $this->normalizeTimezone($timezone));
        if ($value) return Carbon::parse($value, $this->normalizeTimezone($timezone));
        return new Carbon;
    }

    /**
     * @param mixed $value
     * @param string $timezone
     * @return \Carbon\Carbon
     */
    protected function newDateFromTimestamp($value, $timezone = null)
    {
        return Carbon::createFromTimestamp($value, $this->normalizeTimezone($timezone));
    }

    /**
     * @param $value
     * @param $format
     * @return Carbon
     * @deprecated
     */
    protected function newDateFromFormat($value, $format)
    {
        return $this->newDateObject($value, $format);
    }

    /**
     * @param $timezone
     * @return mixed|null
     */
    protected function normalizeTimezone($timezone)
    {
        return $timezone ?? $this->options->get('timezone') ?? static::$defaultTimezone;
    }
}