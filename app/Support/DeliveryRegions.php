<?php

namespace App\Support;

final class DeliveryRegions
{
    public static function forCountry(string $country): array
    {
        return strtoupper($country) === 'PK' ? [
            'Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan',
            'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Jammu and Kashmir',
        ] : [];
    }
}
