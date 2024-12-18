<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: profile.proto

namespace Pakutoma\Phreakscope\protobuf\profiles;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * ValueType describes the semantics and measurement units of a value.
 *
 * Generated from protobuf message <code>perftools.profiles.ValueType</code>
 */
class ValueType extends \Google\Protobuf\Internal\Message
{
    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int64 type = 1;</code>
     */
    protected $type = 0;
    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int64 unit = 2;</code>
     */
    protected $unit = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $type
     *           Index into string table.
     *     @type int|string $unit
     *           Index into string table.
     * }
     */
    public function __construct($data = NULL) {
        \Pakutoma\Phreakscope\protobuf\metadata\Profile::initOnce();
        parent::__construct($data);
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int64 type = 1;</code>
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int64 type = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkInt64($var);
        $this->type = $var;

        return $this;
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int64 unit = 2;</code>
     * @return int|string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int64 unit = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setUnit($var)
    {
        GPBUtil::checkInt64($var);
        $this->unit = $var;

        return $this;
    }

}

