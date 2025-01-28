<?php

namespace Database\Seeders;

use App\Models\ApiClientCareer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiClientCareerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seed();
    }

    /**
     * @return void
     */
    private function seed(): void
    {
        foreach ($this->factory() as $career) {
            ApiClientCareer::create([
                'title' => $career['title'],
                'description' => $career['description'],
                'link' => $career['link']
            ]);
        }
        echo PHP_EOL . 'seeded';
    }

    /**
     * @return \array[][]
     */
    private function factory(): array
    {
        return [
            [
                'title' => 'Event Operations Manager (Surrey)',
                'description' => 'We are looking for an experienced candidate to manage a team of operations executives and support the delivery and execution of regular mass-participation running events.',
                'link' => 'https://www.uksport.gov.uk/jobs-in-sport/Event-Operations-Manager-UKSP-41294'
            ],
            [
                'title' => 'Content Producer (Loughborough)',
                'description' => 'We are looking for someone with a passion for making creative content on a spectrum of social media platforms to collaborate with our marketing and communications teams.',
                'link' => 'https://www.uksport.gov.uk/jobs-in-sport/CONTENT-PRODUCER-UKSP-40594'
            ],
            [
                'title' => 'Communications & PR Manager (Loughborough)',
                'description' => 'We are looking for a skilled writer with experience in PR, customer service and communications to join and lead our passionate marketing and communications team.',
                'link' => 'https://www.uksport.gov.uk/jobs-in-sport/Communications-PR-Manager-UKSP-41009'
            ],
            [
                'title' => 'Event Operations Executive (Surrey)',
                'description' => 'We are looking for someone to support the delivery of professional mass-participation running events. You will work with and support the operations team with venue management, course designs and ensuring high-standards are maintained in the preparation and delivery of events.',
                'link' => 'https://www.uksport.gov.uk/jobs-in-sport/Event-Operations-Executive-UKSP-41019'
            ],
            [
                'title' => 'Head of Marketing (Loughborough)',
                'description' => 'We are looking for an experienced senior manager in the marketing team to be responsible for fostering a collaborative approach with other areas of the business, including but not limited to commercial, operations, customer experience and technologies teams.',
                'link' => 'https://www.uksport.gov.uk/jobs-in-sport/Head-of-Marketing-UKSP-41017'
            ],
            [
                'title' => 'Logistics & Operations Executive (Manchester)',
                'description' => 'We are looking for someone with a genuine interest in sporting events and willingness to adapt and learn to work directly with our operations team to organise and deliver running events across the North West & Midlands.',
                'link' => 'https://www.uksport.gov.uk/jobs-in-sport/Event-Operations-Logistics-Executive-UKSP-38331'
            ]
        ];
    }
}
