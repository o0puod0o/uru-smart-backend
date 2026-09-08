<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InfoAdminSummaryService
{
    /**
     * Return aggregate-only data for the Admin Web dashboard.
     * The browser never receives the Info service credential.
     */
    public function summary(): array
    {
        return Cache::remember('admin-web.info-summary.v1', now()->addSeconds(45), function (): array {
            $baseUrl = rtrim((string) config('services.info_admin.base_url'), '/');
            $serviceKey = $this->serviceKey();
            $caBundle = storage_path('app/info-uru-ca.pem');

            if ($baseUrl === '' || $serviceKey === '' || ! is_file($caBundle)) {
                return [
                    'available' => false,
                    'message' => 'Info service bridge is not configured.',
                ];
            }

            try {
                $response = Http::acceptJson()
                    ->withOptions(['verify' => $caBundle])
                    ->timeout((int) config('services.info_admin.timeout', 7))
                    ->withHeaders(['X-URU-Admin-Service-Key' => $serviceKey])
                    ->get($baseUrl.'/info/admin/summary');

                if (! $response->successful() || ! is_array($response->json('data'))) {
                    return [
                        'available' => false,
                        'message' => 'Info service is temporarily unavailable.',
                    ];
                }

                return [
                    'available' => true,
                    'data' => $response->json('data'),
                ];
            } catch (\Throwable) {
                return [
                    'available' => false,
                    'message' => 'Info service is temporarily unavailable.',
                ];
            }
        });
    }

    private function serviceKey(): string
    {
        $configured = trim((string) config('services.info_admin.service_key'));

        if ($configured !== '') {
            return $configured;
        }

        $path = storage_path('app/info-admin-service.key');

        return is_file($path) ? trim((string) @file_get_contents($path)) : '';
    }
}
