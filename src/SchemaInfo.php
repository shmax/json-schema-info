<?php

namespace Erayd\JsonSchemaInfo;

/**
 * Provide info on various json-schema specification standards
 *
 * @package json-schema-info
 * @license ISC
 * @author Steve Gilberd <steve@erayd.net>
 */
class SchemaInfo
{
    const SPEC_NONE = 0;
    const SPEC_DRAFT_03 = 1;
    const SPEC_DRAFT_04 = 2;
    const SPEC_DRAFT_05 = 3;

    const SPEC_DRAFT_03_URI = 'http://json-schema.org/draft-03/schema#';
    const SPEC_DRAFT_04_URI = 'http://json-schema.org/draft-04/schema#';
    const SPEC_DRAFT_05_URI = 'http://json-schema.org/draft-05/schema#';

    // primitive types
    const OPT_TYPE_STRING       = true;  // primitive type string is allowed
    const OPT_TYPE_NUMBER       = true;  // primitive type number is allowed
    const OPT_TYPE_INTEGER      = false; // primitive type integer is allowed
    const OPT_TYPE_BOOLEAN      = true;  // primitive type boolean is allowed
    const OPT_TYPE_OBJECT       = true;  // primitive type object is allowed
    const OPT_TYPE_ARRAY        = true;  // primitive type array is allowed
    const OPT_TYPE_NULL         = true;  // primitive type null is allowed
    const OPT_TYPE_ANY          = false; // primitive type any is allowed
    const OPT_TYPE_OTHER        = false; // other, non-spec primitive types are allowed

    // basic properties
    const OPT_SELF_DESCRIPTIVE_SCHEMA   = false; // Whether $schema must validate against itself

    // numeric constraints
    const OPT_CONSTRAINT_DIVISIBLE_BY       = false; // Whether "divisibleBy" is supported
    const OPT_CONSTRAINT_MULTIPLE_OF        = true;  // Whether "multipleOf" is supported
    const OPT_CONSTRAINT_MAXIMUM            = true;  // Whether "maxumum" is supported
    const OPT_CONSTRAINT_EXCLUSIVE_MAXUMUM  = true;  // Whether "exclusiveMaximum" is supported
    const OPT_CONSTRAINT_MINIMUM            = true;  // Whether "minimum" is supported
    const OPT_CONSTRAINT_EXCLUSIVE_MINIMUM  = true;  // Whether "exclusiveMinimum" is supported

    // string constraints
    const OPT_CONSTRAINT_MIN_LENGTH         = true;  // Whether "minLength" is supported
    const OPT_CONSTRAINT_MAX_LENGTH         = true;  // Whether "maxLength" is supported
    const OPT_CONSTRAINT_PATTERN            = true;  // Whether "pattern" is supported

    /** @var int Spec version **/
    protected $specVersion = self::SPEC_NONE;

    /** @var array Feature matrix */
    protected $matrix = array();

    /**
     * @param mixed $spec URI string or spec int constant
     */
    public function __construct($spec)
    {
        // make sure spec is an int
        if (!is_int($spec)) {
            $spec = self::getSpecForURI($spec);
        }

        // spec-specific setup
        switch ($spec) {
            case self::SPEC_DRAFT_05:
                $this->setDraft05();
                break;
            case self::SPEC_DRAFT_04:
                $this->setDraft04();
                break;
            case self::SPEC_DRAFT_03:
                $this->setDraft03();
                break;
            default:
                throw new \InvalidArgumentException('Unknown schema spec');
        }
    }

    /**
     * Get the status of an option
     */
    public function __get($optionName)
    {
        $defaultValue;
        $option = self::getOptionConstant($optionName, $defaultValue);

        if (!array_key_exists($option, $this->matrix)) {
            $this->matrix[$option] = $defaultValue;
        }

        return $this->matrix[$option];
    }

    /**
     * Get the spec version by URI
     *
     * @param string $uri
     * @return int
     */
    public static function getSpecForURI($uri)
    {
        if (!is_string($uri) || !strlen($uri)) {
            throw new \InvalidArgumentException('You must provide a URI');
        }

        $matches = array();
        if (preg_match('~^https?://json-schema.org/(draft-[0-9]+)/schema($|#.*)~ui', $uri, $matches)) {
            switch ($matches[1]) {
                case 'draft-05':
                    return self::SPEC_DRAFT_05;
                case 'draft-04':
                    return self::SPEC_DRAFT_04;
                case 'draft-03':
                    return self::SPEC_DRAFT_03;
            }
        }

        throw new \InvalidArgumentException("Unknown schema spec: $uri");
    }

    /**
     * Get the constant name for a given camelCase option
     *
     * @param string $option Option name (camelCase or CONSTANT_CASE)
     * @return string
     */
    public static function getOptionConstant($optionName, &$defaultValue = null)
    {
        $words = preg_split('/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z0-9])/', $optionName);
        array_walk($words, function (&$item) {
            $item = strtoupper($item);
        });
        $optionConst = 'OPT_' . implode('_', $words);

        if (!defined('\Erayd\JsonSchemaInfo\SchemaInfo::' . $optionConst)) {
            throw new \InvalidArgumentException("No option constant $optionConst available for $optionName");
        }

        $defaultValue = constant('\Erayd\JsonSchemaInfo\SchemaInfo::' . $optionConst);

        return $optionConst;
    }

    /**
     * Set options
     * @param array $options Options to set
     */
    private function setOptions($options)
    {
        foreach ($options as $option => $value) {
            $this->matrix[$option] = $value;
        }
    }

    /**
     * Apply options that are unique to draft-03
     */
    protected function setDraft03()
    {
        $this->setOptions(array(
            'OPT_TYPE_INTEGER'              => true,
            'OPT_TYPE_ANY'                  => true,
            'OPT_TYPE_OTHER'                => true,
            'OPT_CONSTRAINT_DIVISIBLE_BY'   => true,
            'OPT_CONSTRAINT_MULTIPLE_OF'    => false,
        ));
    }

    /**
     * Apply options that are unique to draft-04
     */
    protected function setDraft04()
    {
        $this->setOptions(array(
            'OPT_TYPE_INTEGER'              => true,
            'OPT_SELF_DESCRIPTIVE_SCHEMA'   => true,
        ));
    }

    /**
     * Apply options that are unique to draft-05
     */
    protected function setDraft05()
    {
        $this->setOptions(array(

        ));
    }
}
