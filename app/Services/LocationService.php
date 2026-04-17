<?php

namespace App\Services;

class LocationService
{
    /**
     * Calculate distance between two coordinates in meters (Haversine Formula)
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a coordinate is within a certain radius of another
     */
    public function isWithinRadius($lat1, $lon1, $lat2, $lon2, $radius): bool
    {
        return $this->calculateDistance($lat1, $lon1, $lat2, $lon2) <= $radius;
    }
}
