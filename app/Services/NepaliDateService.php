<?php

namespace App\Services;

use App\Models\Company;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Nilambar\NepaliDate\NepaliDate;

class NepaliDateService
{
    public const MIN_SUPPORTED_AD_DATE = '1944-01-01';

    public const MAX_SUPPORTED_AD_DATE = '2033-04-13';

    public function __construct(private readonly NepaliDate $converter = new NepaliDate)
    {
    }

    public function adToBs(string|DateTimeInterface $adDate): string
    {
        $date = $this->normalizeAdDate($adDate);
        $dateString = $date->format('Y-m-d');

        if ($dateString < self::MIN_SUPPORTED_AD_DATE || $dateString > self::MAX_SUPPORTED_AD_DATE) {
            throw new InvalidArgumentException(sprintf(
                'AD date must be between %s and %s.',
                self::MIN_SUPPORTED_AD_DATE,
                self::MAX_SUPPORTED_AD_DATE,
            ));
        }

        $converted = $this->converter->convertAdToBs(
            (int) $date->format('Y'),
            (int) $date->format('m'),
            (int) $date->format('d'),
        );

        if (! isset($converted['year'], $converted['month'], $converted['day'])) {
            throw new InvalidArgumentException('The AD date could not be converted to a supported BS date.');
        }

        return sprintf(
            '%04d-%02d-%02d',
            $converted['year'],
            $converted['month'],
            $converted['day'],
        );
    }

    public function adToBsForCompany(Company $company, string|DateTimeInterface $adDate): ?string
    {
        if ($company->countryMaster?->iso_code !== 'NP') {
            return null;
        }

        return $this->adToBs($adDate);
    }

    /** Format the centralized AD-to-BS result for the IRD dotted date field. */
    public function adToIrdBs(string|DateTimeInterface $adDate): string
    {
        return str_replace('-', '.', $this->adToBs($adDate));
    }

    private function normalizeAdDate(string|DateTimeInterface $adDate): DateTimeImmutable
    {
        if ($adDate instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($adDate);
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $adDate);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $adDate) {
            throw new InvalidArgumentException('AD date must be a valid date in YYYY-MM-DD format.');
        }

        return $date;
    }
}
