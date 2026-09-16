<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Communication\Sms\BulkSmsBdChannel;
use App\Services\Communication\Sms\OtpMessage;
use App\Services\Communication\Sms\SmsMessage;
use Illuminate\Console\Command;

/**
 * Sends one real SMS through the configured gateway.
 *
 * This SPENDS REAL BALANCE and texts a real phone, so it confirms before
 * sending and shows the exact body and segment cost first.
 */
class SmsTestCommand extends Command
{
    protected $signature = 'sms:test
        {phone : Recipient, e.g. 01712345678}
        {--message= : Custom body. Defaults to a short English test message.}
        {--bangla : Send a Bangla body, to verify Unicode rendering on the handset}
        {--otp : Send an OTP in the vendor-mandated format}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Send one real SMS through the configured gateway (spends balance)';

    public function handle(): int
    {
        /** @var array<string, mixed> $config */
        $config = config('sms.drivers.bulksmsbd');

        if (blank($config['api_key']) || blank($config['sender_id'])) {
            $this->components->error(
                'BULKSMSBD_API_KEY and BULKSMSBD_SENDER_ID must both be set. '
                .'An unset sender id returns code 1002 on every send.'
            );

            return self::FAILURE;
        }

        $body = $this->body();
        $sms = SmsMessage::make((string) $this->argument('phone'), $body);

        if (! $sms->isDeliverable()) {
            $this->components->error(
                'That is not a valid Bangladeshi mobile number. Expected 01[3-9] followed by eight digits.'
            );

            return self::FAILURE;
        }

        $channel = new BulkSmsBdChannel($config);
        $balanceBefore = $channel->balance();

        $this->newLine();
        $this->components->twoColumnDetail('To', $sms->to);
        $this->components->twoColumnDetail('Sender ID', (string) $config['sender_id']);
        $this->components->twoColumnDetail('Encoding', $sms->type().($sms->isUnicode ? ' <fg=yellow>(70 chars/segment)</>' : ' (160 chars/segment)'));
        $this->components->twoColumnDetail('Characters', (string) mb_strlen($body));
        $this->components->twoColumnDetail('Segments (billed)', '<fg=yellow>'.$sms->segments.'</>');
        $this->components->twoColumnDetail('Balance before', $balanceBefore === null ? 'unknown' : (string) $balanceBefore);
        $this->newLine();
        $this->line('  <fg=gray>Body:</> '.$body);
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Send this message? It spends real balance.', false)) {
            $this->components->info('Cancelled. Nothing was sent.');

            return self::SUCCESS;
        }

        $result = $channel->send($sms->to, $body);

        $this->newLine();

        if (! $result->ok) {
            $this->components->error('Send failed.');
            $this->components->twoColumnDetail('Code', (string) $result->code);
            $this->components->twoColumnDetail('Meaning', (string) $result->message);

            if ($result->halt) {
                $this->components->warn(
                    'This is an account or configuration problem, not a transient one. '
                    .'A campaign would halt here rather than retry.'
                );
            }

            return self::FAILURE;
        }

        $this->components->info('Sent. Code '.$result->code.' — '.$result->message);

        // The channel caches balance, so read past it for a true after-figure.
        cache()->forget('sms.bulksmsbd.balance');
        $balanceAfter = $channel->balance();

        $this->components->twoColumnDetail('Balance after', $balanceAfter === null ? 'unknown' : (string) $balanceAfter);

        if ($balanceBefore !== null && $balanceAfter !== null) {
            $this->components->twoColumnDetail(
                'Cost',
                '<fg=yellow>'.round($balanceBefore - $balanceAfter, 4).'</>',
            );
        }

        $this->newLine();
        $this->components->info('Check the handset: Bangla must render as Bangla, not as boxes or question marks.');

        return self::SUCCESS;
    }

    private function body(): string
    {
        if ($this->option('otp')) {
            return OtpMessage::make((string) random_int(100000, 999999))->body;
        }

        $custom = $this->option('message');

        if (is_string($custom) && $custom !== '') {
            return $custom;
        }

        if ($this->option('bangla')) {
            return 'সুবর্ণজয়ন্তী ২০২৬ — প্রাক্তন ছাত্র-ছাত্রী পরিষদ। এটি একটি পরীক্ষামূলক বার্তা।';
        }

        return 'SSHS Alumni: test message from the Golden Jubilee platform.';
    }
}
