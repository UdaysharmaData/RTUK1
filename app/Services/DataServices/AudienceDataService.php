<?php

namespace App\Services\DataServices;

use App\Enums\AudienceSourceEnum;
use App\Filters\AudienceOrderByFilter;
use App\Filters\AudiencesAuthorFilter;
use App\Filters\AudiencesSourceFilter;
use App\Filters\DeletedFilter;
use App\Models\Audience;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\FileManager\FileManager;
use App\Services\Reporting\AudienceStatistics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AudienceDataService extends DataService implements DataServiceInterface
{
    /**
     * @var bool
     */
    private bool $loadRedirect = false;

    public function __construct()
    {
        $this->builder = Audience::query();
        $this->appendAnalyticsData = false;
    }

    /**
     * @param bool $value
     * @return AudienceDataService
     */
    public function setLoadRedirect(bool $value): static
    {
        $this->loadRedirect = $value;

        return $this;
    }

    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        $source = request('source');
        $term = request('term');
        $parameters = array_filter(request()->query());

        $query = $this->builder
            ->with('author', function ($query) {
                $query->select(['id', 'first_name', 'last_name', 'email']);
            })
            ->when($this->loadRedirect, fn($query) => $query->with('redirect'))
            ->filterListBy(new AudienceOrderByFilter)
            ->filterListBy(new AudiencesSourceFilter)
            ->filterListBy(new AudiencesAuthorFilter)
            ->filterListBy(new DeletedFilter);

        if (count($parameters) === 0) {
            $query = $query->latest();
        }

        return $query->when($term, $this->applySearchTermFilter($term));
    }

    /**
     * @param string|null $term
     * @return \Closure
     */
    private function applySearchTermFilter(string|null $term): \Closure
    {
        return function (Builder $query) use ($term) {
            if (isset($term)) {
                $query->Where('name', 'LIKE', "%$term%")
                    ->orWhere('description', 'LIKE', "%$term%");
            }
        };
    }

    /**
     * @param mixed $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        return $this->paginate($this->getBuilderWithAnalytics($this->getFilteredQuery($request)));
    }

    /**
     * @param mixed $request
     * @return Builder[]|Collection
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * Portal Show
     * @param string $ref
     * @return Model|Builder|\Illuminate\Database\Query\Builder|array
     */
    public function show(string $ref): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder|array
    {
        $model = $this->getBuilderWithAnalytics()
            ->where('audiences.ref', '=', $ref)
            ->firstOrFail();

        return $this->getAudienceRelations($model);
    }

    /**
     * @param string|null $source
     * @return string|null
     */
    public function getAudienceDynamicRelation(string $source = null): ?string
    {
        return $source === AudienceSourceEnum::Emails->value || $source === AudienceSourceEnum::MailingList->value
            ? 'mailingLists'
            : null;
    }

    /**
     * @param Request $request
     * @param $audience
     * @return void
     * @throws \App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException
     */
    public function processAudienceMailingList(Request $request, $audience): void
    {
        if ($request->get('source') === AudienceSourceEnum::Emails->value) {
            $this->processMailingListFromEmails($request->get('data')['emails'], $audience);
        } elseif ($request->get('source') === AudienceSourceEnum::MailingList->value) {
            if ($request->has('data.mailing_list')) {
                $this->processMailingListFromCsv(
                    FileManager::createFileFromUrl($request->data['mailing_list']),
                    $audience
                );
            }
        }
    }

    /**
     * @param UploadedFile $file
     * @param Audience $audience
     * @return Audience
     */
    private function processMailingListFromCsv(UploadedFile $file, Audience $audience): Audience
    {
        $handle = fopen($file->path(), 'r');
        fgetcsv($handle);
        $chunkSize = 25;

        while (! feof($handle)) {
            $chunk = [];

            for ($i = 0; $i < $chunkSize; $i++) {
                $data = fgetcsv($handle);
                if ($data === false) {
                    break;
                }
                $chunk[] = $data;
            }

            foreach ($chunk as $column) {
                $data = array_filter([
                    'first_name' => $column[0],
                    'last_name' => $column[1],
                    'email' => $column[2],
                    'phone' => $column[3],
                ]);

                if (
                    (!isset($data['email']))
                    || (!is_string($email = $data['email']))
                    || (!filter_var($email, FILTER_VALIDATE_EMAIL))
                ) {
                    continue;
                }

                $audience->mailingLists()
                    ->updateOrCreate(array_merge($data, ['site_id' => clientSiteId()]));
            }
        }
        fclose($handle);

        return $audience;
    }

    /**
     * @param array $emails
     * @param Audience $audience
     * @return void
     */
    public function processMailingListFromEmails(array $emails, Audience $audience): void
    {
        $lists = collect($emails)
            ->map(fn($email) => ['email' => $email])
            ->toArray();

        foreach ($lists as $list) {
            $audience->mailingLists()
                ->updateOrCreate(array_merge($list, ['site_id' => clientSiteName()]));
        }
    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return AudienceStatistics::generateStatsSummary($year, $period);
    }

    /**
     * @param Model $model
     * @param string $relation
     * @param bool $paginate
     * @return mixed
     */
    private function getRelations(Model $model, string $relation, bool $paginate): mixed
    {
        $query = $model->{$relation}()->when($term = request('term'), function ($query) use ($term) {
            $query->where('email', 'LIKE', '%'.$term.'%')
                ->orWhere('first_name', 'LIKE', '%'.$term.'%')
                ->orWhere('last_name', 'LIKE', '%'.$term.'%')
                ->orWhere('phone', 'LIKE', '%'.$term.'%');
        });

        if ($paginate) {
            return $query->when(((int)$perPage = request('per_page')),
                fn($query) => $query->paginate($perPage),
                fn($query) => $query->paginate(10)
            );
        } else {
            return $query->get();
        }
    }

    /**
     * @param Model|Builder|\Illuminate\Database\Query\Builder $model
     * @return array
     */
    public function getAudienceRelations(Model|Builder|\Illuminate\Database\Query\Builder $model): array
    {
        $model->load([
            'author' => fn($query) => $query->select(['id', 'first_name', 'last_name', 'email']),
            'role' => fn($query) => $query->select(['id', 'name']),
        ]);

        $data = [
            'audience' => $updatedModel = $this->modelWithAppendedAnalyticsAttribute($model)
        ];

        if (!is_null($relation = $this->getAudienceDynamicRelation($source = $updatedModel->source->value ?? null))) {
            if ($source === AudienceSourceEnum::MailingList->value) {
                $load = [Str::snake($relation) => $this->getRelations($updatedModel, $relation, true)];
            } elseif ($source === AudienceSourceEnum::Emails->value) {
                $load = [Str::snake($relation) => $this->getRelations($updatedModel, $relation, false)];
            }

            $data = array_merge($data, $load ?? []);
        }

        $data['audience']['author']['role'] = $data['audience']['role'];
        unset($data['audience']['role']);

        return $data;
    }
}
