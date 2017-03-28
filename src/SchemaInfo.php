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

    // internal spec identifiers
    const SPEC_MISSING_FILE                     = 'missing';     // spec file missing
    const SPEC_INVALID_JSON                     = '../invalid';  // spec file contains invalid JSON
    const SPEC_NONE                             =  'none';       // no spec available
    const SPEC_PERMISSIVE                       =  'permissive'; // most permissive superset of options possible

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
            $matches = array();
            if (preg_match('~^https?://json-schema.org/(draft-[0-9]+)/schema($|#.*)~ui', $spec, $matches)) {
                switch ($matches[1]) {
                    case 'draft-04':
                        $spec = self::SPEC_DRAFT_04;
                        break;
                    case 'draft-03':
                        $spec = self::SPEC_DRAFT_03;
                        break;
                }
            }

            // make sure spec is valid
            if (!in_array($spec, array(
                'draft-03',
                'draft-04',
                'draft-05',
                'permissive',
                'missing',
                '../invalid',
            ))) {
                throw new \InvalidArgumentException('Unknown schema spec');
            }

            // load the spec ruleset file
            $specInfo = json_decode(file_get_contents(__DIR__ . "/../rules/standard/$spec.json"));
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
