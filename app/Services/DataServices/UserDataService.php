<?php

namespace App\Services\DataServices;

use App\Enums\RoleNameEnum;
use App\Enums\SiteUserStatus;
use App\Enums\UserVerificationStatus;
use App\Filters\DeletedFilter;
use App\Filters\MonthFilter;
use App\Filters\PeriodFilter;
use App\Filters\YearFilter;
use App\Jobs\ProcessDataServiceExport;
use App\Modules\User\Models\User;
use App\Filters\UserOrderByFilter;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\FileExporterService;
use App\Services\ExportManager\Formatters\UserExportableDataFormatter;
use App\Services\Reporting\EntryStatistics;
use App\Services\Reporting\UserStatistics;
use App\Traits\Response;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Traits\SiteTrait;
use App\Http\Requests\UserListingQueryParamsRequest;
class UserDataService extends DataService implements DataServiceInterface
{
    use Response,SiteTrait;

    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        
        if ($request instanceof UserListingQueryParamsRequest) {
            return $this->getFilteredUsersQuery($request);
        }else{
            return $this->getFilteredUsersQueryExport($request);
        }
    }
    public function getFilteredUsersQuery(mixed $request): Builder
    {
        $term = request('term');
        $role = RoleNameEnum::tryFrom(request('role'))?->value;
        $parameters = array_filter(request()->query());
        $status = SiteUserStatus::tryFrom(request('status'))?->value;
        $verification = UserVerificationStatus::tryFrom(request('verification'))?->value;
        $query = User::query()
            ->with(['sites' => function ($query) {
                $query->where('sites.id', '=', clientSiteId());
            }, 'activeRole', 'roles', 'profile'])
            ->currentSiteOnly()
//            ->withOnly(['profile', 'roles'])
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new UserOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->filterListBy(new MonthFilter);

        if (count($parameters) === 0) {
            $query = $query->latest();
        }

        return $query->when($role, $this->applyRoleFilter($role))
            ->when($status, fn($query) => $query->whereHas('sites', function ($query) use ($status) {
                $query->where('site_user.status', '=', $status);
            }))
            ->when($verification, function($query) use ($verification) {
                if ($verification === UserVerificationStatus::Verified->value) {
                    $query->whereNotNull('email_verified_at');
                } elseif ($verification === UserVerificationStatus::Unverified->value) {
                    $query->whereNull('email_verified_at');
                }
            })
            ->when($term, $this->applySearchTermFilter($term));
    }

    public function getFilteredUsersQueryExport(mixed $request): Builder
    {
        $term = $request->term;
        $role = RoleNameEnum::tryFrom($request->role)?->value;
        $parameters = array_filter(request()->query());
        $status = SiteUserStatus::tryFrom($request->status)?->value;
        $verification = UserVerificationStatus::tryFrom($request->verification)?->value;

        $query = User::query()
            ->with(['sites' => function ($query) use ($request) {
                $query->where('sites.id', $request->site_id);
            }, 'activeRole', 'roles', 'profile'])
            ->whereHas('sites', function ($query) use ($request) {
                $query->where('site_user.site_id', '=', $request->site_id);
            })
            ->filterListBy(new DeletedFilter($request))
            ->filterListBy(new UserOrderByFilter($request))
            ->filterListBy(new PeriodFilter($request))
            ->filterListBy(new YearFilter($request))
            ->filterListBy(new MonthFilter($request));
        if (count($parameters) === 0) {
            $query = $query->latest();
        }
       return $query->when($role, function (Builder $query) use ($role, $request) {
            if (is_string($role)) {
                return $query->whereHas('roles', function ($query) use ($role, $request) {
                    // Exclude all global scopes for 'roles' model
                    return $query->withoutGlobalScopes() 
                                 ->where('name', $role)
                                 ->where('roles.site_id', '=', $request->site_id); 
                });
            }
            return $query;
        })
        ->when($status, fn($query) => $query->whereHas('sites', function ($query) use ($status) {
            $query->where('site_user.status', '=', $status);
        }))
        ->when($verification, function ($query) use ($verification) {
            if ($verification === UserVerificationStatus::Verified->value) {
                $query->whereNotNull('email_verified_at');
            } elseif ($verification === UserVerificationStatus::Unverified->value) {
                $query->whereNull('email_verified_at');
            }
        })
        ->when($term, $this->applySearchTermFilter($term));
    }

    /**
     * @param  mixed  $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        $this->paginatedList = $this->paginate($this->getFilteredQuery($request));

        $this->appendStatusAttribute();

        return $this->paginatedList;
    }

    /**
     * @return $this
     */
    public function appendStatusAttribute(): UserDataService
    {
        if (! is_null($this->paginatedList)) {
            $this->paginatedList = $this->paginatedList->through(function (User $user) {
                return $user->append('status');
            });
        }

        return $this;
    }

    /**
     * @param  mixed  $request
     * @return Collection|Builder
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * @param mixed $request
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $site = static::getSite();
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new UserExportableDataFormatter,
                'users'
            )),
            $request,
            $request->user(),
            $site,
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

    /**
     * @param string $user
     * @return array
     */
    public function getSettings(string $user): array
    {
        $user = User::query()
            ->where('ref', '=', $user)
            ->firstOrFail();
        $profile = $user->profile ?: $user->profile()->create();
        $paymentCards = $user->paymentCards;
        $participantProfile = $profile->participantProfile;
//        $fundRaisingPage = $user->fundRaisingPage;

        return [
            'personal_info' => [
                // User
                'ref' => $user->ref,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                // Profile
                'gender' => $profile->gender,
                'dob' => $profile->dob,
                'country' => $profile->country,
                'state' => $profile->state,
                'city' => $profile->city,
                'postcode' => $profile->postcode,
                'address' => $profile->address,
                'nationality' => $profile->nationality,
                'occupation' => $profile->occupation,
                'passport_number' => $profile->passport_number,
                'bio' => $profile->bio,
                // Participant Profile
                'tshirt_size' => $participantProfile?->tshirt_size,
                'emergency_contact_name' => $participantProfile?->emergency_contact_name,
                'emergency_contact_phone' => $participantProfile?->emergency_contact_phone,
                'slogan' => $participantProfile?->slogan,
                'club' => $participantProfile?->club,
            ],
            'socials' => $this->getUserSocials($user),
//            'fundraising_page' => $fundRaisingPage,
            'payment_cards' => $paymentCards
        ];
    }

    /**
     * @param User $user
     * @return mixed
     */
    private function getUserSocials(User $user): mixed
    {
        if ($user->socials()->exists()) {
            $socials = $user->socials->map(function ($social) {
                return [
                    'id' => $social['id'],
                    'platform' => $social['platform'],
                    'url' => $social['url'],
                    'is_social_auth' => $social['is_social_auth'],
                ];
            });
        } else $socials = [];

        return $socials;
    }

    /**
     * @param string|null $term
     * @return \Closure
     */
    public function applySearchTermFilter(?string $term): \Closure
    {
        return function (Builder $query) use ($term) {
            if (is_string($term)) {
                return $query->where(function (Builder $query) use ($term) {
                    $query->orWhere('email', 'LIKE', "%$term%")
                        ->orWhere('first_name', 'LIKE', "%$term%")
                        ->orWhere('last_name', 'LIKE', "%$term%")
                        ->orWhereRaw('concat(first_name," ",last_name) LIKE ?', "%{$term}%")
                        ->orWhere('phone', 'LIKE', "%$term%");
                });
            }
            return $query;
        };
    }

    /**
     * @param string|null $role
     * @return \Closure
     */
    public function applyRoleFilter(?string $role): \Closure
    {
        return function (Builder $query) use ($role) {
            if (is_string($role)) {
                return $query->whereHas('roles', function ($query) use ($role) {
                    return $query->where('name', $role);
                });
            }
            return $query;
        };
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getCurrentUser(): \Illuminate\Contracts\Auth\Authenticatable
    {
        return request()->user()->load(['activeRole', 'roles', 'profile']);
    }

    /**
     * @param string $ref
     * @return Builder|Model|\Illuminate\Database\Query\Builder
     */
    public function show(string $ref): Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder
    {
        return User::withTrashed()
            ->currentSiteOnly()
            ->where('ref', '=', $ref)
            ->firstOrFail();
    }

    /**
     * @param string $email
     * @return Builder|Model
     */
    public function _show(string $email): Model|Builder
    {
        return User::with('profile.participantProfile')
            ->currentSiteOnly()
            ->where('email', $email)
            ->firstOrFail();
    }

    /**
     * @param string $ref
     * @return \Illuminate\Database\Query\Builder|Builder|Model
     */
    public function edit(string $ref): Builder|Model|\Illuminate\Database\Query\Builder
    {
        return User::withTrashed()
            ->currentSiteOnly()
            ->withoutEagerLoads()
            ->where('ref', '=', $ref)
            ->with(['activeRole', 'roles', 'profile'])
            ->firstOrFail()
            ->append('status');
    }

    /**
     * @param array $ids
     * @return Collection
     */
    public function findMany(array $ids): Collection
    {
        return User::query()
            ->currentSiteOnly()
            ->findMany($ids)
            ->append('status');
    }

    /**
     * @return Collection
     */
    public function getEventManagers(): Collection
    {
        return User::select('id', 'ref', 'first_name', 'last_name')
            ->currentSiteOnly()
            ->whereHas('roles', function ($query) {
                $query->where('name', RoleNameEnum::EventManager);
            })->get();
    }

    /**
     * @return Collection|\Illuminate\Support\Collection|array
     */
    public function generateStatsSummary(): Collection|\Illuminate\Support\Collection|array
    {
        return (new UserStatistics)->summary();
    }

    /**
     * @return array
     */
    public function generateYearGraphData(): array
    {
        return (new UserStatistics)->chart();
    }
}
