<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: profile.proto

namespace Pakutoma\Phreakscope\protobuf\profiles;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Each Sample records values encountered in some program
 * context. The program context is typically a stack trace, perhaps
 * augmented with auxiliary information like the thread-id, some
 * indicator of a higher level request being handled etc.
 *
 * Generated from protobuf message <code>perftools.profiles.Sample</code>
 */
class Sample extends \Google\Protobuf\Internal\Message
{
    /**
     * The ids recorded here correspond to a Profile.location.id.
     * The leaf is at location_id[0].
     *
     * Generated from protobuf field <code>repeated uint64 location_id = 1;</code>
     */
    private $location_id;
    /**
     * The type and unit of each value is defined by the corresponding
     * entry in Profile.sample_type. All samples must have the same
     * number of values, the same as the length of Profile.sample_type.
     * When aggregating multiple samples into a single sample, the
     * result has a list of values that is the element-wise sum of the
     * lists of the originals.
     *
     * Generated from protobuf field <code>repeated int64 value = 2;</code>
     */
    private $value;
    /**
     * label includes additional context for this sample. It can include
     * things like a thread id, allocation size, etc.
     * NOTE: While possible, having multiple values for the same label key is
     * strongly discouraged and should never be used. Most tools (e.g. pprof) do
     * not have good (or any) support for multi-value labels. And an even more
     * discouraged case is having a string label and a numeric label of the same
     * name on a sample.  Again, possible to express, but should not be used.
     *
     * Generated from protobuf field <code>repeated .perftools.profiles.Label label = 3;</code>
     */
    private $label;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<int>|array<string>|\Google\Protobuf\Internal\RepeatedField $location_id
     *           The ids recorded here correspond to a Profile.location.id.
     *           The leaf is at location_id[0].
     *     @type array<int>|array<string>|\Google\Protobuf\Internal\RepeatedField $value
     *           The type and unit of each value is defined by the corresponding
     *           entry in Profile.sample_type. All samples must have the same
     *           number of values, the same as the length of Profile.sample_type.
     *           When aggregating multiple samples into a single sample, the
     *           result has a list of values that is the element-wise sum of the
     *           lists of the originals.
     *     @type array<\Pakutoma\Phreakscope\protobuf\profiles\Label>|\Google\Protobuf\Internal\RepeatedField $label
     *           label includes additional context for this sample. It can include
     *           things like a thread id, allocation size, etc.
     *           NOTE: While possible, having multiple values for the same label key is
     *           strongly discouraged and should never be used. Most tools (e.g. pprof) do
     *           not have good (or any) support for multi-value labels. And an even more
     *           discouraged case is having a string label and a numeric label of the same
     *           name on a sample.  Again, possible to express, but should not be used.
     * }
     */
    public function __construct($data = NULL) {
        \Pakutoma\Phreakscope\protobuf\metadata\Profile::initOnce();
        parent::__construct($data);
    }

    /**
     * The ids recorded here correspond to a Profile.location.id.
     * The leaf is at location_id[0].
     *
     * Generated from protobuf field <code>repeated uint64 location_id = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getLocationId()
    {
        return $this->location_id;
    }

    /**
     * The ids recorded here correspond to a Profile.location.id.
     * The leaf is at location_id[0].
     *
     * Generated from protobuf field <code>repeated uint64 location_id = 1;</code>
     * @param array<int>|array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setLocationId($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::UINT64);
        $this->location_id = $arr;

        return $this;
    }

    /**
     * The type and unit of each value is defined by the corresponding
     * entry in Profile.sample_type. All samples must have the same
     * number of values, the same as the length of Profile.sample_type.
     * When aggregating multiple samples into a single sample, the
     * result has a list of values that is the element-wise sum of the
     * lists of the originals.
     *
     * Generated from protobuf field <code>repeated int64 value = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * The type and unit of each value is defined by the corresponding
     * entry in Profile.sample_type. All samples must have the same
     * number of values, the same as the length of Profile.sample_type.
     * When aggregating multiple samples into a single sample, the
     * result has a list of values that is the element-wise sum of the
     * lists of the originals.
     *
     * Generated from protobuf field <code>repeated int64 value = 2;</code>
     * @param array<int>|array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setValue($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::INT64);
        $this->value = $arr;

        return $this;
    }

    /**
     * label includes additional context for this sample. It can include
     * things like a thread id, allocation size, etc.
     * NOTE: While possible, having multiple values for the same label key is
     * strongly discouraged and should never be used. Most tools (e.g. pprof) do
     * not have good (or any) support for multi-value labels. And an even more
     * discouraged case is having a string label and a numeric label of the same
     * name on a sample.  Again, possible to express, but should not be used.
     *
     * Generated from protobuf field <code>repeated .perftools.profiles.Label label = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * label includes additional context for this sample. It can include
     * things like a thread id, allocation size, etc.
     * NOTE: While possible, having multiple values for the same label key is
     * strongly discouraged and should never be used. Most tools (e.g. pprof) do
     * not have good (or any) support for multi-value labels. And an even more
     * discouraged case is having a string label and a numeric label of the same
     * name on a sample.  Again, possible to express, but should not be used.
     *
     * Generated from protobuf field <code>repeated .perftools.profiles.Label label = 3;</code>
     * @param array<\Pakutoma\Phreakscope\protobuf\profiles\Label>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setLabel($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Pakutoma\Phreakscope\protobuf\profiles\Label::class);
        $this->label = $arr;

        return $this;
    }

}

