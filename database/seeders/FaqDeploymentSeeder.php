<?php

namespace Database\Seeders;

use Str;
use Storage;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Faq;
use App\Models\Page;
use App\Models\FaqDetails;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Traits\SiteTrait;

class FaqDeploymentSeeder extends Seeder
{
    use WithoutModelEvents, SiteTrait;

    private $pages;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct()
    {
        $this->pages = config('pages');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $path = config('app.images_path') . '/';

        foreach ($this->pages as $page) {
            $_page = Page::updateOrCreate([
                'site_id' => static::getSite()?->id,
                'url' => $page['url']
            ],[
                'ref' => Str::orderedUuid(),
                'name' => $page['name'],
                'status' => true
            ]);

            foreach ($page['faqs'] as $faq) {
                $_faq = Faq::create([
                    'site_id' => static::getSite()?->id,
                    'user_id' => User::where('email', 'matt@runthrough.co.uk')->value('id'),
                    'ref' => Str::orderedUuid(),
                    'section' => $faq['section'],
                    'faqsable_type' => Page::class,
                    'faqsable_id' => $_page->id
                ]);

                foreach ($faq['faq_details'] as $faq_detail) {
                    $_faq_detail = FaqDetails::create([
                        'faq_id' => $_faq->id,
                        'ref' => Str::orderedUuid(),
                        'question' => $faq_detail['question'],
                        'answer' => $faq_detail['answer'],
                        'view_more_link' => $faq_detail['viewMoreLink'] ?? null
                    ]);

                    if (isset($faq_detail['media']) && count($faq_detail['media']) > 0) {
                        foreach ($faq_detail['media'] as $media) {
                            if (Storage::disk('public')->exists($path . 'faqs/' . $media)) {
                                $extension = \File::extension(storage_path('app/public/' . 'public/' . $path . 'faqs/' . $media)); // Get the file extension

                                $fileName = Str::random(40) . '.' . $extension;

                                Storage::move('public/' . $path . 'faqs/' . $media, 'public/' . $path . $fileName); // Rename and move the file to its right path

                                $_faq_detail->uploads()->create([
                                    'ref' => Str::orderedUuid(),
                                    'type' => UploadTypeEnum::Image,
                                    'use_as' => UploadUseAsEnum::Image,
                                    'url' => $path . $fileName,
                                    'title' => strip_tags($faq_detail['question']),
                                    'description' => strip_tags($faq_detail['answer']),
                                ]);

                                Storage::disk('public')->delete($path . 'faqs/' . $media); // Delete the copied file
                            }
                        }
                    }
                }
            }
        }

        if (Storage::disk('public')->exists($path . 'faqs'))
            Storage::disk('public')->deleteDirectory($path . 'faqs'); // Delete the faqs folder
    }
}
