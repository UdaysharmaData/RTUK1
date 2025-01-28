<?php

namespace App\Services\ExportManager;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Exports\CsvExporter;
use App\Services\DataCaching\CacheDataManager;
use App\Traits\DownloadTrait;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;
use App\Services\DataServices\Contracts\DataServiceInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request as HttpRequest;

class FileExporterService
{
    use DownloadTrait;

    public function __construct(
        private readonly DataServiceInterface            $dataService,
        private readonly ExportableDataTemplateInterface $dataTemplate,
        private readonly string                          $label,
    ) {}

    /**
     * @param array $data
     * @param string $fileName
     * @param array $headers
     * @return void
     */
    private function storeFile(array $data, string $fileName, array $headers): void
    {
        Excel::store(
            new CsvExporter($data),
            config('app.csvs_path') . '/' . $fileName,
            config('filesystems.default'),
            \Maatwebsite\Excel\Excel::CSV,
            $headers
        );
    }

    /**
     * @param mixed $request
     * @param bool $sendAsAttachment
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     * @throws ExportableDataMissingException
     * @throws \Exception
     */
    public function download(mixed $request, bool $sendAsAttachment = false,$site,$user): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
       
        $new_request=new HttpRequest($request);
        $new_request['export'] = true;
        $new_request['site_id'] =$site->id;
        $new_request['user_id']= $user->id;
        $exportedList = $this->dataService->getExportList($new_request);
        $exportedListToFormat = config('filesystems.default') === 's3' ? $exportedList : $exportedList->take(10000);
        $data = $this->dataTemplate->format($exportedListToFormat);
        // catch exception in controller to obtain the message and code
        if (empty($data)) throw new ExportableDataMissingException(sprintf('The %s were not found', $this->label), 406);

        $headers = ['Content-Type' => 'text/csv'];
        $fileName = Str::ucfirst($this->label) . '-' . date('Y-m-d_H-i-s') . '.csv';
        $path = config('app.csvs_path') . '/' . $fileName;

        $this->storeFile($data, $fileName, $headers);
        return static::generateMinTypeForFile($path, true, null, true);
    }
}
