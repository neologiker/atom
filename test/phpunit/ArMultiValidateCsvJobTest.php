<?php

/**
 * @internal
 *
 * @covers \arMultiValidateCsvJob
 * @covers \multiCsvValidateIndexAction
 */
class ArMultiValidateCsvJobTest extends \PHPUnit\Framework\TestCase
{
    public function testEveryUploadableTypeMapsToAValidatorClass()
    {
        foreach (multiCsvValidateIndexAction::OBJECT_TYPES as $type) {
            $options = arMultiValidateCsvJob::mapImportTypeToClassName(
                arMultiValidateCsvJob::setOptions(['objectType' => $type])
            );

            $this->assertNotEmpty($options['className'], $type);
        }
    }

    public function testOptionsMapObjectTypeToClassName()
    {
        $options = arMultiValidateCsvJob::mapImportTypeToClassName(
            arMultiValidateCsvJob::setOptions(['objectType' => 'accession'])
        );

        $this->assertSame('QubitAccession', $options['className']);
    }
}
