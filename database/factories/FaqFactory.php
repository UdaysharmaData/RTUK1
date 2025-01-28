<?php

namespace Database\Factories;

use App\Models\Faq;
use Str;
use Database\Traits\SiteTrait;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faq>
 */
class FaqFactory extends Factory
{
    use SiteTrait;

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        $dispatcher = Faq::getEventDispatcher();

        return $this->afterMaking(function (Faq $faq) {
            // Remove Dispatcher for this factory
            Faq::unsetEventDispatcher();
        })->afterCreating(function (Faq $faq) use ($dispatcher) {
            // Re-add Dispatcher
            Faq::setEventDispatcher($dispatcher);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $faq = $this->faker->randomElement([
            \App\Models\City::class,
            \App\Models\Venue::class,
            \App\Models\Region::class,
            // \App\Models\Page::class,
            // \App\Models\Combination::class,
            \App\Modules\Event\Models\Event::class,
            \App\Modules\Event\Models\EventCategory::class
        ]);

        return [
            'faqsable_id' => $faq::factory(['ref' => Str::orderedUuid()]),
            'faqsable_type' => $faq,
            'ref' => Str::orderedUuid(),
            'site_id' => static::getSite()?->id,
            'user_id' => User::factory(),
            'section' => $this->faker->sentence('12'),
            'description' => $this->faker->sentence('12')
        ];
    }
}
