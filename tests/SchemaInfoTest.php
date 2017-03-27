<?php

namespace Erayd\JsonSchemaInfo\Tests;

use Erayd\JsonSchemaInfo\SchemaInfo;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class SchemaInfoTest extends \PHPUnit\Framework\TestCase
{
    public function dataRulesets()
    {
        return array_map(function ($item) {
            return array($item);
        }, glob(__DIR__ . "/../rules/standard/*.json"));
    }

    /** @dataProvider dataRulesets **/
    public function testRulesets($ruleset)
    {
        $schema = json_decode(file_get_contents(__DIR__ . '/../rules/schema.json'));
        $ruleset = json_decode(file_get_contents($ruleset));

        $v = new Validator();
        $v->validate($ruleset, $schema, Constraint::CHECK_MODE_EXCEPTIONS | Constraint::CHECK_MODE_VALIDATE_SCHEMA);
        $this->assertTrue($v->isValid());
    }

    public function dataSchemaInfoFromURI()
    {
        return array(
            // various valid URI
            array('http://json-schema.org/draft-03/schema', SchemaInfo::SPEC_DRAFT_03),
            array('http://json-schema.org/draft-03/schema#', SchemaInfo::SPEC_DRAFT_03),
            array('http://json-schema.org/draft-03/schema#fragment', SchemaInfo::SPEC_DRAFT_03),
            array('https://json-schema.org/draft-04/schema', SchemaInfo::SPEC_DRAFT_04),

            array('https://json-schema.org/draft-05/schema#', 0, false), // draft-05 doesn't have a meta-schema
            array('http://example.com/schema', 0, false), // invalid URI

            array(5, 0, false), // invalid type for URI
            array('', 0, false), // empty string for URI
        );
    }

    /** @dataProvider dataSchemaInfoFromURI **/
    public function testGetSpecForURI($uri, $spec, $isValid = true)
    {
        if (!$isValid) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        $uriSchemaInfo = SchemaInfo::getSpecForURI($uri);

        $this->assertEquals($spec, $uriSchemaInfo);
    }

    public function dataLoadSchemaSpec()
    {
        return array(
            array('http://json-schema.org/draft-03/schema'),
            array('http://json-schema.org/draft-04/schema'),
            array(SchemaInfo::SPEC_DRAFT_05),
            array(SchemaInfo::SPEC_PERMISSIVE),
            array(SchemaInfo::SPEC_NONE, '\InvalidArgumentException'),
            array(SchemaInfo::SPEC_MISSING_FILE, '\RuntimeException'),
            array(SchemaInfo::SPEC_INVALID_JSON, '\RuntimeException'),
        );
    }

    /** @dataProvider dataLoadSchemaSpec **/
    public function testLoadSchemaSpec($spec, $exception = null)
    {
        if ($exception) {
            $this->setExpectedException($exception);
        }

        $s = new SchemaInfo($spec);
    }

    public function dataRules()
    {
        return array(
            array('types', 'string', true),
            array('types', 'any', false),
            array('formats', 'date-time', true),
            array('formats', 'date', false),
            array('keywords', 'required', true),
            array('keywords', 'extends', false),
            array('rules', 'requiredArray', true),
            array('rules', 'requiredBoolean', false),
            array('rules', 'invalidRule', true, '\InvalidArgumentException'),
            array('invalidSection', 'invalidRule', true, '\InvalidArgumentException'),
        );
    }

    /** @dataProvider dataRules **/
    public function testRules($section, $ruleName, $expectedValue, $exception = null)
    {
        if ($exception) {
            $this->setExpectedException($exception);
        }

        $s = new SchemaInfo(SchemaInfo::SPEC_DRAFT_04);
        $this->assertEquals($expectedValue, $s->rule($ruleName, $section));
    }

    /** @dataProvider dataRules **/
    public function testSectionHelpers($section, $ruleName, $expectedValue, $exception = null)
    {
        switch ($section) {
            case 'types':
                $method = 'type';
                break;
            case 'formats':
                $method = 'format';
                break;
            case 'keywords':
                $method = 'keyword';
                break;
            case 'rules':
                $method = 'rule';
                break;
            default:
                return; // don't test for undefined helpers
        }
        
        if ($exception) {
            $this->setExpectedException($exception);
        }

        $s = new SchemaInfo(SchemaInfo::SPEC_DRAFT_04);
        $this->assertEquals($expectedValue, $s->$method($ruleName));
    }

    public function testOrder()
    {
        $s = new SchemaInfo(SchemaInfo::SPEC_DRAFT_04);
        $this->assertEquals(array('$ref'), $s->getOrder());
    }
}
