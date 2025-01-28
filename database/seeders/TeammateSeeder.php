<?php

namespace Database\Seeders;

use App\Models\Teammate;
use App\Models\Upload;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use App\Services\FileManager\FileManager;
use App\Services\FileManager\Traits\SingleUploadModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeammateSeeder extends Seeder
{
    use SingleUploadModel;
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws UnableToOpenFileFromUrlException
     */
    public function run()
    {
        $this->seed();
    }

    /**
     * @return void
     * @throws \App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException
     */
    private function seed(): void
    {
        foreach ($this->getDataChunks() as $chunk) {
            foreach ($chunk as $teammate) {
                $newTeammate = Teammate::updateOrCreate(
                    ['name' => $teammate['name']],
                    ['title' => $teammate['title']]
                );

                try {
                    $fileVisibility = 'public';
                    $uploadQuery = $newTeammate->upload();
                    if ($uploadQuery->exists()) {
                        $upload = $uploadQuery->get();
                        Storage::disk($fileVisibility)->delete($upload->storage_url)
                        && $upload->delete();
                    }

                    $file = FileManager::createFileFromUrl($teammate['avatar']);
                    $path = self::getPath('image');
                    $url = Storage::disk($fileVisibility)->putFile($path, $file, $fileVisibility);

                    $uploadQuery->create([
                        'url' => $url,
                        'type' => FileManager::guessFileType($file),
//                        'metadata' => FileManager::setFileMetadata($url)
                    ]);
                } catch (UnableToOpenFileFromUrlException $exception) {
                    Log::error($exception->getMessage());
                    continue;
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }
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
                'name' => 'Matt Wood',
                'title' => 'Co-Founder',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/07/wood.jpg'
            ],
            [
                'name' => 'Ben Green',
                'title' => 'Co-Founder',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/07/ben-1.jpg'
            ],
            [
                'name' => 'Lucy Harfield',
                'title' => 'Managing Director',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/08/WhatsApp-Image-2022-01-15-at-13.30.02-1-500x500.jpeg'
            ],
            [
                'name' => 'Jonny Woodhouse',
                'title' => 'Head of Operations',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/07/PHOTO-2021-09-08-21-51-05-500x500.jpg'
            ],
            [
                'name' => 'Jatila Blake',
                'title' => 'Head of Event Staffing & Volunteering',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/08/jatila.jpg'
            ],
            [
                'name' => 'James Cody',
                'title' => 'Events Logistics Manager',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/08/james.jpg'
            ],
            [
                'name' => 'Jason Prickett',
                'title' => 'Head of Timing & Tech',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/07/jason.jpeg'
            ],
            [
                'name' => 'Rob Sullivan',
                'title' => 'Event Operations & Planning Manager',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/36808289_2029671583770307_7496489979191754752_n-500x500.jpg'
            ],
            [
                'name' => 'Lucy Russell',
                'title' => 'Event Operations Manager',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/Image-from-iOS-500x500.jpg'
            ],
            [
                'name' => 'Liam Burthem',
                'title' => 'Event Operations Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/09/WhatsApp-Image-2021-10-04-at-08.05.14-1-500x500.jpg'
            ],
            [
                'name' => 'Nico Printant',
                'title' => 'Event Operations Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/09/PHOTO-2021-06-16-23-00-41-500x500.jpg'
            ],
            [
                'name' => 'Liam Doughty',
                'title' => 'Event Operations & Logistics Manager',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/09/Screenshot-2021-09-29-at-15.52.07-500x500.png'
            ],
            [
                'name' => 'Elsabe Sharpe',
                'title' => 'Communications & PR Manager',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/08/WhatsApp-Image-2021-11-27-at-14.11.34-500x500.jpeg'
            ],
            [
                'name' => 'Christian Disley-May',
                'title' => 'Event Logistics Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/WhatsApp-Image-2018-12-29-at-14.29.25-500x500.jpeg'
            ],
            [
                'name' => 'Katie Buckingham',
                'title' => 'Event Communications Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/09/FB_IMG_1630013416050-e1643647114391-500x472.jpg'
            ],
            [
                'name' => 'Emma Cockroft',
                'title' => 'Social Media & Communications Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2020/11/Screenshot-2020-11-18-at-12.21.30-500x500.png'
            ],
            [
                'name' => 'Ailie McGilligan',
                'title' => 'Marketing Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/09/PHOTO-2021-08-22-12-58-43-500x500.jpg'
            ],
            [
                'name' => 'Jess Hillard',
                'title' => 'Event Staffing Executive',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/09/PHOTO-2021-08-22-12-58-43-500x500.jpg'
            ],
            [
                'name' => 'Graham Green',
                'title' => 'Operations Assistant',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/08/graham.jpg'
            ],
            [
                'name' => '“Yummy Mummys”',
                'title' => 'Event Assistants',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/11/yummy1.jpg'
            ],
            [
                'name' => 'Nathaniel Bell',
                'title' => 'Events Assistant',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/Nathaniel.png'
            ],
            [
                'name' => 'Josh Ennis',
                'title' => 'Events Assistant',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/Josh-500x500.jpg'
            ],
            [
                'name' => 'Epiphany Russell',
                'title' => 'Events Assistant',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/Pip-500x500.jpg'
            ],
            [
                'name' => 'Tony Cavanagh',
                'title' => 'Medical Director',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2020/12/PHOTO-2021-05-23-11-15-46-2-500x500.jpg'
            ],
            [
                'name' => 'Seb Higgins',
                'title' => 'Head Photographer',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/08/seb.jpg'
            ],
            [
                'name' => 'Larren Jeffries',
                'title' => 'Social Media Assistant',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/Larren-500x500.jpg'
            ],
            [
                'name' => 'Ellie Tidey',
                'title' => 'Social Media Assistant',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/Ellie.jpg'
            ],
            [
                'name' => 'Clare Holman',
                'title' => 'Running Club Head Coach',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2018/11/clare.jpg'
            ],
            [
                'name' => 'Matthew Scott',
                'title' => 'Videographer',
                'avatar' => 'https://www.runthrough.co.uk/wp-content/uploads/2021/03/IMG_0901-1-500x500.jpg'
            ],
        ];
    }

    /**
     * @param int $count
     * @return array
     */
    private function getDataChunks(int $count = 3): array
    {
        return array_chunk(
            $this->factory(),
            $count,
            true
        );
    }
}
