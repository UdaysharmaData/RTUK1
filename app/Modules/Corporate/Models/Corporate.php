<?php

namespace App\Modules\Corporate\Models;

use App\Models\Registration;
use App\Http\Helpers\TextHelper;
use Mtownsend\ReadTime\ReadTime;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Modules\User\Models\Deposit;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasOneUpload;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;

use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Event\Models\EventPage;
use App\Modules\Charity\Models\Donation;

class Corporate extends Model implements CanHaveUploadableResource
{
    // Todo: move class to Corporate module
    use HasFactory, HasOneUpload, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        //'image', // Todo: refactor property to Uploadable instance
        'bio',
        'primary_color',
        'secondary_color',
        'slug' // Todo: refactor setter
    ];

    /**
     * @var string[]
     */
    public static $rules = [
        'name' => 'sometimes|required|string|max:255', // why do we have this here?
        'email' => 'sometimes|required|email|unique:users,email',
        'credits' => 'integer|min:0',
        'password' => 'string|confirmed',
        'password_confirmation' => 'required_with:password|string',
        'verified' => 'boolean',
        'image' => 'image',
        'bio' => 'string',
        'primary_color' => 'string|max:7',
        'secondary_color' => 'string|max:7'
    ];

    /**
     * @return string
     */
    public function fullName(): string
    {
        return "{$this->user->first_name} {$this->user->last_name}";
    }

    /**
     * @return void
     */
    public function setSlug()
    {
        $this->slug = TextHelper::slugifyHyphen($this->fullName());
    }

    /**
     * set `slug` attribute
     * @return Attribute
     */
    public function slug(): Attribute // Todo: Clarification needed. Maybe use an observer here instead?
    {
        return new Attribute(
            set: fn () => TextHelper::slugifyHyphen($this->fullName())
        );
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the donations that belong to the corporate.
     * @return HasMany
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get the website enquiries associated with the corporate.
     * @return HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get the event pages associated with the corporate.
     * @return HasMany
     */
    public function eventPages(): HasMany
    {
        return $this->hasMany(EventPage::class);
    }

    /**
     * Return the corporates deposits
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function registrations()
    {
        return $this->hasMany('App\Models\Registration');
    }

    public static function listAll()
    {
        return Corporate::with('user')->get()
            ->sort(function($a, $b) {
                if($a->fullName() > $b->fullName()) {
                    return 1;
                } elseif($a->fullName() < $b->fullName()) {
                    return -1;
                } else {
                    return 0;
                }
            });
    }

    /**
     * Work out the corporates credit balance
     * Total up the deposits - the refund amounts
     * Total up the spends on event pages
     */
    public function getCreditBalance()
    {
        $deposited = 0;
        $donated = 0;
        $amount = 0;
        $refund = 0;
        $registered = 0;

        // Determine purchased credit balance
        foreach($this->deposits as $deposit) {
            if ($deposit->amount) {
                $amount += (int) ($deposit->amount * $deposit->conversion_rate);
            }

            if ($deposit->refund) {
                $refund += (int) ($deposit->refund * $deposit->conversion_rate);
            }
        }

        $deposited = $amount - $refund;

        // Determine donated credit balance
        foreach ($this->donations as $donation) {
            $donated += (int) ($donation->amount * $donation->conversion_rate);
        }

        // Determine credit balance spent in registering participants
        foreach ($this->registrations as $registration) {
            $registered += (int) ($registration->amount * $registration->conversion_rate);
        }

        return ($deposited - ($donated + $registered));
    }

    public function dashboardEventPages()
    {
        return EventPage::with('charity', 'event')
            ->where('corporate_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    }

    public function dashboardDonations()
    {
        return Donation::with('charity') // Todo: add `Donation` model
            ->where('corporate_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    }

    public function dashboardRegistations()
    {
        return Registration::with('participant.event') // Todo: add `Registration` model
            ->where('corporate_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    }
}