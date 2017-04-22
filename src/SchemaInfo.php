<?php

namespace Erayd\JsonSchemaInfo;

/**
 * Provide info on various json-schema specification standards
 *
 * @package json-schema-info
 * @license ISC
 * @author Steve Gilberd <steve@erayd.net>
 * @copyright (c) 2017 Erayd LTD
 */
class SchemaInfo
{
    // spec URIs
    const SPEC_DRAFT_03_URI                     =  'http://json-schema.org/draft-03/schema#';
    const SPEC_DRAFT_04_URI                     =  'http://json-schema.org/draft-04/schema#';
    const SPEC_DRAFT_06_URI                     =  'http://json-schema.org/draft-06/schema#';

    // internal spec identifiers
    const SPEC_MISSING_FILE                     = 'missing';     // spec file missing
    const SPEC_INVALID_JSON                     = '../invalid';  // spec file contains invalid JSON
    const SPEC_NONE                             =  'none';       // no spec available

    const SPEC_DRAFT_03                         =  'draft-03';
    // d03 (combined) https://tools.ietf.org/html/draft-zyp-json-schema-03

    const SPEC_DRAFT_04                         =  'draft-04';
    // d04c (core) https://tools.ietf.org/html/draft-zyp-json-schema-04
    // d04v (validation) https://tools.ietf.org/html/draft-fge-json-schema-validation-00
    // d04h (hyper-schema) https://tools.ietf.org/html/draft-luff-json-hyper-schema-00

    const SPEC_DRAFT_05                         =  'draft-05';
    // d05c (core) https://tools.ietf.org/html/draft-wright-json-schema-00
    // d05v (validation) https://tools.ietf.org/html/draft-wright-json-schema-validation-00
    // d05h (hyper-schema) https://tools.ietf.org/html/draft-wright-json-schema-hyperschema-00

    const SPEC_DRAFT_06                         =  'draft-06';
    // d06c (core) https://tools.ietf.org/html/draft-wright-json-schema-01
    // d06v (validation) https://tools.ietf.org/html/draft-wright-json-schema-validation-01
    // d06h (hyper-schema) https://tools.ietf.org/html/draft-wright-json-schema-hyperschema-01

    /** @var int Spec version */
    protected $specVersion = self::SPEC_NONE;

    /** @var \StdClass Spec rules */
    protected $specInfo = null;

    /** @var \StdClass Ruleset schema */
    protected $rulesetSchema = null;

    /** @var \StdClass Spec schema */
    protected $specSchema = null;

    /**
     * Create a new SchemaInfo instance for the provided spec
     *
     * @api
     *
     * @param mixed $spec URI string or spec int constant
     */
    public function __construct($spec)
    {
        // check type
        if (!is_string($spec)) {
            throw new \InvalidArgumentException('Spec must be a string');
        }

        // catch errors
        set_error_handler(function ($errno, $errstr) {
            throw new \RuntimeException("Error loading spec: $errstr");
        });

        try {
            // translate URI
            $spec = self::getSpecName($spec) ?: $spec;

            // make sure spec is valid
            if (!in_array($spec, array(
                self::SPEC_DRAFT_03,
                self::SPEC_DRAFT_04,
                self::SPEC_DRAFT_05,
                self::SPEC_DRAFT_06,
                self::SPEC_MISSING_FILE,
                self::SPEC_INVALID_JSON,
            ))) {
                throw new \InvalidArgumentException('Unknown schema spec');
            }

            // load the spec ruleset file
            $specInfo = json_decode(file_get_contents(__DIR__ . "/../rules/standard/$spec.json"));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Unable to decode ruleset file');
            }

            // load the ruleset schema file
            $rulesetSchema = json_decode(file_get_contents(__DIR__ . "/../rules/schema.json"));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Unable to decode ruleset schema file'); // @codeCoverageIgnore
            }

            // load the spec schema file
            $specSchema = json_decode(file_get_contents(__DIR__ . "/../dist/$spec/schema.json"));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Unable to decode ruleset schema file'); // @codeCoverageIgnore
            }

            $this->specVersion = $spec;
            $this->specInfo = $specInfo;
            $this->rulesetSchema = $rulesetSchema;
            $this->specSchema = $specSchema;
        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }
        restore_error_handler();
    }

    /**
     * Get a rule
     *
     * @api
     *
     * @param string $ruleName Rule name
     * @param string $section Rule section [type|format|keyword|rule]
     * @param \StdClass $constraints constraints object to set
     * @return bool
     */
    public function rule($ruleName, $section = 'rules', &$constraints = null)
    {
        if (!isset($this->specInfo->$section)) {
            throw new \InvalidArgumentException("Invalid section: $section");
        }
        if (!isset($this->specInfo->$section->$ruleName)) {
            throw new \InvalidArgumentException("Invalid rule name: $section.$ruleName");
        }

        // set constraints object
        $constraints = new \stdClass();
        foreach ($this->rulesetSchema->definitions->rule->properties->constraints->properties as $name => $constraint) {
            if (isset($this->specInfo->$section->$ruleName->constraints)
                && isset($this->specInfo->$section->$ruleName->constraints->$name)
            ) {
                $constraints->$name = $this->specInfo->$section->$ruleName->constraints->$name;
            } elseif (property_exists($constraint, 'default')) {
                $constraints->$name = $constraint->default;
            }
        }

        return $this->specInfo->$section->$ruleName->value;
    }

    /**
     * Get a type rule
     *
     * @api
     *
     * @param string $typeName Type name
     * @param \StdClass $constraints constraints object to set
     * @return bool
     */
    public function type($typeName, &$constraints = null)
    {
        return $this->rule($typeName, 'types', $constraints);
    }

    /**
     * Get a format rule
     *
     * @api
     *
     * @param string $formatName Format name
     * @param \StdClass $constraints constraints object to set
     * @return bool
     */
    public function format($formatName, &$constraints = null)
    {
        return $this->rule($formatName, 'formats', $constraints);
    }

    /**
     * Get a keyword rule
     *
     * @api
     *
     * @param string $keywordName
     * @param \StdClass $constraints constraints object to set
     * @return bool
     */
    public function keyword($keywordName, &$constraints = null)
    {
        return $this->rule($keywordName, 'keywords', $constraints);
    }

    /**
     * Get the spec meta-schema for validation
     *
     * @api
     *
     * @return \StdClass
     */
    public function getSchema()
    {
        return $this->specSchema;
    }

    /**
     * Get the spec meta-schema URI
     *
     * @api
     *
     * @return string
     */
    public function getURI($specVersion = null)
    {
        switch ($specVersion ?: $this->specVersion) {
            case self::SPEC_DRAFT_03:
                return self::SPEC_DRAFT_03_URI;
            case self::SPEC_DRAFT_04: // draft-04 and draft-05 share the same meta-schema uri
            case self::SPEC_DRAFT_05:
                return self::SPEC_DRAFT_04_URI;
            case self::SPEC_DRAFT_06:
                return self::SPEC_DRAFT_06_URI;
        }

        throw new \InvalidArgumentException('No URI defined for spec: ' . $this->specVersion);
    }

    /**
     * Get the spec name for a meta-schema URI
     *
     * @api
     *
     * @param string $uri
     * @return string
     */
    public static function getSpecName($uri)
    {
        // check type
        if (!is_string($uri)) {
            throw new \InvalidArgumentException('URI must be a string');
        }

        // translate URI
        $matches = array();
        if (preg_match('~^https?://json-schema.org/(draft-0[346])/schema($|#.*)~ui', $uri, $matches)) {
            switch ($matches[1]) {
                case 'draft-06':
                    return self::SPEC_DRAFT_06;
                case 'draft-04':
                    return self::SPEC_DRAFT_04;
                case 'draft-03':
                    return self::SPEC_DRAFT_03;
            }
        }

        // no match found
        return null;
    }
}
