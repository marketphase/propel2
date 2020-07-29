<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * An abstract model class to represent objects that belongs to a schema like
 * databases, tables, columns, indices, unices, foreign keys...
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
abstract class MappingModel implements MappingModelInterface
{
    /**
     * The list of attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The list of vendor's information.
     *
     * @var \Propel\Generator\Model\VendorInfo[]
     */
    protected $vendorInfos = [];

    /**
     * Loads a mapping definition from an array.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function loadMapping(array $attributes)
    {
        $this->attributes = array_change_key_case($attributes, CASE_LOWER);
        $this->setupObject();
    }

    /**
     * This method must be implemented by children classes to hydrate and
     * configure the current object with the loaded mapping definition stored in
     * the protected $attributes array.
     *
     * @return void
     */
    abstract protected function setupObject();

    /**
     * Returns all definition attributes.
     *
     * All attribute names (keys) are lowercase.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns a particular attribute by a case-insensitive name.
     *
     * If the attribute is not set, then the second default value is
     * returned instead.
     *
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        $name = strtolower($name);
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Converts a value (Boolean, string or numeric) into a Boolean value.
     *
     * This is to support the default value when used with a boolean column.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function booleanValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool)$value;
        }

        return in_array(strtolower($value), [ 'true', 't', 'y', 'yes' ], true);
    }

    /**
     * @param string $stringValue
     *
     * @return string|null
     */
    protected function getDefaultValueForArray($stringValue)
    {
        $stringValue = trim($stringValue);

        if (empty($stringValue)) {
            return null;
        }

        $values = [];
        foreach (explode(',', $stringValue) as $v) {
            $values[] = trim($v);
        }

        $value = implode(' | ', $values);
        if (empty($value) || $value === ' | ') {
            return null;
        }

        return sprintf('||%s||', $value);
    }

    /**
     * Converts the default string for set columns to an array.
     *
     * @param string $stringValue
     *
     * @return array|null
     */
    protected function getDefaultValueForSet($stringValue)
    {
        $stringValue = trim($stringValue);

        if (empty($stringValue)) {
            return null;
        }

        $values = [];
        foreach (explode(',', $stringValue) as $v) {
            $values[] = trim($v);
        }
        if (count($values) === 0) {
            return null;
        }

        return $values;
    }

    /**
     * Adds a new VendorInfo instance to this current model object.
     *
     * @param \Propel\Generator\Model\VendorInfo|array $vendor
     *
     * @return \Propel\Generator\Model\VendorInfo
     */
    public function addVendorInfo($vendor)
    {
        if ($vendor instanceof VendorInfo) {
            $this->vendorInfos[$vendor->getType()] = $vendor;

            return $vendor;
        }

        $vi = new VendorInfo();
        $vi->loadMapping($vendor);

        return $this->addVendorInfo($vi);
    }

    /**
     * Returns a VendorInfo object by its type.
     *
     * @param string $type
     *
     * @return \Propel\Generator\Model\VendorInfo
     */
    public function getVendorInfoForType($type)
    {
        if (isset($this->vendorInfos[$type])) {
            return $this->vendorInfos[$type];
        }

        return new VendorInfo($type);
    }

    /**
     * Returns the list of all vendor information.
     *
     * @return \Propel\Generator\Model\VendorInfo[]
     */
    public function getVendorInformation()
    {
        return $this->vendorInfos;
    }
}
