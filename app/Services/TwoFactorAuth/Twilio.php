<?php

namespace App\Services\TwoFactorAuth;

use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Twilio
{
    /**
     * @var Client
     */
    private Client $client;

    private string $senderId;

    public function __construct()
    {
        if (config('services.twilio.environment') == 'test') {
            $username = config('services.twilio.test.sid');
            $password = config('services.twilio.test.auth_token');
            $this->senderId =  config('services.twilio.test.sender_id');

        } else {
            $username = config('services.twilio.live.sid');
            $password = config('services.twilio.live.auth_token');
            $this->senderId =  config('services.twilio.live.sender_id');
        }

        $this->client = new Client($username, $password);
    }

    /**
     * Send message to a set of phone numbers
     * @param $phones
     * @param $message
     * @return true
     * @throws TwilioException
     */
    public function sendSms($phones, $message): bool
    {

        try {
            $this->client->messages->create(
                $phones,
                ['body' => $message, 'from' => $this->senderId]
            );

        } catch (TwilioException $exception) {
//            Log::error($exception->getMessage());
            throw new TwilioException($exception->getMessage());
        }
        return true;
    }
}
