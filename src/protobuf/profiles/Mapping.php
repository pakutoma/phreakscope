<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: profile.proto

namespace Pakutoma\Phreakscope\protobuf\profiles;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>perftools.profiles.Mapping</code>
 */
class Mapping extends \Google\Protobuf\Internal\Message
{
    /**
     * Unique nonzero id for the mapping.
     *
     * Generated from protobuf field <code>uint64 id = 1;</code>
     */
    protected $id = 0;
    /**
     * Address at which the binary (or DLL) is loaded into memory.
     *
     * Generated from protobuf field <code>uint64 memory_start = 2;</code>
     */
    protected $memory_start = 0;
    /**
     * The limit of the address range occupied by this mapping.
     *
     * Generated from protobuf field <code>uint64 memory_limit = 3;</code>
     */
    protected $memory_limit = 0;
    /**
     * Offset in the binary that corresponds to the first mapped address.
     *
     * Generated from protobuf field <code>uint64 file_offset = 4;</code>
     */
    protected $file_offset = 0;
    /**
     * The object this entry is loaded from.  This can be a filename on
     * disk for the main binary and shared libraries, or virtual
     * abstractions like "[vdso]".
     *
     * Generated from protobuf field <code>int64 filename = 5;</code>
     */
    protected $filename = 0;
    /**
     * A string that uniquely identifies a particular program version
     * with high probability. E.g., for binaries generated by GNU tools,
     * it could be the contents of the .note.gnu.build-id field.
     *
     * Generated from protobuf field <code>int64 build_id = 6;</code>
     */
    protected $build_id = 0;
    /**
     * The following fields indicate the resolution of symbolic info.
     *
     * Generated from protobuf field <code>bool has_functions = 7;</code>
     */
    protected $has_functions = false;
    /**
     * Generated from protobuf field <code>bool has_filenames = 8;</code>
     */
    protected $has_filenames = false;
    /**
     * Generated from protobuf field <code>bool has_line_numbers = 9;</code>
     */
    protected $has_line_numbers = false;
    /**
     * Generated from protobuf field <code>bool has_inline_frames = 10;</code>
     */
    protected $has_inline_frames = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $id
     *           Unique nonzero id for the mapping.
     *     @type int|string $memory_start
     *           Address at which the binary (or DLL) is loaded into memory.
     *     @type int|string $memory_limit
     *           The limit of the address range occupied by this mapping.
     *     @type int|string $file_offset
     *           Offset in the binary that corresponds to the first mapped address.
     *     @type int|string $filename
     *           The object this entry is loaded from.  This can be a filename on
     *           disk for the main binary and shared libraries, or virtual
     *           abstractions like "[vdso]".
     *     @type int|string $build_id
     *           A string that uniquely identifies a particular program version
     *           with high probability. E.g., for binaries generated by GNU tools,
     *           it could be the contents of the .note.gnu.build-id field.
     *     @type bool $has_functions
     *           The following fields indicate the resolution of symbolic info.
     *     @type bool $has_filenames
     *     @type bool $has_line_numbers
     *     @type bool $has_inline_frames
     * }
     */
    public function __construct($data = NULL) {
        \Pakutoma\Phreakscope\protobuf\metadata\Profile::initOnce();
        parent::__construct($data);
    }

    /**
     * Unique nonzero id for the mapping.
     *
     * Generated from protobuf field <code>uint64 id = 1;</code>
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Unique nonzero id for the mapping.
     *
     * Generated from protobuf field <code>uint64 id = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkUint64($var);
        $this->id = $var;

        return $this;
    }

    /**
     * Address at which the binary (or DLL) is loaded into memory.
     *
     * Generated from protobuf field <code>uint64 memory_start = 2;</code>
     * @return int|string
     */
    public function getMemoryStart()
    {
        return $this->memory_start;
    }

    /**
     * Address at which the binary (or DLL) is loaded into memory.
     *
     * Generated from protobuf field <code>uint64 memory_start = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setMemoryStart($var)
    {
        GPBUtil::checkUint64($var);
        $this->memory_start = $var;

        return $this;
    }

    /**
     * The limit of the address range occupied by this mapping.
     *
     * Generated from protobuf field <code>uint64 memory_limit = 3;</code>
     * @return int|string
     */
    public function getMemoryLimit()
    {
        return $this->memory_limit;
    }

    /**
     * The limit of the address range occupied by this mapping.
     *
     * Generated from protobuf field <code>uint64 memory_limit = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setMemoryLimit($var)
    {
        GPBUtil::checkUint64($var);
        $this->memory_limit = $var;

        return $this;
    }

    /**
     * Offset in the binary that corresponds to the first mapped address.
     *
     * Generated from protobuf field <code>uint64 file_offset = 4;</code>
     * @return int|string
     */
    public function getFileOffset()
    {
        return $this->file_offset;
    }

    /**
     * Offset in the binary that corresponds to the first mapped address.
     *
     * Generated from protobuf field <code>uint64 file_offset = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setFileOffset($var)
    {
        GPBUtil::checkUint64($var);
        $this->file_offset = $var;

        return $this;
    }

    /**
     * The object this entry is loaded from.  This can be a filename on
     * disk for the main binary and shared libraries, or virtual
     * abstractions like "[vdso]".
     *
     * Generated from protobuf field <code>int64 filename = 5;</code>
     * @return int|string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * The object this entry is loaded from.  This can be a filename on
     * disk for the main binary and shared libraries, or virtual
     * abstractions like "[vdso]".
     *
     * Generated from protobuf field <code>int64 filename = 5;</code>
     * @param int|string $var
     * @return $this
     */
    public function setFilename($var)
    {
        GPBUtil::checkInt64($var);
        $this->filename = $var;

        return $this;
    }

    /**
     * A string that uniquely identifies a particular program version
     * with high probability. E.g., for binaries generated by GNU tools,
     * it could be the contents of the .note.gnu.build-id field.
     *
     * Generated from protobuf field <code>int64 build_id = 6;</code>
     * @return int|string
     */
    public function getBuildId()
    {
        return $this->build_id;
    }

    /**
     * A string that uniquely identifies a particular program version
     * with high probability. E.g., for binaries generated by GNU tools,
     * it could be the contents of the .note.gnu.build-id field.
     *
     * Generated from protobuf field <code>int64 build_id = 6;</code>
     * @param int|string $var
     * @return $this
     */
    public function setBuildId($var)
    {
        GPBUtil::checkInt64($var);
        $this->build_id = $var;

        return $this;
    }

    /**
     * The following fields indicate the resolution of symbolic info.
     *
     * Generated from protobuf field <code>bool has_functions = 7;</code>
     * @return bool
     */
    public function getHasFunctions()
    {
        return $this->has_functions;
    }

    /**
     * The following fields indicate the resolution of symbolic info.
     *
     * Generated from protobuf field <code>bool has_functions = 7;</code>
     * @param bool $var
     * @return $this
     */
    public function setHasFunctions($var)
    {
        GPBUtil::checkBool($var);
        $this->has_functions = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool has_filenames = 8;</code>
     * @return bool
     */
    public function getHasFilenames()
    {
        return $this->has_filenames;
    }

    /**
     * Generated from protobuf field <code>bool has_filenames = 8;</code>
     * @param bool $var
     * @return $this
     */
    public function setHasFilenames($var)
    {
        GPBUtil::checkBool($var);
        $this->has_filenames = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool has_line_numbers = 9;</code>
     * @return bool
     */
    public function getHasLineNumbers()
    {
        return $this->has_line_numbers;
    }

    /**
     * Generated from protobuf field <code>bool has_line_numbers = 9;</code>
     * @param bool $var
     * @return $this
     */
    public function setHasLineNumbers($var)
    {
        GPBUtil::checkBool($var);
        $this->has_line_numbers = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool has_inline_frames = 10;</code>
     * @return bool
     */
    public function getHasInlineFrames()
    {
        return $this->has_inline_frames;
    }

    /**
     * Generated from protobuf field <code>bool has_inline_frames = 10;</code>
     * @param bool $var
     * @return $this
     */
    public function setHasInlineFrames($var)
    {
        GPBUtil::checkBool($var);
        $this->has_inline_frames = $var;

        return $this;
    }

}
