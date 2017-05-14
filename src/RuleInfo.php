<?php

namespace Erayd\JsonSchemaInfo;

/**
 * Provide info on a specific rule
 *
 * @package json-schema-info
 * @license ISC
 * @author Steve Gilberd <steve@erayd.net>
 * @copyright (c) 2017 Erayd LTD
 */
class RuleInfo extends \StdClass
{
    /** @var \StdClass Rule definition */
    private $info = null;

    /** @var \StdClass Rule schema */
    private $infoRuleList = null;

    /**
     * Create a new instance
     *
     * @param \StdClass $ruleInfo Defined rule information
     * @param \StdClass $schema Ruleset schema
     */
    public function __construct($ruleInfo, $schema)
    {
        $this->info = $ruleInfo;
        $this->infoRuleList = $schema->additionalProperties->additionalProperties->properties->info->properties;
    }

    /**
     * Get a rule value
     *
     * @param string $infoRule Rule name
     * @return boolean
     */
    public function __get($infoRule)
    {
        if (!property_exists($this->info, $infoRule)) {
            if (!property_exists($this->infoRuleList, $infoRule)) {
                throw new \InvalidArgumentException("Invalid rule: $infoRule");
            }
            $this->info->$infoRule = $this->infoRuleList->$infoRule->default;
        }

        return $this->info->$infoRule;
    }
}
