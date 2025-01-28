<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqDetails;
use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faq>
 */
class FaqDetailsFactory extends Factory
{
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (FaqDetails $faqDetails) {
            if ($faqDetails->faq?->faqsable_type == Event::class) {
                $details = $this->faqEvents[rand(0, count($this->faqEvents) - 1)];

                $faqDetails->question = $details['question'];
                $faqDetails->answer = $details['answer'];
            }
        })->afterCreating(function (FaqDetails $faqDetails) {
            // $faqDetails->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'faq_id' => Faq::factory(),
            'question' => $this->faker->sentence,
            'answer' => $this->faker->sentence('12'),
        ];
    }

    public $faqEvents = [
        [
            'question' => 'Are your events chip timed?',
            'answer' => 'Yes, all of our events are chip timed and accurately measured.'
        ],
        [
            'question' => 'What is the minimum age to enter an event?',
            'answer' => '11 years old for a 5k, 15 years old for a 10k and 17 years old for Half Marathon.'
        ],
        [
            'question' => 'Can I enter more than one person at once?',
            'answer' => 'The best way to enter more than one person is to do it in separate transactions so that we accurately capture the details of each runner. If you have already entered more than one person, send us an email at info@runthrough.co.uk with their details and we’ll get it sorted.'
        ],
        [
            'question' => 'When do entries close before an event? Can I enter on the day?',
            'answer' => 'Entries normally close when the event sells out or on the Thursday before the event. Entries on the day is dependent on the capacity for the event, please refer to the specific event page after online entries have closed to see is there are entries on the day.'
        ],
        [
            'question' => 'When will I receive more information about the event?',
            'answer' => 'You will receive all race information, via email, in the week leading up to the event. For a weekend event you will receive it by Wednesday, and for a mid-week event, you will receive it by Monday.'
        ],
        [
            'question' => "I didn't choose whether I am running 5k or 10k, did I need to?",
            'answer' => 'No, unless the different distances show as an option on the event webpage when entering, you can decide whether you run 5k or 10k on the day.'
        ],
        [
            'question' => 'Do we collect our Timing Chip and Race Number on the day of the event?',
            'answer' => 'Yes, that is correct. Please make sure you arrive at least 45 minutes before the race start so you have plenty of time to collect them.'
        ],
        [
            'question' => 'Are there water stations?',
            'answer' => 'Yes, every event has water stations – where and how many depends on the course and the distance.'
        ],
        [
            'question' => 'Where can I find my results after the event?',
            'answer' => 'Your results will be live on the website www.runthrough.co.uk during the event. If you have any questions or queries with your results please email info@runthrough.co.uk'
        ],
        [
            'question' => 'Can the events be used for Good For Age?',
            'answer' => 'Yes, all our events are officially measured and UKA certified, so can be used for GFA and qualifying times.'
        ],
        [
            'question' => 'Are there water stations?',
            'answer' => 'Our races are normally either licensed by The Association of Running Clubs (ARC) or RunBritain. All courses have been officially measured by the Association of UK Course Measurers and a copy of the measurement certificate will be on display at the information desk. If you have anymore questions around this please email info@runthrough.co.uk'
        ],
    ];
}
