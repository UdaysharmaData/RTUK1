<?php

namespace App\Services\DataExchangeService\Actions;

use App\Models\Payload;
use Codexshaper\WooCommerce\Facades\Customer;
use Codexshaper\WooCommerce\Facades\Product;
use Illuminate\Support\Facades\Log;

class MigratePayloadData
{
    /**
     * @var Payload
     */
    private Payload $payload;

    /**
     * @var int
     */
    private int $completedQueryCounter = 0;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private mixed $config;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Payload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function process()
    {
        $this->writeToDatabase()
            ->sanitizePostMigration();
    }

    /**
     * @return MigratePayloadData
     */
    private function writeToDatabase(): MigratePayloadData
    {
        foreach ($this->payloadData() as $payload) {
            try {
                // todo: write data to appropriate source
                // process $payload
                $this->createWoocommerceCustomer($payload);
                $this->createWoocommerceProduct($payload);

                $this->completedQueryCounter++;

            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function allDone(): bool
    {
        return $this->completedQueryCounter === count($this->payloadData());
    }

    /**
     * @return void
     */
    private function sanitizePostMigration(): void
    {
        if ($this->allDone() && $this->payload->delete()) {
            Log::info('Importing Data Batch Complete.');
        }
    }

    /**
     * @return mixed
     */
    private function payloadData(): mixed
    {
        return json_decode($this->payload->response, true)['data'];
    }

    /**
     * @param array $data
     * @return void
     */
    private function createWoocommerceCustomer(array $data): void
    {
        Customer::create([
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'john.doe',
            'billing' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => '',
                'address_1' => '969 Market',
                'address_2' => '',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postcode' => '94103',
                'country' => 'US',
                'email' => 'john.doe@example.com',
                'phone' => '(555) 555-5555'
            ],
            'shipping' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => '',
                'address_1' => '969 Market',
                'address_2' => '',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postcode' => '94103',
                'country' => 'US'
            ]
        ]);
    }

    /**
     * @param array $data
     * @return void
     */
    private function createWoocommerceProduct(array $data): void
    {
        Product::create([
            'name' => 'Simple Product',
            'type' => 'simple',
            'regular_price' => '10.00',
            'description' => 'Simple product full description.',
            'short_description' => 'Simple product short description.',
        ]);
    }
}
