<?php

namespace App\Modules\Participant\Models\Traits;

use App\Enums\CurrencyEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\ParticipantActionTypeEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Http\Helpers\AccountType;
use App\Http\Helpers\FormatNumber;
use App\Http\Helpers\ReplaceTextHelper;
use App\Mail\Mail;
use App\Mail\participant\entry\ParticipantDeleteAdminMail;
use App\Mail\participant\entry\ParticipantDeleteCustomerMail;
use App\Mail\participant\entry\ParticipantDeletedMail;
use App\Models\Invoice;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Participant\Models\Participant;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Modules\Setting\Enums\SiteEnum;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;
use App\Traits\AdministratorEmails;
use Illuminate\Support\Facades\Log;
use Stripe\Refund;

trait ParticipantDeleteTrait
{
    use AdministratorEmails;

    /**
     * Check if a participant can be deleted
     *
     * @param  mixed $participant
     * @return object
     */
    public static function canParticipantBeDeleted(Participant $participant): object
    {
        if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $result = static::isConsideredInEventPlaceInvoice($participant);
        } else {
            $result = (object)['status' => true, 'message' => null];
        }

        if ($result->status) {
            $eec = $participant->eventEventCategory;

            if ($eec) {
                if (AccountType::isAdmin()) {
                    $result = static::canParticipantBeDeletedByAdmin($participant);
                } else {
                    $result = static::canEntryBeDeletedByParticipant($participant);
                }
            }
        }

        return $result;
    }

    /**
     * Check if a participant can be deleted by an admin
     *
     * @param  Participant $participant
     * @return object
     */
    public static function canParticipantBeDeletedByAdmin(Participant $participant): object
    {
        $eec = $participant->eventEventCategory;

        if ($eec->hasExpired() && $participant->isConsideredAmongCompletedParticipants()) {
            $errorMessage = 'You cannot withdraw a participant after the event has expired!';
        }

        return (object)[
            'status' => !isset($errorMessage),
            'message' => $errorMessage ?? null
        ];
    }

    /**
     * canEntryBeDeletedByParticipant
     *
     * @param  mixed $participant
     * @return object
     */
    public static function canEntryBeDeletedByParticipant(Participant $participant): object
    {
        $eec = $participant->eventEventCategory;

        if ($eec->hasExpired()) {
            $errorMessage = 'You cannot withdraw an entry after the event has expired!';
        } elseif (!$eec->isWithdrawable()) {
            $errorMessage = 'You cannot withdraw an entry after the withdrawal deadline!';
        }

        return (object)[
            'status' => !isset($errorMessage),
            'message' => $errorMessage ?? null
        ];
    }

    /**
     * Check if the participant is considered in an event place invoice
     *
     * @param  Participant $participant
     * @return object
     */
    public static function isConsideredInEventPlaceInvoice(Participant $participant): object
    {
        $status = true;
        $eec = $participant->eventEventCategory;

        if ($participant->status == ParticipantStatusEnum::Complete && (!$participant->waive && $participant->waiver == ParticipantWaiverEnum::Charity)) {
            if ($charity = $participant->charity) {
                if ($eec && $eec->registration_deadline && !$eec->rolling_event) {
                    $year = $eec->registration_deadline->year;
                    $month = $eec->registration_deadline->month;
                } else {
                    $year = $participant->created_at->year;
                    $month = $participant->created_at->month;
                }

                if ($month >= 9 && $month <= 11) {
                    $period = '09_11';
                } elseif ($month >= 12 || $month <= 2) {
                    $period = '12_02';
                } elseif ($month >= 3 && $month <= 5) {
                    $period = '03_05';
                } elseif ($month >= 6 && $month <= 8) {
                    $period = '06_08';
                }

                $exists = $charity->eventPlaceInvoices()->where('year', $year)
                    ->where('period', $period)
                    ->exists();

                if ($exists) {
                    $participantOrEntry = ReplaceTextHelper::participantOrEntry($participant);
                    $status = false;
                    $message = "The $participantOrEntry is present in an event place invoice. You cannot delete the $participantOrEntry!";
                }
            }
        }

        return (object)[
            'status' => $status,
            'message' => $message ?? null
        ];
    }

    /**
     * customDelete
     *
     * @param  mixed $participant
     * @return object
     */
    public static function customDelete(Participant $participant): object
    {
        $validation = static::canParticipantBeDeleted($participant);

        if (!$validation->status) {
            return $validation;
        }

        $eec = $participant->eventEventCategory;
        $user = $participant->user;

        DB::beginTransaction();
       
        if ($participant->payment_status->value == ParticipantPaymentStatusEnum::Paid->value || ($participant->waive && $participant->waiver == ParticipantWaiverEnum::Partner)) {
            if ($invoiceItem = $participant->invoiceItem) {
                $invoice = $invoiceItem->invoice;

                if ($invoice && $transaction = $invoice->transaction) {
                    if ($externalTransaction = $transaction->externalTransaction) {

                        try {
                            $stripeSecretKey = config('stripe.' . static::getSite()->code . '.secret_key');
                            $stripe = new StripeClient($stripeSecretKey);

                            $refund = $stripe->refunds->create([
                                'charge' => $externalTransaction->charge_id,
                                'amount' => FormatNumber::convertAmount($invoiceItem->final_price, CurrencyEnum::Cents),
                                'reason' => 'requested_by_customer',
                            ]);

                            if ($refund->status == 'succeeded') {
                                // save refund transaction
                                $refundTransaction = new Transaction();
                                $refundTransaction->transactionable()->associate($invoiceItem);
                                $refundTransaction->user_id = $user->id;
                                $refundTransaction->ongoing_external_transaction_id = $transaction->ongoing_external_transaction_id;
                                $refundTransaction->status = TransactionStatusEnum::Completed;
                                $refundTransaction->type = TransactionTypeEnum::Refund;
                                $refundTransaction->amount = $invoiceItem->final_price;
                                $refundTransaction->description = 'Refund applied on participant deleted';
                                $refundTransaction->save();

                                // save external transaction
                                $transaction->externalTransaction()->create([
                                    'payment_intent_id' => $externalTransaction->payment_intent_id,
                                    'charge_id' => $externalTransaction->charge_id,
                                    'refund_id' => $refund->id
                                ]);

                                $invoiceItem->update(['status' => InvoiceItemStatusEnum::Refunded]);
                                Invoice::updateStatus($invoiceItem, InvoiceStatusEnum::Refunded);
                            } else {
                                if ($refund->status == 'pending') {
                                    $stripe->refunds->cancel($refund->id);
                                }

                                $errorMessage = static::refundStripeFailureCustomMessage($refund->failure_reason ?? '');

                                Log::channel(static::getSite()->code . 'stripecharge')->info("Refund Failed: " . $refund->failure_reason);
                                Log::channel(static::getSite()->code . 'adminanddeveloper')->info("Refund Failed: " . $refund->failure_reason);
                            }
                        } catch (Exception $e) {
                            $errorMessage = 'An error occurred while processing the refund. Please try again later!';
                            Log::channel(static::getSite()->code . 'stripecharge')->info("Refund Exception: " . $e->getMessage());
                            Log::channel(static::getSite()->code . 'adminanddeveloper')->info("Refund Exception: " . $e->getMessage());
                            Log::channel(static::getSite()->code . 'stripecharge')->debug($e);
                            Log::channel(static::getSite()->code . 'adminanddeveloper')->debug($e);
                        }
                    }
                }
            } else {
                if ($participant->waive && $participant->waiver == ParticipantWaiverEnum::Partner) {

                    // credit user account when the participant has been waived by a partner
                    $refundTransaction = $user->participantProfile->creditAccount([
                        'type' => AccountTypeEnum::Infinite,
                        'balance' => $eec->userRegistrationFee($user),
                        'user' => $user,
                    ], [
                        'transaction_type' => TransactionTypeEnum::Refund,
                        'description' => 'Refund applied on participant deleted '
                    ]);
                }
            }
        }

        // save participant action
        $participant->participantActions()->create([
            'user_id' => Auth::user()->id,
            'type' => ParticipantActionTypeEnum::Deleted,
            'role_id' => Auth::user()->activeRole?->role_id
        ]);

        $participant->delete();

        DB::commit();

        try {
            // Notify the participant
            $isParticipantCurrentUser = $participant->user_id == Auth::id();
            Mail::site()->send(new ParticipantDeleteCustomerMail($participant, $refundTransaction ?? null, $isParticipantCurrentUser));

            if (!AccountType::isAdmin()) {
                // Notify the admin
                // static::sendEmails(new ParticipantDeleteAdminMail($participant, $refundTransaction ?? null, $isParticipantCurrentUser));
            }
        } catch (Exception $e) {
            Log::channel(static::getSite()->code . 'mailexception')->info("Participant - Delete - Mail");
            Log::channel(static::getSite()->code . 'mailexception')->info($e);
            Log::channel(static::getSite()->code . 'mailexception')->debug($e);
        }

        return (object) [
            'status' => !isset($errorMessage),
            'message' => $errorMessage ?? 'Participant deleted successfully!'
        ];
    }

    /**
     * refund Stripe failure custom message
     *
     * @param  mixed $reason
     * @return string
     */
    public static function refundStripeFailureCustomMessage(string $reason): string
    {
        switch ($reason) {
            case 'lost_or_stolen_card':
                return 'The card used for payment has been reported lost or stolen. Please use another card.';
            case 'expired_or_canceled_card':
                return 'The card used for payment has expired or has been canceled. Please use another card.';
            case 'declined':
                return 'The card used for payment has been declined. Please use another card.';
            default:
                return 'An error occurred while processing the refund. Please try again later!';
        }
    }
}
