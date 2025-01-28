<?php

namespace App\Models;

use App\Traits\HasManyFaqs;
use Illuminate\Support\Str;
use App\Contracts\CanHaveManyFaqs;
use App\Modules\Setting\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiClient extends Model implements CanHaveManyFaqs
{
    use HasFactory, HasManyFaqs;

    /**
     * eager-load api client's relations
     */
    protected $with = [
        'site'
    ];

    /**
     * default API clients
     */
    const DEFAULT_CLIENTS = [
        'RunThrough' => [
            'host' => 'runthrough.co.uk',
            'is_active' => true,
        ],
        'RunThroughHub' => [
            'host' => 'hub.runthrough.co.uk',
            'is_active' => true,
        ],
        'RunForCharity' => [
           'host' => 'runforcharity.com',
           'is_active' => true,
       ],
        'SportsMediaAgency' => [
            'host' => 'sportsmediaagency.com',
            'is_active' => true,
        ]
    ];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'name' => ['required', 'string', 'unique:api_clients'],
            'host' => ['required', 'string', 'unique:api_clients'],
            'ip' => ['nullable', 'ip', 'unique:api_clients'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'site_id' => ['sometimes', 'integer', 'exists:sites,id'],
        ]
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'host',
        'is_active',
        'site_id',
        'api_client_id'
    ];

    /**
     * get route-model binding attribute.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'api_client_id';
    }

    /**
     * @var string[]
     */
    protected $casts = [
        'is_active' => 'boolean'
    ];

//    /**
//     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
//     */
//    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
//    {
//        return $this->belongsTo(User::class, 'user_id');
//    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function site(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apiClientTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApiClientToken::class);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    /**
     * issue new client token
     * @return string
     */
    public function issueNewToken(): string
    {
        $model = $this->apiClientTokens()->create([
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(ApiClientToken::TOKEN_DURATION['minutes']),
        ]);

        return $model->token;
    }

    /**
     * create pre-listed default clients
     * @return void
     */
    public static function createDefaultClients()
    {
        if (Schema::hasTable('sites') && Site::exists()) {
            if (Schema::hasTable('api_clients')) {
                foreach (self::DEFAULT_CLIENTS as $name => $client) {
                    self::updateOrCreate([
                        'name' => $name,
                    ], [
                        'host' => $client['host'],
                        'is_active' => $client['is_active'],
                        'site_id' => Site::firstWhere('domain', $client['host'])->id,
                    ]);
                }
                echo 'Completed!';
            }
        }
    }

    /**
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->api_client_id = Str::orderedUuid();
            $model->site_id = request('site_id') ?? $model->site_id ?? clientSiteId();
        });
    }
}
