<?php

namespace App\Http\Controllers\Portal;

use DB;
use Str;
use Auth;
use Rule;
use Excel;
use Storage;
use Validator;
use Carbon\Carbon;
use App\Http\Helpers\Years;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Collection;
use App\Jobs\ParticipantsNotifyJob;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\AddEventToPromotionalPagesJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Models\Upload;
use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;

use App\Modules\Participant\Requests\ParticipantNotifyRequest;
use App\Modules\Participant\Requests\ParticipantUpdateRequest;

use App\Modules\Event\Resources\EventResource;
use App\Http\Resources\InvoiceResource;
use App\Modules\Participant\Resources\ParticipantResource;

use App\Modules\Participant\Requests\ParticipantDeleteRequest;

use App\Enums\GenderEnum;
use App\Enums\RegionEnum;
use App\Enums\EventStateEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Http\Requests\FamilyRegistrationRequest;
use App\Modules\Event\Models\EventEventCategory;
use App\Traits\Response;
use App\Traits\SiteTrait;

/**
 * @group Profile
 * Manages users profiles on the application
 * @authenticated
 */
class ProfileController extends Controller
{
    use Response, SiteTrait;

    /*
    |--------------------------------------------------------------------------
    | Participant Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with participants. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the invoices of the user (having the participant role)
     * 
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function invoices(Request $request): JsonResponse
    {
        if (! AccountType::isParticipant()) { // Only participants have access to this
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $invoices = Invoice::with(['site' => function ($query) {
            $query->where('id', static::getSite()?->id);
        }, 'invoiceable', 'upload']);

        $invoices = $invoices->whereHas('site', function ($query) {
            $query->where('id', static::getSite()?->id);
        })->whereHasMorph(
            'invoiceable',
            [User::class],
            function ($query) {
                $query->where('id', Auth::user()->id);
            }
        );

        if ($request->filled('term')) {
            $invoices = $invoices->where(function($query) use ($request) {
                $query->where('name', 'like', '%'.$request->term.'%')
                    ->orWhere('description', 'like', '%'.$request->term.'%')
                    ->orWhere('price', 'like', '%'.$request->term.'%')
                    ->orWhere('po_number', 'like', '%'.$request->term.'%')
                    ->orWhere('issue_date', 'like', '%'.$request->term.'%')
                    ->orWhere('due_date', 'like', '%'.$request->term.'%')
                    ->orWhere(function($query) use ($request) {
                        $query->whereHas('invoiceItems', function ($query) use ($request) {
                            $query->whereHasMorph(
                                'invoiceItemable',
                                [Participant::class],
                                function($query) use ($request) {
                                    $query->where('user_id', Auth::user()->id)
                                        ->whereHas('charity', function ($query) use ($request) {
                                            $query->where('name', 'like', '%'.$request->term.'%');
                                        });
                                }
                            );
                        });
                    });
            });
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $invoices = $invoices->paginate($perPage);

        return $this->success('My invoices', 200, [
            'invoices' => new InvoiceResource($invoices)
        ]);
    }
}
