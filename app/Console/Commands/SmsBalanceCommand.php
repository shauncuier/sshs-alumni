<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Communication\Sms\BulkSmsBdChannel;
use App\Services\Communication\SmsManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Reports the SMS account balance and the effective configuration.
 *
 * Read-only: it sends no message and costs nothing.
 */
class SmsBalanceCommand extends Command
{
    protected $signature = 'sms:balance {--live : Query the real gateway even when the log driver is configured}';

    protected $description = 'Show the SMS gateway balance and effective configuration';

    public function handle(SmsManager $manager): int
    {
        /** @var array<string, mixed> $config */
        $config = config('sms.drivers.bulksmsbd');

        $this->components->twoColumnDetail('Driver', (string) config('sms.driver'));
        $this->components->twoColumnDetail('Enabled', config('sms.enabled') ? '<fg=green>yes</>' : '<fg=yellow>no</>');
        $this->components->twoColumnDetail('Endpoint', (string) $config['base_url']);
        $this->components->twoColumnDetail(
            'API key',
            filled($config['api_key'])
                ? '<fg=green>set</> ('.strlen((string) $config['api_key']).' chars)'
                : '<fg=red>NOT SET</>',
        );
        $this->components->twoColumnDetail(
            'Sender ID',
            filled($config['sender_id'])
                ? (string) $config['sender_id']
                : '<fg=red>NOT SET — every send returns 1002</>',
        );
        $this->components->twoColumnDetail('Daily cap (segments)', (string) config('sms.daily_cap'));
        $this->components->twoColumnDetail('Used today', (string) $manager->sentToday());
        $this->components->twoColumnDetail('Remaining today', (string) $manager->remainingToday());

        $this->newLine();

        if (blank($config['api_key'])) {
            $this->components->error('No API key configured. Set BULKSMSBD_API_KEY in .env.');

            return self::FAILURE;
        }

        $channel = config('sms.driver') === 'bulksmsbd' || $this->option('live')
            ? new BulkSmsBdChannel($config)
            : null;

        if ($channel === null) {
            $this->components->info('Log driver is active, so no balance was requested. Pass --live to query the gateway.');

            return self::SUCCESS;
        }

        $this->components->task('Querying gateway balance', function () use ($channel): bool {
            $balance = $channel->balance();

            $this->newLine();

            if ($balance === null) {
                $this->components->warn('The gateway did not return a usable balance.');

                return false;
            }

            $this->components->twoColumnDetail('<fg=green>Balance</>', (string) $balance);

            return true;
        });

        $this->newLine();
        $this->checkTransportSecurity((string) $config['base_url'], (string) $config['api_key']);

        return self::SUCCESS;
    }

    /**
     * The vendor documents a plain-HTTP endpoint, which means the API key
     * travels unencrypted. If the host also answers on HTTPS, say so — it is a
     * one-line .env change for a real improvement.
     */
    private function checkTransportSecurity(string $baseUrl, string $apiKey): void
    {
        if (str_starts_with($baseUrl, 'https://')) {
            $this->components->info('Using HTTPS — the API key is encrypted in transit.');

            return;
        }

        $this->components->warn(
            'Endpoint is plain HTTP, so the API key travels unencrypted. Checking whether HTTPS is available…'
        );

        try {
            $secure = str_replace('http://', 'https://', $baseUrl);
            $response = Http::timeout(15)->get($secure.'/getBalanceApi', ['api_key' => $apiKey]);

            if ($response->successful()) {
                $this->components->info(
                    'HTTPS works. Set BULKSMSBD_BASE_URL='.$secure.' in .env.'
                );

                return;
            }

            $this->components->warn('HTTPS returned HTTP '.$response->status().'. Keeping the documented HTTP endpoint.');
        } catch (\Throwable $e) {
            $this->components->warn('HTTPS is not available on this endpoint: '.$e->getMessage());
        }
    }
}
