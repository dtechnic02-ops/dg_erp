<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Country;
use App\Services\NepaliDateService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NepaliDateServiceTest extends TestCase
{
    private NepaliDateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NepaliDateService;
    }

    #[DataProvider('knownDates')]
    public function test_it_converts_known_ad_dates_deterministically(string $ad, string $bs): void
    {
        $this->assertSame($bs, $this->service->adToBs($ad));
        $this->assertSame($bs, $this->service->adToBs($ad));
    }

    public static function knownDates(): array
    {
        return [
            ['1944-01-01', '2000-09-17'],
            ['2020-01-01', '2076-09-16'],
            ['2024-04-13', '2081-01-01'],
            ['2026-08-14', '2083-04-29'],
            ['2033-04-13', '2089-12-30'],
        ];
    }

    public function test_it_returns_bs_for_a_nepal_company(): void
    {
        $company = $this->companyIn('NP');

        $this->assertSame('2081-01-01', $this->service->adToBsForCompany($company, '2024-04-13'));
    }

    public function test_it_is_not_applicable_to_a_non_nepal_company(): void
    {
        $company = $this->companyIn('IN');

        $this->assertNull($this->service->adToBsForCompany($company, '2024-04-13'));
    }

    public function test_it_rejects_an_invalid_ad_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('valid date in YYYY-MM-DD format');

        $this->service->adToBs('2024-02-30');
    }

    #[DataProvider('unsupportedDates')]
    public function test_it_rejects_dates_outside_the_supported_range(string $date): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AD date must be between 1944-01-01 and 2033-04-13');

        $this->service->adToBs($date);
    }

    public static function unsupportedDates(): array
    {
        return [
            ['1943-12-31'],
            ['2033-04-14'],
        ];
    }

    public function test_conversion_does_not_mutate_the_authoritative_ad_value(): void
    {
        $ad = new DateTimeImmutable('2024-04-13 15:45:30');
        $before = $ad->format('Y-m-d H:i:s');

        $this->assertSame('2081-01-01', $this->service->adToBs($ad));
        $this->assertSame($before, $ad->format('Y-m-d H:i:s'));
    }

    public function test_company_country_eligibility_is_isolated_per_company(): void
    {
        $nepalCompany = $this->companyIn('NP');
        $otherCompany = $this->companyIn('US');

        $this->assertSame('2081-01-01', $this->service->adToBsForCompany($nepalCompany, '2024-04-13'));
        $this->assertNull($this->service->adToBsForCompany($otherCompany, '2024-04-13'));
        $this->assertSame('NP', $nepalCompany->countryMaster->iso_code);
        $this->assertSame('US', $otherCompany->countryMaster->iso_code);
        $this->assertFalse($nepalCompany->exists);
        $this->assertFalse($otherCompany->exists);
    }

    private function companyIn(string $isoCode): Company
    {
        $company = new Company;
        $company->setRelation('countryMaster', new Country([
            'name' => $isoCode === 'NP' ? 'Nepal' : 'Other',
            'iso_code' => $isoCode,
            'is_active' => true,
        ]));

        return $company;
    }
}
