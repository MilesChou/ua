<?php

namespace App\Commands;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;

class FetchCommand extends Command
{
    protected $signature = 'fetch {ua*}';

    protected $description = 'Fetch UA data';

    public function handle()
    {
        $endpoint = config('services.userstack.endpoint');
        $token = config('services.userstack.access_key');

        $ua = $this->argument('ua');

        $collection = collect($ua)
            ->flip()
            ->map(fn(string $_, $ua) => $endpoint . '?' . Arr::query([
                    'access_key' => $token,
                    'ua' => $ua,
                    'output' => 'json',
                ]))
            ->map(function (string $url, string $ua) {
                if (File::exists('docs/' . sha1($ua) . '.json')) {
                    $this->info('Cache ' . $url, 'vv');

                    return json_decode(File::get('docs/' . sha1($ua) . '.json'), true);
                }
                $this->info('GET ' . $url, 'vv');

                $json = Http::get($url)->json();

                if (isset($json['success']) && false === $json['success']) {
                    throw new \RuntimeException('API return error: ' . $json['error']['info'] ?? 'Unknown error');
                }

                // Save cache
                File::put('docs/' . sha1($ua) . '.json', json_encode($json));

                return $json;
            });

        $collection = $collection->map(fn(array $json) => [
            'type' => $json['type'],
            'name' => $json['name'],
            'os' => $json['os']['name'],
            'browser' => $json['browser']['name'],
            'browser_version' => $json['browser']['version_major'],
            'browser_engine' => $json['browser']['engine'],
        ]);

        $this->table([
            'type',
            'name',
            'os',
            'browser',
            'browser_version',
            'browser_engine',
        ], $collection->toArray());

        return 0;
    }
}
