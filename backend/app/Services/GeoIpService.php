<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    protected ?Reader $reader = null;

    public function __construct()
    {
        $path = config('services.geoip.database_path', storage_path('geoip/GeoLite2-City.mmdb'));

        if (file_exists($path)) {
            try {
                $this->reader = new Reader($path);
            } catch (\Exception $e) {
                Log::warning('GeoIP database could not be loaded: ' . $e->getMessage());
            }
        }
    }

    /**
     * Lookup IP address and return location data.
     */
    public function lookup(string $ip): array
    {
        if (!$this->reader) {
            return $this->getDefaultResponse();
        }

        // Skip private/local IPs
        if ($this->isPrivateIp($ip)) {
            return $this->getDefaultResponse();
        }

        try {
            $record = $this->reader->city($ip);

            return [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
                'region' => $record->mostSpecificSubdivision->name,
                'city' => $record->city->name,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'timezone' => $record->location->timeZone,
            ];
        } catch (AddressNotFoundException $e) {
            return $this->getDefaultResponse();
        } catch (\Exception $e) {
            Log::warning('GeoIP lookup failed: ' . $e->getMessage());
            return $this->getDefaultResponse();
        }
    }

    /**
     * Check if IP is private/local.
     */
    protected function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Get default response when lookup fails.
     */
    protected function getDefaultResponse(): array
    {
        return [
            'country_code' => null,
            'country_name' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
        ];
    }
}
