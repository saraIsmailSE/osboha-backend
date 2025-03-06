<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class CertificateServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (env('CURL_CA_BUNDLE') && file_exists(env('CURL_CA_BUNDLE'))) {
            putenv('SSL_CERT_FILE=' . env('CURL_CA_BUNDLE'));
        }
    }

    public static function getHttpClient()
    {
        return new Client([
            RequestOptions::VERIFY => env('CURL_CA_BUNDLE') ?: true,
        ]);
    }
}
