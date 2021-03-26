<?php

namespace Northwestern\SysDev\DynamicForms\Tests\Components\Inputs;

use Northwestern\SysDev\DynamicForms\Components\Inputs\Day;
use Northwestern\SysDev\DynamicForms\Tests\Components\InputComponentTestCase;

/**
 * @coversDefaultClass \Northwestern\SysDev\DynamicForms\Components\Inputs\Day
 */
class DayTest extends InputComponentTestCase
{
    protected string $componentClass = Day::class;

    /**
     * @dataProvider getDatePartsDataProvider
     * @covers ::getDateParts
     */
    public function testGetDateParts(string $value, array $expected): void
    {
        $reflection = new \ReflectionClass(Day::class);
        $method = $reflection->getMethod('getDateParts');
        $method->setAccessible(true);

        $this->assertEquals(
            $expected,
            $method->invokeArgs($this->getDay('', []), [$value])
        );
    }

    public function getDatePartsDataProvider(): array
    {
        $default = ['year' => null, 'month' => null, 'day' => null];

        return [
            'empty' => ['', $default],
            'blank date' => ['00/00/0000', $default],
            'full date' => ['02/01/2020', ['year' => 2020, 'month' => 2, 'day' => 1]],
            'month' => ['02/00/0000', array_merge($default, ['month' => 2])],
            'day' => ['00/02/0000', array_merge($default, ['day' => 2])],
            'year' => ['00/00/2020', array_merge($default, ['year' => 2020])],
        ];
    }

    /**
     * Overwriting the parent method so we can pass validations to a different spot.
     *
     * @see getDay
     *
     * @dataProvider validationsProvider
     * @covers ::processValidations
     * @covers ::validate
     */
    public function testValidations(
        array $validations,
        mixed $submissionValue,
        bool $passes,
        ?string $message = null,
        array $additional = []
    ): void {
        $component = $this->getDay($submissionValue, $validations);

        $bag = $component->validate($component->key(), app()->make('validator'));
        $this->assertEquals($passes, $bag->isEmpty(), $bag);
    }

    public function validationsProvider(): array
    {
        $allRequired = [
            'year' => ['required' => true],
            'month' => ['required' => true],
            'day' => ['required' => true],
        ];

        return [
            'passes with empty data' => [[], '', true],
            'passes with day required' => [['day' => ['required' => true]], '01/01/2021', true],
            'fails with day required' => [['day' => ['required' => true]], '01/00/2021', false],
            'passes with month required' => [['month' => ['required' => true]], '01/01/2021', true],
            'fails with month required' => [['month' => ['required' => true]], '00/01/2021', false],
            'passes with year required' => [['year' => ['required' => true]], '01/01/2021', true],
            'fails with year required' => [['year' => ['required' => true]], '00/01/0000', false],
            'passes with all required' => [$allRequired, '01/01/2021', true],
            'fails with all required' => [$allRequired, '00/00/0000', false],
            'passes with min/max years blank' => [['year' => ['minYear' => 2020, 'maxYear' => 2021]], '', true],
            'passes with minimum year' => [['year' => ['minYear' => 2020, 'maxYear' => 2021]], '00/00/2021', true],
            'fails with minimum year too low' => [['year' => ['minYear' => 2020, 'maxYear' => 2021]], '00/00/2019', false],
            'fails with minimum year too high' => [['year' => ['minYear' => 2020, 'maxYear' => 2021]], '00/00/2022', false],
            'passes with minimum date blank' => [['minDate' => '2020-01-01', 'maxDate' => '2020-12-31'], '', true],
            'passes with minimum date' => [['minDate' => '2020-01-01', 'maxDate' => '2020-12-31'], '02/02/2020', true],
            'fails with minimum date too low' => [['minDate' => '2020-01-01', 'maxDate' => '2020-12-31'], '02/02/2019', false],
            'fails with minimum date too high' => [['minDate' => '2020-01-01', 'maxDate' => '2020-12-31'], '02/02/2022', false],
        ];
    }

    private function getDay(string $submissionValue, array $additional): Day
    {
        /**
         * This component does not use the 'validations' key like normal components.
         * This is why what is normally the validations data is being passed to a
         * different key.
         */
        return $this->getComponent(
            submissionValue: $submissionValue,
            additional: $additional,
        );
    }
}
