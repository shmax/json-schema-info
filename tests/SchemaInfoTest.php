<?php

namespace Erayd\JsonSchemaInfo\Tests;

use Erayd\JsonSchemaInfo\SchemaInfo;

class SchemaInfoTest extends \PHPUnit\Framework\TestCase
{
    public function dataSchemaInfoFromURI()
    {
        return array(
            // various valid URI
            array('http://json-schema.org/draft-03/schema', SchemaInfo::SPEC_DRAFT_03),
            array('http://json-schema.org/draft-03/schema#', SchemaInfo::SPEC_DRAFT_03),
            array('http://json-schema.org/draft-03/schema#fragment', SchemaInfo::SPEC_DRAFT_03),
            array('https://json-schema.org/draft-04/schema', SchemaInfo::SPEC_DRAFT_04),
            array('https://json-schema.org/draft-05/schema#', SchemaInfo::SPEC_DRAFT_05),

            array('https://json-schema.org/draft-06/schema#', 0, false), // unsupported draft
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

    public function dataGetOption()
    {
        // all tests here are against draft-04
        return array(
            // valid tests
            array('typeString', true),  // camelCase option exists, true by default
            array('TYPE_STRING', true), // CONSTANT_CASE option exists, true by default
            array('typeInteger', true), // False by default, true for draft-04

            // invalid tests
            array('fakeOptionName', true, false),   // camelCase option doesn't exist
            array('FAKE_OPTION_NAME', true, false), // OPTION_CASE option doesn't exist
        );
    }

    /** @dataProvider dataGetOption **/
    public function testGetOption($name, $defaultValue, $isValid = true)
    {
        $s = new SchemaInfo(SchemaInfo::SPEC_DRAFT_04_URI);

        if (!$isValid) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        $this->assertEquals($defaultValue, $s->$name);
    }

    public function testGetOptionDraft03()
    {
        $s = new SchemaInfo(SchemaInfo::SPEC_DRAFT_03);
        $this->assertTrue($s->typeString);
    }

    public function testGetOptionDraft05()
    {
        $s = new SchemaInfo(SchemaInfo::SPEC_DRAFT_05);
        $this->assertTrue($s->typeString);
    }

    public function testUnknownSchemaSpec()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new SchemaInfo(SchemaInfo::SPEC_NONE);
    }

    public function testUnknownSchemaSpecURI()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new SchemaInfo('http://example.com/fake/schema');
    }
}
