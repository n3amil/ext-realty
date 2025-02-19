<?php

namespace OliverKlee\Realty\Tests\Unit\Import;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use OliverKlee\Realty\Tests\Unit\Import\Fixtures\TestingDomDocumentConverter;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Benjamin Schulte <benj@minschulte.de>
 */
class DomDocumentConverterTest extends UnitTestCase
{
    /**
     * @var TestingDomDocumentConverter
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new TestingDomDocumentConverter();
    }

    /*
     * Utility functions
     */

    /**
     * Loads an XML string, sets the raw realty data and returns a DOMDocument
     * of the provided string.
     *
     * @param string $xmlString XML string to set for converting, must contain well-formed XML, must not be empty
     *
     * @return \DOMDocument \DOMDocument of the provided XML string
     */
    private function setRawDataToConvert($xmlString)
    {
        $loadedXml = new \DOMDocument();
        $loadedXml->loadXML($xmlString);
        $this->subject->setRawRealtyData($loadedXml);

        return $loadedXml;
    }

    /////////////////////////////////////
    // Testing the domDocumentConverter
    /////////////////////////////////////

    /**
     * @test
     */
    public function findFirstGrandchildReturnsGrandchildIfItExists()
    {
        $this->setRawDataToConvert(
            '<immobilie>'
            . '<child>'
            . '<grandchild>foo</grandchild>'
            . '</child>'
            . '</immobilie>'
        );

        self::assertSame(
            'foo',
            $this->subject->findFirstGrandchild('child', 'grandchild')->nodeValue
        );
    }

    /**
     * @test
     */
    public function findFirstGrandchildReturnsNullIfTheGrandchildDoesNotExists()
    {
        $this->setRawDataToConvert(
            '<immobilie>'
            . '<child/>'
            . '</immobilie>'
        );

        self::assertNull(
            $this->subject->findFirstGrandchild('child', 'grandchild')
        );
    }

    /**
     * @test
     */
    public function findFirstGrandchildReturnsNullIfTheGivenDomNodeIsEmpty()
    {
        $this->setRawDataToConvert(
            '<immobilie/>'
        );

        self::assertNull(
            $this->subject->findFirstGrandchild('child', 'grandchild')
        );
    }

    /**
     * @test
     */
    public function firstGrandchildIsFoundAlthoughTheSecondChildAndItsChildAlsoMatch()
    {
        $this->setRawDataToConvert(
            '<immobilie>'
            . '<child>'
            . '<grandchild>foo</grandchild>'
            . '</child>'
            . '<child>'
            . '<grandchild>bar</grandchild>'
            . '</child>'
            . '</immobilie>'
        );

        self::assertSame(
            'foo',
            $this->subject->findFirstGrandchild('child', 'grandchild')->nodeValue
        );
    }

    /**
     * @test
     */
    public function firstGrandchildIsFoundAlthoughTheFirstChildHasTwoMatchingChildren()
    {
        $this->setRawDataToConvert(
            '<immobilie>'
            . '<child>'
            . '<grandchild>foo</grandchild>'
            . '<grandchild>bar</grandchild>'
            . '</child>'
            . '</immobilie>'
        );

        self::assertSame(
            'foo',
            $this->subject->findFirstGrandchild('child', 'grandchild')->nodeValue
        );
    }

    /**
     * @test
     */
    public function getNodeNameDoesNotChangeNodeNameWithoutXmlNamespace()
    {
        $node = new \DOMDocument();
        $child = $node->appendChild($node->createElement('foo'));

        self::assertSame(
            'foo',
            $this->subject->getNodeName($child)
        );
    }

    /**
     * @test
     */
    public function getNodeNameReturnsNameWithoutXmlNamespaceWhenNameWithXmlNamespaceGiven()
    {
        $node = new \DOMDocument();
        $child = $node->appendChild($node->createElement('prefix:foo'));

        self::assertSame(
            'foo',
            $this->subject->getNodeName($child)
        );
    }

    /**
     * @test
     */
    public function addOneElementToTheRealtyDataArray()
    {
        $data = [];
        $this->subject->addElementToArray($data, 'foo', 'bar');

        self::assertSame(
            ['foo' => 'bar'],
            $data
        );
    }

    /**
     * @test
     */
    public function addTwoElementsToTheRealtyDataArray()
    {
        $data = [];
        $this->subject->addElementToArray($data, 'foo', 'foo');
        $this->subject->addElementToArray($data, 'bar', 'bar');

        self::assertSame(
            ['foo' => 'foo', 'bar' => 'bar'],
            $data
        );
    }

    /**
     * @test
     */
    public function addOneElementTwiceToTheRealtyDataArray()
    {
        $data = [];
        $this->subject->addElementToArray($data, 'foo', 'foo');
        $this->subject->addElementToArray($data, 'foo', 'bar');

        self::assertSame(
            ['foo' => 'bar'],
            $data
        );
    }

    //////////////////////////////////////
    // Tests concerning getConvertedData
    //////////////////////////////////////

    /**
     * @test
     */
    public function getConvertedDataForNoRecordsReturnsEmptyArray()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter/>'
            . '</openimmo>'
        );

        self::assertSame(
            [],
            $this->subject->getConvertedData($node)
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForOneEmptyRecordReturnsDefaultValues()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie/>'
            . '</anbieter>'
            . '</openimmo>'
        );

        self::assertEquals(
            [
                [
                    'sales_area' => 0.0,
                    'other_area' => 0.0,
                    'window_bank' => 0.0,
                    'rental_income_target' => 0.0,
                    'energy_certificate_issue_date' => 0,
                    'rent_with_heating_costs' => 0.0,
                ],
            ],
            $this->subject->getConvertedData($node)
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForSeveralEmptyRecordsReturnsArrayOfDefaultValues()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie/>' .
            '<immobilie/>' .
            '</anbieter>' .
            '</openimmo>'
        );

        self::assertEquals(
            [
                [
                    'sales_area' => 0.0,
                    'other_area' => 0.0,
                    'window_bank' => 0.0,
                    'rental_income_target' => 0.0,
                    'energy_certificate_issue_date' => 0,
                    'rent_with_heating_costs' => 0.0,
                ],
                [
                    'sales_area' => 0.0,
                    'other_area' => 0.0,
                    'window_bank' => 0.0,
                    'rental_income_target' => 0.0,
                    'energy_certificate_issue_date' => 0,
                    'rent_with_heating_costs' => 0.0,
                ],
            ],
            $this->subject->getConvertedData($node)
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanImportSeveralObjects()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo',
            $importedData[0]['title'],
            'The first object is missing.'
        );
        self::assertSame(
            'bar',
            $importedData[1]['title'],
            'The second object is missing.'
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReadsObjectTitle()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>klein und teuer</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'klein und teuer',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesSingleLinefeedInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . LF . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesDoubleLinefeedInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . LF . LF . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesSingleCarriageReturnInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . CR . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesDoubleCarriageReturnInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . CR . CR . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesCrLfInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . CRLF . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * data provider for all fields converted to HTML
     *
     * @return string[][]
     */
    public function htmlFieldsDataProvider()
    {
        return [
            'location' => ['lage', 'location'],
            'misc' => ['sonstige_angaben', 'misc'],
            'equipment' => ['ausstatt_beschr', 'equipment'],
            'description' => ['objektbeschreibung', 'description'],
        ];
    }

    /**
     * @test
     *
     * @param string $xmlKey the name of the XML tag
     * @param string $arrayKey the name of the array key
     *
     * @dataProvider htmlFieldsDataProvider
     */
    public function getConvertedDataForCrLfReplacesCrLfWithLfInRichTextField($xmlKey, $arrayKey)
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<' . $xmlKey . '>foo' . CRLF . 'bar 123</' . $xmlKey . '>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $importedData = $this->subject->getConvertedData($node);

        self::assertSame(
            'foo' . LF . 'bar 123',
            $importedData[0][$arrayKey]
        );
    }

    /**
     * @test
     *
     * @param string $xmlKey the name of the XML tag
     * @param string $arrayKey the name of the array key
     *
     * @dataProvider htmlFieldsDataProvider
     */
    public function getConvertedDataForCrReplacesCrWithLfInRichTextField($xmlKey, $arrayKey)
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<' . $xmlKey . '>foo' . CR . 'bar 123</' . $xmlKey . '>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $importedData = $this->subject->getConvertedData($node);

        self::assertSame(
            'foo' . LF . 'bar 123',
            $importedData[0][$arrayKey]
        );
    }

    /**
     * @test
     *
     * @param string $xmlKey the name of the XML tag
     * @param string $arrayKey the name of the array key
     *
     * @dataProvider htmlFieldsDataProvider
     */
    public function getConvertedDataForLfKeepsLfInRichTextField($xmlKey, $arrayKey)
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<' . $xmlKey . '>foo' . LF . 'bar 123</' . $xmlKey . '>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $importedData = $this->subject->getConvertedData($node);

        self::assertSame(
            'foo' . LF . 'bar 123',
            $importedData[0][$arrayKey]
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesSingleTabInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . TAB . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReplacesMultipleTabsInObjectTitleWithSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<freitexte>' .
            '<objekttitel>foo' . TAB . TAB . 'bar</objekttitel>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo bar',
            $importedData[0]['title']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForSaleTrueAndRentFalseCreatesSaleObject()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<vermarktungsart KAUF="true" MIETE_PACHT="false"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
            $importedData[0]['object_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForSaleFalseAndRentTrueCreatesRentObject()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<vermarktungsart KAUF="false" MIETE_PACHT="true"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            $importedData[0]['object_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForSaleOneAndRentZeroCreatesSaleObject()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<vermarktungsart KAUF="1" MIETE_PACHT="0"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
            $importedData[0]['object_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForSaleZeroAndRentOneCreatesRentObject()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<vermarktungsart KAUF="0" MIETE_PACHT="1"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
            $importedData[0]['object_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReturnsUniversalDataAndDefaultValuesInEachRecord()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<firma>foo</firma>'
            . '<openimmo_anid>bar</openimmo_anid>'
            . '<immobilie/>'
            . '<immobilie/>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $universalDataAndDefaultValues = [
            'sales_area' => 0.0,
            'other_area' => 0.0,
            'window_bank' => 0.0,
            'rental_income_target' => 0.0,
            'employer' => 'foo',
            'openimmo_anid' => 'bar',
            'energy_certificate_issue_date' => 0,
            'rent_with_heating_costs' => 0.0,
        ];
        $result = $this->subject->getConvertedData($node);

        self::assertEquals(
            $universalDataAndDefaultValues,
            $result[0],
            'The first record has been imported incorrectly.'
        );
        self::assertEquals(
            $universalDataAndDefaultValues,
            $result[1],
            'The second record has been imported incorrectly.'
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanImportSeveralProperties()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<strasse>foobar</strasse>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<freitexte>' .
            '<lage>foo</lage>' .
            '</freitexte>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            'foobar',
            $importedData[0]['street'],
            'The street is missing.'
        );
        self::assertSame(
            'bar',
            $importedData[0]['zip'],
            'The ZIP is missing.'
        );
        self::assertSame(
            'foo',
            $importedData[0]['location'],
            'The location is missing.'
        );
    }

    /**
     * @test
     */
    public function getConvertedDataSubstitutesSurplusDecimalsWhenAPositiveNumberIsGiven()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>1.00</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '1',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataSubstitutesSurplusDecimalsWhenANegativeNumberIsGiven()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>-1.00</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '-1',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataSubstitutesTwoSurplusDecimalsWhenZeroIsGiven()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>0.00</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '0',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataSubstitutesOneSurplusDecimalWhenZeroIsGiven()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>0.0</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '0',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotSubstitutesTwoNonSurplusDecimalsFromAPositiveNumber()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>1.11</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '1.11',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotSubstitutesTwoNonSurplusDecimalsFromANegativeNumber()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>-1.11</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '-1.11',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotSubstitutesOneNonSurplusDecimals()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>1.1</strasse>'
            . '</geo>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '1.1',
            $result[0]['street']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesContactPersonName()
    {
        $name = 'Jane Doe';
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<kontaktperson>' .
            '<name>' . $name . '</name>' .
            '</kontaktperson>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $name,
            $result[0]['contact_person']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesContactPersonFirstName()
    {
        $name = 'Jane';
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<kontaktperson>' .
            '<vorname>' . $name . '</vorname>' .
            '</kontaktperson>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $name,
            $result[0]['contact_person_first_name']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesContactPersonSalutation()
    {
        $salutation = 'Frau';
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<kontaktperson>' .
            '<anrede>' . $salutation . '</anrede>' .
            '</kontaktperson>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $salutation,
            $result[0]['contact_person_salutation']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesAlternativeContactEmail()
    {
        $emailAddress = 'any-email@example.com';
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<kontaktperson>' .
            '<email_direkt>' . $emailAddress . '</email_direkt>' .
            '</kontaktperson>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $emailAddress,
            $result[0]['contact_email']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataGetsStateIfValidStateProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<zustand_angaben>' .
            '<zustand ZUSTAND_ART="gepflegt" />' .
            '</zustand_angaben>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            8,
            $result[0]['state']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataDoesNotGetStateIfInvalidStateProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<zustand_angaben>' .
            '<zustand ZUSTAND_ART="geputzt" />' .
            '</zustand_angaben>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['state'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetOneValidHeatingTypeSetToTrue()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(2, $result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetOneValidHeatingTypeSetToOne()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="1" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            2,
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetMultipleValidHeatingTypesFromHeatingTypeNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="true" OFEN="true" ETAGE="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '2,9,11',
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReadsHeatingSetToFalseAsFalse()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="false" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEmpty(
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataReadsHeatingSetToZeroAsFalse()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="0" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEmpty(
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesHeatingTypesMixedTrueAndFalse()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="true" OFEN="false" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(2, $result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetMultipleValidHeatingTypesFromFiringNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung OEL="true" GAS="true" BLOCK="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '5,8,12',
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetHeatingTypesFromFiringNodeAndHeatingTypeNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart OFEN="true" />' .
            '<befeuerung BLOCK="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '11,12',
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesFiringNodeWithTrue()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung BLOCK="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(12, $result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesFiringNodeWithOne()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung BLOCK="1" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(12, $result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesFiringNodeWithFalseAsNotSet()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung BLOCK="false" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEmpty($result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesFiringNodeWithZeroAsNotSet()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung BLOCK="0" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEmpty($result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesFiringNodeWithTrueAndFalseMixed()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung BLOCK="false" OEL="true"/>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(8, $result[0]['heating_type']);
    }

    /**
     * @test
     */
    public function getConvertedDataDoesNotGetInvalidHeatingTypeFromHeatingTypeNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart BACKOFEN="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['heating_type'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataDoesNotGetInvalidHeatingTypeFromFiringNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<befeuerung KERZE="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['heating_type'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataOnlyGetsValidHeatingTypesIfValidAndInvalidTypesProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<heizungsart ZENTRAL="true" FUSSBODEN="true" BACKOFEN="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '2,4',
            $result[0]['heating_type']
        );
    }

    /**
     * @return array[]
     */
    public function heatingTypeDataProvider()
    {
        return [
            'OFEN' => ['OFEN', 11],
            'ETAGE' => ['ETAGE', 9],
            'ZENTRAL' => ['ZENTRAL', 2],
            'FERN' => ['FERN', 1],
            'FUSSBODEN' => ['FUSSBODEN', 4],
        ];
    }

    /**
     * @test
     *
     * @param string $name
     * @param int $id
     *
     * @dataProvider heatingTypeDataProvider
     */
    public function getConvertedDataImportsAllHeatingTypes($name, $id)
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>
                <anbieter>
                    <immobilie>
                        <ausstattung>
                            <heizungsart ' . $name . '="true"/>
                        </ausstattung>
                    </immobilie>
                </anbieter>
             </openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $id,
            $result[0]['heating_type']
        );
    }

    /**
     * @return array[]
     */
    public function firingTypeDataProvider()
    {
        return [
            'OEL' => ['OEL', 8],
            'GAS' => ['GAS', 5],
            'ELEKTRO' => ['ELEKTRO', 3],
            'ALTERNATIV' => ['ALTERNATIV', 6],
            'SOLAR' => ['SOLAR', 10],
            'ERDWAERME' => ['ERDWAERME', 7],
            'LUFTWP' => ['LUFTWP', 13],
            'FERN' => ['FERN', 1],
            'BLOCK' => ['BLOCK', 12],
            'WASSER-ELEKTRO' => ['WASSER-ELEKTRO', 14],
            'PELLET' => ['PELLET', 15],
            'KOHLE' => ['KOHLE', 16],
            'HOLZ' => ['HOLZ', 17],
            'FLUESSIGGAS' => ['FLUESSIGGAS', 18],
        ];
    }

    /**
     * @test
     *
     * @param string $name
     * @param int $id
     *
     * @dataProvider firingTypeDataProvider
     */
    public function getConvertedDataImportsAllFiringTypes($name, $id)
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>
                <anbieter>
                    <immobilie>
                        <ausstattung>
                            <befeuerung ' . $name . '="true"/>
                        </ausstattung>
                    </immobilie>
                </anbieter>
             </openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $id,
            $result[0]['heating_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesSwitchboardPhoneNumber()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<kontaktperson>'
            . '<tel_zentrale>1234567</tel_zentrale>'
            . '</kontaktperson>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '1234567',
            $result[0]['phone_switchboard']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesDirectExtensionPhoneNumber()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<kontaktperson>'
            . '<tel_durchw>1234567</tel_durchw>'
            . '</kontaktperson>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '1234567',
            $result[0]['phone_direct_extension']
        );
    }

    /**
     * @test
     *
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=4057
     */
    public function getConvertedDataCanImportLivingUsageUsingTrueFalseAttributeValues()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="true" GEWERBE="false"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            'Wohnen',
            $result[0]['utilization']
        );
    }

    /**
     * @test
     *
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=4057
     */
    public function getConvertedDataCanImportLivingUsageUsingOneZeroAttributeValues()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1" GEWERBE="0"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            'Wohnen',
            $result[0]['utilization']
        );
    }

    /**
     * @test
     *
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
     */
    public function getConvertedDataCanImportCommercialUsage()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="false" GEWERBE="true"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            'Gewerbe',
            $result[0]['utilization']
        );
    }

    /**
     * @test
     *
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
     */
    public function getConvertedDataCanImportLivingAndCommercialUsage()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="true" GEWERBE="true"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            'Wohnen, Gewerbe',
            $result[0]['utilization']
        );
    }

    /**
     * @test
     *
     * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
     */
    public function getConvertedDataCanImportEmptyUsage()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="false" GEWERBE="false"/>' .
            '</objektkategorie>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['utilization'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataGetsFurnishingCategoryForStandardCategoryProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<ausstatt_kategorie>STANDARD</ausstatt_kategorie>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            1,
            $result[0]['furnishing_category']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataGetsFurnishingCategoryForUpmarketCategoryProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<ausstatt_kategorie>GEHOBEN</ausstatt_kategorie>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            2,
            $result[0]['furnishing_category']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataGetsFurnishingCategoryForLuxuryCategoryProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<ausstatt_kategorie>LUXUS</ausstatt_kategorie>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            3,
            $result[0]['furnishing_category']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotGetsFurnishingCategoryIfInvalidCategoryProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<ausstatt_kategorie>FOO</ausstatt_kategorie>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['furnishing_category'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetOneValidFlooringType()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<boden FLIESEN="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '1',
            $result[0]['flooring']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataCanGetMultipleValidFlooringTypesFromFlooringNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<boden STEIN="true" TEPPICH="true" PARKETT="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '2,3,4',
            $result[0]['flooring']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotGetsInvalidFlooringFromFlooringNode()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<boden RAUHFAHSER="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['flooring'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataOnlyGetsValidFlooringsIfValidAndInvalidFlooringsProvided()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<boden FERTIGPARKETT="true" LAMINAT="true" FENSTER="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            '5,6',
            $result[0]['flooring']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForValidButFalseFlooringDoesNotImportThisFlooring()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<boden FERTIGPARKETT="false" LINOLEUM="true" />' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );
        $this->subject->setRawRealtyData($node);

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            '11',
            $result[0]['flooring']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentedTrueAsStatusRented()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<verwaltung_objekt>' .
            '<vermietet>TRUE</vermietet>' .
            '</verwaltung_objekt>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::STATUS_RENTED,
            $result[0]['status']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentedFalseAsStatusVacant()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<verwaltung_objekt>' .
            '<vermietet>FALSE</vermietet>' .
            '</verwaltung_objekt>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::STATUS_VACANT,
            $result[0]['status']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForRentedMissingNotSetsStatus()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<verwaltung_objekt>' .
            '</verwaltung_objekt>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['status'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesHotWaterTrue()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<mitwarmwasser>true</mitwarmwasser>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(1, $result[0]['with_hot_water']);
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesHotWaterFalse()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<mitwarmwasser>false</mitwarmwasser>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(0, $result[0]['with_hot_water']);
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesHotWaterMissing()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['with_hot_water'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateValidUntil()
    {
        $value = '11/2027';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<gueltig_bis>' . $value . '</gueltig_bis>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['energy_certificate_valid_until']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyConsumptionCharacteristic()
    {
        $value = 'ABC';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<energieverbrauchkennwert>' . $value . '</energieverbrauchkennwert>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['energy_consumption_characteristic']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesUltimateEnergyDemand()
    {
        $value = '24,2154 kwH';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<endenergiebedarf>' . $value . '</endenergiebedarf>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['ultimate_energy_demand']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesPrimaryEnergyCarrier()
    {
        $value = 'GAS';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<primaerenergietraeger>' . $value . '</primaerenergietraeger>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['primary_energy_carrier']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesElectricPowerConsumptionCharacteristic()
    {
        $value = 'C42-abc';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<stromwert>' . $value . '</stromwert>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['electric_power_consumption_characteristic']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesHeatEnergyConsumptionCharacteristic()
    {
        $value = 'X42-abc';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<waermewert>' . $value . '</waermewert>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['heat_energy_consumption_characteristic']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesValueCategory()
    {
        $value = 'C 44 C12';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<wertklasse>' . $value . '</wertklasse>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['value_category']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesYearOfConstruction()
    {
        $value = '1963';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<baujahr>' . $value . '</baujahr>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            (int)$value,
            $result[0]['year_of_construction']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateText()
    {
        $value = 'My, this is a nice certificate!';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<epasstext>' . $value . '</epasstext>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['energy_certificate_text']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesHeatEnergyRequirementValue()
    {
        $value = '123 a 45';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<hwbwert>' . $value . '</hwbwert>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['heat_energy_requirement_value']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesHeatEnergyRequirementClass()
    {
        $value = '123 a 45';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<hwbklasse>' . $value . '</hwbklasse>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['heat_energy_requirement_class']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesTotalEnergyEfficiencyValue()
    {
        $value = '123 a 45';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<fgeewert>' . $value . '</fgeewert>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['total_energy_efficiency_value']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesTotalEnergyEfficiencyClass()
    {
        $value = '123 a 45';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<fgeeklasse>' . $value . '</fgeeklasse>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            $value,
            $result[0]['total_energy_efficiency_class']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateIssueDate()
    {
        $value = '2014-02-20';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<ausstelldatum>' . $value . '</ausstelldatum>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            mktime(0, 0, 0, 2, 20, 2014),
            $result[0]['energy_certificate_issue_date']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataSetsMissingEnergyCertificateIssueDateToZero()
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            0,
            $result[0]['energy_certificate_issue_date']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateTypeRequirement()
    {
        $value = 'BEDARF';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<epart>' . $value . '</epart>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_REQUIREMENT,
            $result[0]['energy_certificate_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateTypeConsumption()
    {
        $value = 'VERBRAUCH';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<epart>' . $value . '</epart>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_CONSUMPTION,
            $result[0]['energy_certificate_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataIgnoresInvalidEnergyCertificateType()
    {
        $value = 'Kürbisbrot';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<epart>' . $value . '</epart>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['energy_certificate_type'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateYear2008()
    {
        $value = '2008';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<jahrgang>' . $value . '</jahrgang>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2008,
            $result[0]['energy_certificate_year']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateYear2014()
    {
        $value = '2014';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<jahrgang>' . $value . '</jahrgang>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2014,
            $result[0]['energy_certificate_year']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateYearNotAvailable()
    {
        $value = 'ohne';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<jahrgang>' . $value . '</jahrgang>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_AVAILABLE,
            $result[0]['energy_certificate_year']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesEnergyCertificateYearNotRequired()
    {
        $value = 'nicht_noetig';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<jahrgang>' . $value . '</jahrgang>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_REQUIRED,
            $result[0]['energy_certificate_year']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataIgnoresInvalidEnergyCertificateYear()
    {
        $value = 'Kürbisbrot';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<jahrgang>' . $value . '</jahrgang>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['energy_certificate_year'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesBuildingTypeResidential()
    {
        $value = 'wohn';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<gebaeudeart>' . $value . '</gebaeudeart>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::BUILDING_TYPE_RESIDENTIAL,
            $result[0]['building_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataFetchesBuildingTypeBusiness()
    {
        $value = 'nichtwohn';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<gebaeudeart>' . $value . '</gebaeudeart>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            \tx_realty_Model_RealtyObject::BUILDING_TYPE_BUSINESS,
            $result[0]['building_type']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataIgnoresInvalidBuildingType()
    {
        $value = 'Kürbisbrot';

        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<energiepass>' .
            '<gebaeudeart>' . $value . '</gebaeudeart>' .
            '</energiepass>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['building_type'])
        );
    }

    /*
     * Tests concerning the attachments
     */

    /**
     * @test
     */
    public function getConvertedDataForNoAttachmentsNodeNotSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie/>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertArrayNotHasKey('attached_files', $realtyObjectData);
    }

    /**
     * @test
     */
    public function getConvertedDataForEmptyAttachmentsNodeNotSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<anhaenge>' .
            '</anhaenge>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertArrayNotHasKey('attached_files', $realtyObjectData);
    }

    /**
     * @test
     */
    public function getConvertedDataForEmptySingleAttachmentNodeWithoutPathNotSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<anhaenge>' .
            '<anhang>' .
            '</anhang>' .
            '</anhaenge>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertArrayNotHasKey('attached_files', $realtyObjectData);
    }

    /**
     * @test
     */
    public function getConvertedDataForAttachmentNodeWithoutPathNotSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<anhaenge>' .
            '<anhang>' .
            '<anhangtitel>bar</anhangtitel>' .
            '</anhang>' .
            '</anhaenge>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertArrayNotHasKey('attached_files', $realtyObjectData);
    }

    /**
     * @test
     */
    public function getConvertedDataForFullyFilledAttachmentNodeSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<anhaenge>' .
            '<anhang>' .
            '<anhangtitel>bar</anhangtitel>' .
            '<daten>' .
            '<pfad>tx_realty_image_test.jpg</pfad>' .
            '</daten>' .
            '</anhang>' .
            '</anhaenge>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertSame(
            [['title' => 'bar', 'path' => 'tx_realty_image_test.jpg']],
            $realtyObjectData['attached_files']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForAttachmentWithPathOnlyNodeSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<anhaenge>' .
            '<anhang>' .
            '<daten>' .
            '<pfad>tx_realty_image_test.jpg</pfad>' .
            '</daten>' .
            '</anhang>' .
            '</anhaenge>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertSame([['title' => '', 'path' => 'tx_realty_image_test.jpg']], $realtyObjectData['attached_files']);
    }

    /**
     * @test
     */
    public function getConvertedDataForMultipleAttachmentsSetsAttachments()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<anhaenge>' .
            '<anhang>' .
            '<daten>' .
            '<pfad>tx_realty_image_test.jpg</pfad>' .
            '</daten>' .
            '</anhang>' .
            '<anhang>' .
            '<daten>' .
            '<pfad>tx_realty_image_test2.jpg</pfad>' .
            '</daten>' .
            '</anhang>' .
            '</anhaenge>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        $realtyObjectData = $result[0];

        self::assertCount(2, $realtyObjectData['attached_files']);
    }

    //////////////////////////////////////
    // Tests concerning getConvertedData
    //////////////////////////////////////

    /**
     * @test
     */
    public function getConvertedDataImportsAttributeValuesCorrectly()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<ausstattung>'
            . '<fahrstuhl PERSONEN="false"/>'
            . '<kueche EBK="true"/>'
            . '</ausstattung>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEmpty($result[0]['elevator'], 'The value for "elevator" is incorrect.');
        self::assertSame(
            1,
            $result[0]['fitted_kitchen'],
            'The value for "fitted_kitchen" is incorrect.'
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheHoaFee()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<hausgeld>12345</hausgeld>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            12345.00,
            $result[0]['hoa_fee']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentExcludingBillsFromNettokaltmiete()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<nettokaltmiete>12345</nettokaltmiete>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            12345.00,
            $result[0]['rent_excluding_bills']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentExcludingBillsFromNettokaltmieteWhenKaltmieteIsPresent()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<nettokaltmiete>12345</nettokaltmiete>' .
            '<kaltmiete>54321</kaltmiete>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            12345.00,
            $result[0]['rent_excluding_bills']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForNettokaltmieteMissingAndExistingKaltmieteImportsRentExcludingBillsFromKaltmiete()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<kaltmiete>54321</kaltmiete>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            54321.00,
            $result[0]['rent_excluding_bills']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataForNettokaltmieteEmptyAndNonEmptyKaltmieteImportsRentExcludingBillsFromKaltmiete()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<nettokaltmiete></nettokaltmiete>' .
            '<kaltmiete>54321</kaltmiete>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            54321.00,
            $result[0]['rent_excluding_bills']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentWithHeatingCostsFromWarmmiete()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<warmmiete>12345.67</warmmiete>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(12345.67, $result[0]['rent_with_heating_costs']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheLanguage()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<verwaltung_objekt>'
            . '<user_defined_anyfield>'
            . '<sprache>foo</sprache>'
            . '</user_defined_anyfield>'
            . '</verwaltung_objekt>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            'foo',
            $result[0]['language']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsLongitudeAndLatitudeAndSetsFlagIfBothAreProvided()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<geokoordinaten laengengrad="1.23" breitengrad="4.56"/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertTrue(
            $result[0]['has_coordinates']
        );
        self::assertFalse(
            $result[0]['coordinates_problem']
        );
        self::assertSame(
            1.23,
            $result[0]['longitude']
        );
        self::assertSame(
            4.56,
            $result[0]['latitude']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotImportsTheCoordinatesIfOnlyOneIsProvided()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<geokoordinaten laengengrad="1.23"/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['has_coordinates'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataNotImportsTheCoordinatesIfOneIsNonEmptyAndOneIsEmpty()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<geo>' .
            '<geokoordinaten laengengrad="1.23" breitengrad=""/>' .
            '</geo>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['has_coordinates'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheCurrency()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<waehrung iso_waehrung="EUR"/>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            'EUR',
            $result[0]['currency']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheValueForNewBuilding()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<zustand_angaben>'
            . '<alter ALTER_ATTR="neubau" />'
            . '</zustand_angaben>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            1,
            $result[0]['old_or_new_building']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheValueForOldBuilding()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<zustand_angaben>'
            . '<alter ALTER_ATTR="altbau" />'
            . '</zustand_angaben>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            2,
            $result[0]['old_or_new_building']
        );
    }

    /**
     * @test
     */
    public function convertedDataDoesNotContainTheKeyOldOrNewBuildingIfNoValueWasSet()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<zustand_angaben>'
            . '<alter ALTER_ATTR="" />'
            . '</zustand_angaben>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertFalse(
            isset($result[0]['old_or_new_building'])
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheValueForShowAddressIfThisIsEnabled()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<verwaltung_objekt>' .
            '<objektadresse_freigeben>1</objektadresse_freigeben>' .
            '</verwaltung_objekt>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(1, $result[0]['show_address']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTheValueForShowAddressIfThisIsDisabled()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<verwaltung_objekt>' .
            '<objektadresse_freigeben>0</objektadresse_freigeben>' .
            '</verwaltung_objekt>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(0, $result[0]['show_address']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentPerSquareMeter()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<mietpreis_pro_qm>12.34</mietpreis_pro_qm>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            12.34,
            $result[0]['rent_per_square_meter']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsLivingArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<wohnflaeche>123.45</wohnflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['living_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTotalUsableArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<nutzflaeche>123.45</nutzflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['total_usable_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsTotalArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<gesamtflaeche>123.45</gesamtflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['total_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsShopArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<ladenflaeche>123.45</ladenflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['shop_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsSalesArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<verkaufsflaeche>123.45</verkaufsflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            123.45,
            $result[0]['sales_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsStorageArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<lagerflaeche>123.45</lagerflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['storage_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsOfficeSpace()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<bueroflaeche>123.45</bueroflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['office_space']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsOtherArea()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<sonstflaeche>123.45</sonstflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            123.45,
            $result[0]['other_area']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsWindowBank()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<fensterfront>12.34</fensterfront>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            12.34,
            $result[0]['window_bank']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsFloorSpaceIndex()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<grz>0.12</grz>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            0.12,
            $result[0]['floor_space_index']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsSiteOccupancyIndex()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<gfz>0.12</gfz>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            0.12,
            $result[0]['site_occupancy_index']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsEstateSize()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<grundstuecksflaeche>123.45</grundstuecksflaeche>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertEquals(
            123.45,
            $result[0]['estate_size']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsNumberOfRooms()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_zimmer>3.5</anzahl_zimmer>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertEquals(
            3.5,
            $importedData[0]['number_of_rooms']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsNumberOfBedrooms()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_schlafzimmer>2</anzahl_schlafzimmer>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            2,
            $importedData[0]['bedrooms']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsNumberOfBathrooms()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_badezimmer>2</anzahl_badezimmer>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            2,
            $importedData[0]['bathrooms']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsNumberOfBalconiesFromBalconiesAndPatios()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_balkon_terrassen>1</anzahl_balkon_terrassen>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);

        self::assertSame(1, $importedData[0]['balcony']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsNumberOfBalconies()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_balkone>1</anzahl_balkone>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(1, $importedData[0]['balcony']);
    }

    /**
     * @test
     */
    public function getConvertedDataRecognizesNoBalconiesAndNoPatios()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_balkon_terrassen>0</anzahl_balkon_terrassen>' .
            '<anzahl_balkone>0</anzahl_balkone>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertNull($importedData[0]['balcony']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsNumberOfParkingSpaces()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<flaechen>' .
            '<anzahl_stellplaetze>2</anzahl_stellplaetze>' .
            '</flaechen>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $importedData = $this->subject->getConvertedData($node);
        self::assertSame(
            2,
            $importedData[0]['parking_spaces']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsRentalIncomeTarget()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<mieteinnahmen_soll>12345.67</mieteinnahmen_soll>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(
            12345.67,
            $result[0]['rental_income_target']
        );
    }

    /**
     * @test
     */
    public function getConvertedDataImportsDepositAsFloat()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<kaution>1234.56</kaution>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);

        self::assertSame('1234.56', $result[0]['deposit']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsDepositAsInt()
    {
        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<kaution>1234</kaution>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);

        self::assertSame(1234, $result[0]['deposit']);
    }

    /**
     * @test
     */
    public function getConvertedDataImportsDepositAsText()
    {
        $deposit = 'one rent';

        $node = $this->setRawDataToConvert(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<preise>' .
            '<kaution_text>' . $deposit . '</kaution_text>' .
            '</preise>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);

        self::assertSame($deposit, $result[0]['deposit']);
    }

    /**
     * @return string[][]
     */
    public function booleanInfrastructureDataProvider()
    {
        return [
            'barrier_free' => ['barrierefrei', 'barrier_free'],
            'wheelchair_accessible' => ['rollstuhlgerecht', 'wheelchair_accessible'],
            'ramp' => ['rampe', 'ramp'],
            'lifting_platform' => ['hebebuehne', 'lifting_platform'],
            'suitable_for_the_elderly' => ['seniorengerecht', 'suitable_for_the_elderly'],
        ];
    }

    /**
     * @test
     *
     * @param string $nodeName
     * @param string $fieldName
     *
     * @dataProvider booleanInfrastructureDataProvider
     */
    public function getConvertedDataFetchesBooleanInfrastructureNodeTrue($nodeName, $fieldName)
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<' . $nodeName . '>true</' . $nodeName . '>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(1, $result[0][$fieldName]);
    }

    /**
     * @test
     *
     * @param string $nodeName
     * @param string $fieldName
     *
     * @dataProvider booleanInfrastructureDataProvider
     */
    public function getConvertedDataFetchesBooleanInfrastructureNodeFalse($nodeName, $fieldName)
    {
        $node = new \DOMDocument();
        $node->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<ausstattung>' .
            '<' . $nodeName . '>false</' . $nodeName . '>' .
            '</ausstattung>' .
            '</immobilie>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $result = $this->subject->getConvertedData($node);
        self::assertSame(0, $result[0][$fieldName]);
    }

    ////////////////////////////////////////
    // Tests concerning fetchDomAttributes
    ////////////////////////////////////////

    /**
     * @test
     */
    public function fetchDomAttributesIfValidNodeGiven()
    {
        $node = new \DOMDocument();
        /** @var \DOMElement $element */
        $element = $node->appendChild($node->createElement('foo'));
        $element->setAttributeNode(new \DOMAttr('foo', 'bar'));

        self::assertSame(
            ['foo' => 'bar'],
            $this->subject->fetchDomAttributes($element)
        );
    }

    /**
     * @test
     */
    public function fetchDomAttributesIfNodeWithoutAttributesGiven()
    {
        $node = new \DOMDocument();
        $element = $node->appendChild($node->createElement('foo'));

        self::assertSame(
            [],
            $this->subject->fetchDomAttributes($element)
        );
    }
}
