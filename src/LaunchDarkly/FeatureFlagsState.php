<?php
namespace LaunchDarkly;

/**
 * A snapshot of the state of all feature flags with regard to a specific user, generated by
 * calling LDClient.allFlagsState(). Serializing this object to JSON using json_encode(), or
 * the jsonSerialize() method, will produce the appropriate data structure for bootstrapping
 * the LaunchDarkly JavaScript client.
 */
class FeatureFlagsState implements \JsonSerializable
{
    /** @var bool */
    protected $_valid = false;

    /** @var array */
    protected $_flagValues;

    /** @var array */
    protected $_flagMetadata;

    public function __construct($valid, $flagValues = array(), $flagMetadata = array())
    {
        $this->_valid = $valid;
        $this->_flagValues = array();
        $this->_flagMetadata = array();
    }

    /**
     * Used internally to build the state map.
     */
    public function addFlag($flag, $evalResult)
    {
        $this->_flagValues[$flag->getKey()] = $evalResult->getValue();
        $meta = array();
        if (!is_null($evalResult->getVariation())) {
            $meta['variation'] = $evalResult->getVariation();
        }
        $meta['version'] = $flag->getVersion();
        $meta['trackEvents'] = $flag->isTrackEvents();
        if ($flag->getDebugEventsUntilDate()) {
            $meta['debugEventsUntilDate'] = $flag->getDebugEventsUntilDate();
        }
        $this->_flagMetadata[$flag->getKey()] = $meta;
    }

    /**
     * Returns true if this object contains a valid snapshot of feature flag state, or false if the
     * state could not be computed (for instance, because the client was offline or there was no user).
     * @return bool true if the state is valid
     */
    public function isValid()
    {
        return $this->_valid;
    }

    /**
     * Returns the value of an individual feature flag at the time the state was recorded.
     * @param $key string
     * @return mixed the flag's value; null if the flag returned the default value, or if there was no such flag
     */
    public function getFlagValue($key)
    {
        return isset($this->_flagValues[$key]) ? $this->_flagValues[$key] : null;
    }

    /**
     * Returns an associative array of flag keys to flag values. If a flag would have evaluated to the default
     * value, its value will be null.
     * <p>
     * Do not use this method if you are passing data to the front end to "bootstrap" the JavaScript client.
     * Instead, use jsonSerialize().
     * @return array an associative array of flag keys to JSON values
     */
    public function toValuesMap()
    {
        return $this->_flagValues;
    }

    /**
     * Returns a JSON representation of the entire state map (as an associative array), in the format used
     * by the LaunchDarkly JavaScript SDK. Use this method if you are passing data to the front end in
     * order to "bootstrap" the JavaScript client.
     * <p>
     * Note that calling json_encode() on a FeatureFlagsState object will automatically use the
     * jsonSerialize() method.
     * @return array an associative array suitable for passing as a JSON object
     */
    public function jsonSerialize()
    {
        $ret = array_replace([], $this->_flagValues);
        $ret['$flagsState'] = $this->_flagMetadata;
        $ret['$valid'] = $this->_valid;
        return $ret;
    }
}
