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
    // internal spec identifiers
    const SPEC_MISSING_FILE                     = -2; // spec file missing
    const SPEC_INVALID_JSON                     = -1; // spec file contains invalid JSON
    const SPEC_NONE                             =  0; // no spec available
    const SPEC_PERMISSIVE                       =  1; // most permissive superset of options possible

    const SPEC_DRAFT_03                         =  3;
    // d03 (combined) https://tools.ietf.org/html/draft-zyp-json-schema-03

    const SPEC_DRAFT_04                         =  4;
    // d04c (core) https://tools.ietf.org/html/draft-zyp-json-schema-04
    // d04v (validation) https://tools.ietf.org/html/draft-fge-json-schema-validation-00
    // d04h (hyper-schema) https://tools.ietf.org/html/draft-luff-json-hyper-schema-00

    const SPEC_DRAFT_05                         =  5;
    // d05c (core) https://tools.ietf.org/html/draft-wright-json-schema-00
    // d05v (validation) https://tools.ietf.org/html/draft-wright-json-schema-validation-00
    // d05h (hyper-schema) https://tools.ietf.org/html/draft-wright-json-schema-hyperschema-00

    // spec URIs
    const SPEC_DRAFT_03_URI                     =  'http://json-schema.org/draft-03/schema#';
    const SPEC_DRAFT_04_URI                     =  'http://json-schema.org/draft-04/schema#';

    /** @var int Spec version */
    protected $specVersion = self::SPEC_NONE;

    /** @var \StdClass Spec rules */
    protected $specInfo = null;

    /**
     * Create a new SchemaInfo instance for the provided spec
     *
     * @api
     *
     * @param mixed $spec URI string or spec int constant
     */
    public function __construct($spec)
    {
        set_error_handler(function ($errno, $errstr) {
            throw new \RuntimeException("Error loading spec: $errstr");
        });

        try {
            // make sure spec is an int
            if (!is_int($spec)) {
                $spec = self::getSpecForURI($spec);
            }

            // spec-specific setup
            switch ($spec) {
                case self::SPEC_DRAFT_05:
                    $ruleset = 'draft-05';
                    break;
                case self::SPEC_DRAFT_04:
                    $ruleset = 'draft-04';
                    break;
                case self::SPEC_DRAFT_03:
                    $ruleset = 'draft-03';
                    break;
                case self::SPEC_PERMISSIVE:
                    $ruleset = 'permissive';
                    break;
                case self::SPEC_MISSING_FILE:
                    $ruleset = 'missing';
                    break;
                case self::SPEC_INVALID_JSON:
                    $ruleset = '../invalid';
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown schema spec');
            }

            // load the spec ruleset file
            $specInfo = json_decode(file_get_contents(__DIR__ . "/../rules/standard/$ruleset.json"));
            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new \RuntimeException('Unable to decode ruleset file');
            }

            $this->specInfo = $specInfo;
        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }
        restore_error_handler();
    }

    /**
     * Get the spec version by URI
     *
     * @api
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
                case 'draft-04':
                    return self::SPEC_DRAFT_04;
                case 'draft-03':
                    return self::SPEC_DRAFT_03;
            }
        }

        throw new \InvalidArgumentException("Unknown schema spec: $uri");
    }

    /**
     * Get a list of keywords that should be applied first, in they order they should be applied
     *
     * @api
     *
     * @return string[]
     */
    public function getOrder()
    {
        return $this->specInfo->order;
    }

    /**
     * Get a rule
     *
     * @api
     *
     * @param string $ruleName Rule name
     * @param string $section Rule section [type|format|keyword|rule]
     * @return bool
     */
    public function rule($ruleName, $section = 'rules')
    {
        if (!isset($this->specInfo->$section)) {
            throw new \InvalidArgumentException("Invalid section: $section");
        }
        if (!isset($this->specInfo->$section->$ruleName)) {
            throw new \InvalidArgumentException("Invalid rule name: $section.$ruleName");
        }

        return $this->specInfo->$section->$ruleName->value;
    }

    /**
     * Get a type rule
     *
     * @api
     *
     * @param string $typeName Type name
     * @return bool
     */
    public function type($typeName)
    {
        return $this->rule($typeName, 'types');
    }

    /**
     * Get a format rule
     *
     * @api
     *
     * @param string $formatName Format name
     * @return bool
     */
    public function format($formatName)
    {
        return $this->rule($formatName, 'formats');
    }

    /**
     * Get a keyword rule
     *
     * @api
     *
     * @param string $keywordName
     * @return bool
     */
    public function keyword($keywordName)
    {
        return $this->rule($keywordName, 'keywords');
    }
}
