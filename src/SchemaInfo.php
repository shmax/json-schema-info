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

    const SPEC_DRAFT_03                         =  'draft-03';
    // d03 (combined) https://tools.ietf.org/html/draft-zyp-json-schema-03

    const SPEC_DRAFT_04                         =  'draft-04';
    // d04c (core) https://tools.ietf.org/html/draft-zyp-json-schema-04
    // d04v (validation) https://tools.ietf.org/html/draft-fge-json-schema-validation-00
    // d04h (hyper-schema) https://tools.ietf.org/html/draft-luff-json-hyper-schema-00

    const SPEC_DRAFT_06                         =  'draft-06';
    // d06c (core) https://tools.ietf.org/html/draft-wright-json-schema-01
    // d06v (validation) https://tools.ietf.org/html/draft-wright-json-schema-validation-01
    // d06h (hyper-schema) https://tools.ietf.org/html/draft-wright-json-schema-hyperschema-01

    /** @var int Spec version */
    protected $specVersion = null;

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
            throw new \RuntimeException("Error loading spec: $errstr"); // @codeCoverageIgnore
        });

        try {
            // translate URI
            $spec = self::getSpecName($spec) ?: $spec;

            // make sure spec is valid
            if (!in_array($spec, array(
                self::SPEC_DRAFT_03,
                self::SPEC_DRAFT_04,
                self::SPEC_DRAFT_06,
            ))) {
                throw new \InvalidArgumentException('Unknown schema spec');
            }

            // load base ruleset file
            $baseInfo = json_decode(file_get_contents(__DIR__ . "/../rules/base.json"));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Unable to decode base ruleset file'); // @codeCoverageIgnore
            }

            // load the spec ruleset file
            $specInfo = json_decode(file_get_contents(__DIR__ . "/../rules/standard/$spec.json"));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Unable to decode ruleset file'); // @codeCoverageIgnore
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

            // build ruleset
            $finalInfo = new \StdClass();
            foreach ($baseInfo as $section => $rules) {
                $finalInfo->$section = new \StdClass();
                if (property_exists($specInfo, $section)) {
                    foreach ($specInfo->$section as $rule => $definition) {
                        $rule = $finalInfo->$section->$rule = $baseInfo->$section->$rule;
                        self::overlay($rule, $definition);
                        $rule->info = new RuleInfo(property_exists($rule, 'info') ? $rule->info : new \StdClass(), $rulesetSchema);
                    }
                }
            }

            $this->specVersion = $spec;
            $this->specInfo = $finalInfo;
            $this->rulesetSchema = $rulesetSchema;
            $this->specSchema = $specSchema;
        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }
        restore_error_handler();
    }

    /**
     * Get info for a section rule
     *
     * @api
     *
     * @param string $section
     * @param array $args
     * @return array|false
     */
    public function __call($section, $args)
    {
        if (!property_exists($this->specInfo, $section) || !property_exists($this->specInfo->$section, $args[0])) {
            return null;
        }

        return $this->specInfo->$section->{$args[0]}->info;
    }

    /**
     * Get the spec ruleset definition
     *
     * @api
     *
     * @return \StdClass
     */
    public function getDefinition()
    {
        return $this->specInfo;
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
    public function getURI()
    {
        switch ($this->specVersion) {
            case self::SPEC_DRAFT_06:
                return self::SPEC_DRAFT_06_URI;
            case self::SPEC_DRAFT_04:
                return self::SPEC_DRAFT_04_URI;
            case self::SPEC_DRAFT_03:
                return self::SPEC_DRAFT_03_URI;
        }
    } // @codeCoverageIgnore

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

    /**
     * Overlay one object onto another
     *
     * @param \StdClass $base
     * @param \StdClass $overlay
     */
    private static function overlay($base, $overlay)
    {
        foreach ($overlay as $property => $definition) {
            // @codeCoverageIgnoreStart
            // because no current ruleset uses this functionality
            if (is_object($definition)) {
                self::overlay($base->$property, $definition);
            } elseif (is_array($definition)) {
                $base->$property = array_merge($base->$property, $definition);
            } else {
                $base->$property = $definition;
            }
            // @codeCoverageIgnoreEnd
        }
    }
}
