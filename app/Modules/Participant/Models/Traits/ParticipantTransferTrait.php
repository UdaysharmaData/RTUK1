<?php

namespace App\Modules\Participant\Models\Traits;

use App\Mail\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Enums\ActivityLogNameEnum;
use App\Enums\CurrencyEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\InvoiceStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\SettingCustomFieldKeyEnum;
use App\Exceptions\MailException;
use App\Http\Helpers\AccountType;
use App\Http\Helpers\FormatNumber;
use App\Http\Helpers\ReplaceTextHelper;
use App\Jobs\GenerateInvoicePdfJob;
use App\Jobs\ResendEmailJob;
use App\Mail\participant\entry\ParticipantCompletedTransferMail;
use App\Mail\participant\entry\ParticipantPendingTransferMail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Event\Exceptions\EventEventCategoryException;
use App\Modules\Finance\Enums\TransactionPaymentMethodEnum;
use App\Modules\Finance\Requests\ParticipantRegistrationCreateRequest;
use App\Modules\Finance\Requests\ParticipantRegistrationUpdateRequest;
use App\Modules\Participant\Exceptions\ParticipantTransferException;
use App\Modules\Participant\Models\Participant;
use App\Modules\Setting\Models\SettingCustomField;
use App\Modules\User\Models\User;
use App\Services\Payment\ParticipantTransferPayment;
use Carbon\Carbon;
use Exception;
use Stripe\StripeClient;

trait ParticipantTransferTrait
{
    /**
     * Participant Transfer
     *
     * @param  mixed $participant
     * @param  mixed $eec
     * @param  mixed $user
     * @return void
     */
    public static function transfer(Participant $participant, EventEventCategory $eec, $user)
    {
        $validation = static::validateTransfer($participant, $eec, $user);

        if (!$user->isParticipant() && $validation['payment_required']) { // if the user performing the transfer is not a participant and there is an extra amount needed to be paid, send an email to the participant
            try {
                Mail::site()->send(new ParticipantPendingTransferMail($participant, $eec, $validation['total'], clientSite())); // Notify the participant via email
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - Transfer - Notify Participant");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new ParticipantPendingTransferMail($participant, $eec, $validation['total'], clientSite()), clientSite()));
            } catch (\Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - Transfer - Notify Participant");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new ParticipantPendingTransferMail($participant, $eec, $validation['total'], clientSite()), clientSite()));
            }

            $participantOrEntry = ReplaceTextHelper::participantOrEntry();

            if ($log = $participant->activities()->InLog(ActivityLogNameEnum::Transferring->value)->first()) {

                $properties['eec'] = $eec->ref;

                if (request()->custom_transfer_fee || request()->custom_transfer_fee == 0) {
                    $properties['transfer_fee'] = request()->custom_transfer_fee;
                }

                if (request()->cancel_difference) {
                    $properties['cancel_difference'] = request()->cancel_difference;
                }

                $log->properties = $properties;
                $log->save(); // Update the log with the new properties (in case the values were changed)

                $message = "The transfer is already in progress, and waiting for the $participantOrEntry to proceed. A new email was sent to the $participantOrEntry.";
            } else {
                $properties['eec'] = $eec->ref;

                if (request()->custom_transfer_fee || request()->custom_transfer_fee == 0) {
                    $properties['transfer_fee'] = request()->custom_transfer_fee;
                }

                if (request()->cancel_difference) {
                    $properties['cancel_difference'] = request()->cancel_difference;
                }

                $participant->logCustom(
                    ActivityLogNameEnum::Transferring,
                    null,
                    $properties,
                );

                $message = "Payment is required to complete the transfer. An email has been sent to the $participantOrEntry.";
            }

            return [
                'status' => false,
                'payment_required' => $validation['payment_required'],
                'message' => $message
            ];
        } else {
            if ($validation['payment_required']) { // Check if payment is required
                return $validation;
            } else {
                $result = static::processTransfer($participant, $eec, $validation);
            }

            return $result;
        }
    }

    /**
     * Validate the transfer
     *
     * @param  Participant $participant
     * @param  EventEventCategory $eec
     * @param  User $user
     * @return array
     */
    public static function validateTransfer(Participant $participant, EventEventCategory $eec, User $user): array
    {
        $oldEec = $participant->eventEventCategory;
        $regActive = $oldEec->registrationActive();
        $participantOrEntry = ReplaceTextHelper::participantOrEntry();
        $data = [];

        if ($regActive->status) {
            if ($participant->status == ParticipantStatusEnum::Transferred) {
                throw new ParticipantTransferException(
                    "The $participantOrEntry has already been transferred."
                );
            }

            if ($oldEec->id == $eec->id) {
                throw new ParticipantTransferException(
                    "The $participantOrEntry can\'t be transferred to the same event and category."
                );
            }

            $regActive = $eec->registrationActive();

            if ($regActive->status) {
                $hasAvailablePlaces = $eec->_hasAvailablePlaces(null, $participant->charity);

                if ($hasAvailablePlaces->status) {

                    Participant::isUserRegistered($participant->user, $eec); // Prevent double registration

                    if ($participant->payment_status->value == ParticipantPaymentStatusEnum::Paid->value || ($participant->waive && $participant->waiver == ParticipantWaiverEnum::Partner)) { // Check if any extra payment is expected or not for the transfer to be completed. If the participant has not paid, then no extra payment is expected as they will be transferred with no invoice item created. As such, they would have to pay the full amount for the new event.

                        if ($user->email == $participant->user->email) { // When auth user is participant - Check if a custom transfer fee was set by the admin for transfer of the given participant
                            $transferFee = $participant->activities()->InLog(ActivityLogNameEnum::Transferring->value)->where('properties->transfer_fee', '>', -1)->first()?->properties['transfer_fee'];
                            $cancelDifference = $participant->activities()->InLog(ActivityLogNameEnum::Transferring->value)->where('properties->cancel_difference', true)->first()?->properties['cancel_difference'];
                        } else { // When auth user is admin
                            $transferFee = request()->custom_transfer_fee;
                            $cancelDifference = request()->cancel_difference;
                        }

                        $settingCustomField = getSettingCustomField(SettingCustomFieldKeyEnum::ParticipantTransferFee);

                        if (is_null($transferFee)) { // If the transfer fee is not set, then use the custom transfer fee set by the admin
                            $transferFee = (float)$settingCustomField->value;
                        } else {
                            $transferFee = $transferFee;
                        }

                        if ($cancelDifference) { // Cancel difference when computing amount to be paid for the transfer
                            $difference = $eec->userRegistrationFee($participant->user) - $oldEec->userRegistrationFee($participant->user);
                            $total = $transferFee;
                            $data['invoice_price'] = $total; // Only set this when compute_invoice_price is false
                            $formattedDifference = FormatNumber::formatWithCurrency(($difference));
            
                        } else {
                            $difference = $eec->userRegistrationFee($participant->user) - $oldEec->userRegistrationFee($participant->user);
                            $total = $difference + $transferFee;
                            $formattedDifference = FormatNumber::formatWithCurrency(($difference));
                        }

                        $formattedTotal = FormatNumber::formatWithCurrency($total);
                        $formattedTransferFee = FormatNumber::formatWithCurrency($transferFee);

                        // Update the registration fee to that for the user
                        $eec['registration_fee'] = $eec->userRegistrationFee($participant->user);
                        $oldEec['registration_fee'] = $oldEec->userRegistrationFee($participant->user);

                        if ($total > 0) { // if the total is positive, then payment is expected for the transfer to proceed
                            return [
                                'payment_required' => true, // Payment is required to complete the transfer
                                'total' => $total,
                                'transfer_fee' => $transferFee,
                                'difference' => $difference ?? null,
                                'formatted_total' => $formattedTotal,
                                'formatted_difference' => $formattedDifference ?? null,
                                'formatted_transfer_fee' => $formattedTransferFee,
                                'old_eec' => $oldEec,
                                'new_eec' => $eec,
                                'setting_custom_field' => $settingCustomField ?? null,
                                'message' => "The $participantOrEntry can be transfered and payment is expected!",
                                ...$data
                            ];
                        } else {
                            if ($total < 0 && ($participant->payment_status->value == ParticipantPaymentStatusEnum::Paid->value || ($participant->waive && $participant->waiver == ParticipantWaiverEnum::Partner))) {
                                $balance = FormatNumber::formatWithCurrency($total);
                               // $newBalance = abs(FormatNumber::formatWithCurrency($total));

                                if (AccountType::isParticipant()) {
                                    $message = "Your entry can be transferred. An outstanding balance of $balance will be credited to your wallet.";
                                } else {
                                    $message = "The participant can be transferred. An outstanding balance of $balance will be credited to their wallet.";
                                }
                            }
                        }
                    }
                } else {
                    throw new EventEventCategoryException(
                        $hasAvailablePlaces->message,
                        406
                    );
                }
            } else {                
                if (str_contains($regActive->message, 'estimated')) {
                    throw new EventEventCategoryException(
                        "The event you are trying to transfer the $participantOrEntry to is an estimated event.",
                        406
                    );
                } else {
                    throw new EventEventCategoryException(
                        "The event you are trying to transfer the $participantOrEntry to is not active.",
                        406
                    );
                }
            }
        } else {
            $participantsOrEntries = ucfirst(ReplaceTextHelper::participantOrEntry(2));

            if (str_contains($regActive->message, 'estimated')) {
                throw new EventEventCategoryException(
                    "$participantsOrEntries for estimated events cannot be transferred.",
                    406
                );
            } else {
                throw new EventEventCategoryException(
                    "$participantsOrEntries for events whose registrations are no longer active cannot be transferred.",
                    406
                );
            }
        }

        return [
            'payment_required' => false, // Payment is not required to complete the transfer. The old event fee can either cover the cost of the new event + the transfer fee or the old event was not paid for.
            'transfer_fee' => $transferFee ?? 0,
            'difference' => $difference ?? null,
            'total' => $total ?? null,
            'formatted_difference' => $formattedDifference ?? null,
            'formatted_total' => $formattedTotal ?? null,
            'formatted_transfer_fee' => $formattedTransferFee ?? null,
            'old_eec' => $oldEec,
            'new_eec' => $eec,
            'setting_custom_field' => $settingCustomField ?? null,
            'message' => $message ?? "The $participantOrEntry can be transferred.",
            ...$data
        ];
    }

    /**
     * Process the transfer
     *
     * @param  Participant $participant
     * @param  EventEventCategory $eec
     * @param  User $user
     * @param  array $transactionDetail
     * @return array
     */
    public static function processTransfer(Participant $participant, EventEventCategory $eec, array $transactionDetail): array
    {
        $total = $transactionDetail['total'] ?? null;
        $oldEec = $participant->eventEventCategory;
        $user = $participant->user;

        try {
            DB::beginTransaction();

            $newParticipant = $participant->duplicate();

            $newParticipant->eventEventCategory()->associate($eec);
            $newParticipant->added_via = ParticipantAddedViaEnum::Transfer;
            $newParticipant->event_page_id = null;
            $newParticipant->save();

            $invoiceItem = $participant->invoiceItem;

            if (($invoiceItem && $invoiceItem->status == InvoiceItemStatusEnum::Paid) || ($participant->waive && $participant->waiver == ParticipantWaiverEnum::Partner)) {
                if ($invoiceItem) {
                    if ($invoiceItem->status == InvoiceItemStatusEnum::Paid) {
                        $invoiceItem->status = InvoiceItemStatusEnum::Transferred;
                        $invoiceItem->saveQuietly();

                        $invoice = $invoiceItem->invoice;

                        if ($invoice->invoiceItems()->where('status', InvoiceItemStatusEnum::Transferred)->count() == $invoice->invoiceItems()->count()) { // if all the invoice items have been transferred, then we need to update the invoice status to transferred
                            $invoice->status = InvoiceStatusEnum::Transferred;
                            $invoice->state = InvoiceStateEnum::Complete;
                        } else {
                            $invoice->state = InvoiceStateEnum::Partial;
                        }

                        $invoice->save();
                    } else if ($invoiceItem->status == InvoiceItemStatusEnum::Transferred) {
                        Log::channel(static::getSite()?->code . 'adminanddeveloper')->info('Participant Transfer Exception. Trying to transfer a participant with invoice item status of transferred ' . $invoiceItem);
                        throw new Exception('Participant Transfer Exception. Trying to transfer a participant with invoice item status of transferred');
                    }
                } else if ($participant->waive && $participant->waiver == ParticipantWaiverEnum::Partner) {
                    $newParticipant->waive = null;
                    $newParticipant->waiver = null;
                    $newParticipant->save();
                }

                if ($total && $total < 0) { // if the total is negative,
                    $data['description'] = 'Account credited due to participant transfer (Surplus)';

                    $user->participantProfile->creditAccount([
                        'type' => AccountTypeEnum::Infinite,
                        'balance' => abs($total),
                        'user' => $user
                    ], $data);
                }

                $invoice = static::recordTransactions($participant, $newParticipant, $eec, $oldEec, $transactionDetail, $total);

                $logProperties = ['invoice' => $invoice->ref];

                if ($participant->invoiceItem?->invoice) { // Generate a new invoice pdf for the former registration since the status was changed
                    dispatch(new GenerateInvoicePdfJob($participant->invoiceItem->invoice, true));
                }
            }

            $participant->update([ // Update old participant status
                'status' => ParticipantStatusEnum::Transferred,
            ]);

            // Update new participant status
            static::updateParticipantStatus($newParticipant);

            $newParticipant->logCustom(
                ActivityLogNameEnum::Transferred,
                null,
                $logProperties ?? [],
                $participant
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info('Participant Transfer Exception. ' . $e->getMessage());
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info($e);
            Log::debug($e);
            throw new Exception('Participant Transfer Exception. ' . $e->getMessage());
        }

        $newParticipant->load('invoiceItem.invoice.upload');

        try {
            Mail::site()->send(new ParticipantCompletedTransferMail($participant, $newParticipant, $total, clientSite()));
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - Process Transfer");
            Log::channel(static::getSite()?->code . 'mailexception')->info($e);
            dispatch(new ResendEmailJob(new ParticipantCompletedTransferMail($participant, $newParticipant, $total, clientSite()), clientSite()));
        } catch (\Exception $e) {
            Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - Process Transfer");
            Log::channel(static::getSite()?->code . 'mailexception')->info($e);
            dispatch(new ResendEmailJob(new ParticipantCompletedTransferMail($participant, $newParticipant, $total, clientSite()), clientSite()));
        }

        $participantOrEntry = ReplaceTextHelper::participantOrEntry();

        return [
            'status' => true,
            'old_participant' => $participant,
            'new_participant' => $newParticipant,
            'message' => "The $participantOrEntry has been successfully transferred!"
        ];
    }

    /**
     * Record transactions
     *
     * @param  Participant        $participant
     * @param  Participant        $newParticipant
     * @param  EventEventCategory $eec
     * @param  EventEventCategory $oldEec
     * @param  array              $transactionDetail
     * @param  mixed              $total
     * @return Invoice
     */
    public static function recordTransactions(Participant $participant, Participant $newParticipant, EventEventCategory $eec, $oldEec, array $transactionDetail, $total): Invoice
    {
        $transactionAmount = $total;
        $transferFee = $transactionDetail['transfer_fee'];
        $oldParticipantRegFee = $oldEec->userRegistrationFee($participant->user);
        $newParticipantRegFee = $eec->userRegistrationFee($newParticipant->user);

        $invoicePrice = $transactionDetail['invoice_price'] ?? ($newParticipantRegFee - ($oldParticipantRegFee + $transferFee));
        $computeInvoicePrice = isset($transactionDetail['invoice_price']) ? false : true;

        // Create the invoice
        $invoice = new Invoice();
        $invoice->forceFill([
            'ref' => Str::orderedUuid()->toString(),
            'site_id' => clientSiteId(),
            'invoiceable_id' => $newParticipant->user_id,
            'invoiceable_type' => User::class,
            'name' => $invoiceName = Invoice::getFormattedName(InvoiceItemTypeEnum::ParticipantTransferNewEvent, $newParticipant->charity, $newParticipant),
            'issue_date' => Carbon::now(),
            'due_date' => Carbon::now(),
            'price' => $invoicePrice,
            'compute' => $computeInvoicePrice,
            'status' => InvoiceStatusEnum::Paid,
            'state' => InvoiceStateEnum::Complete,
            'send_on' => Carbon::now(),
            'description' => 'Transfer of participant from ' . $oldEec->event->formattedName . '(' . $oldEec->eventCategory->name . ') to ' . $eec->event->formattedName . '(' . $eec->eventCategory->name . ')'
        ]);

        $invoice->saveQuietly();

        // Create the invoice items
        $invoiceItem = new InvoiceItem();
        $invoiceItem->forceFill([
            'ref' => Str::orderedUuid()->toString(),
            'invoice_id' => $invoice->id,
            'invoice_itemable_id' => $newParticipant->id,
            'invoice_itemable_type' => Participant::class,
            'price' => $newParticipantRegFee,
            'type' => InvoiceItemTypeEnum::ParticipantTransferNewEvent,
            'status' => InvoiceItemStatusEnum::Paid
        ]);

        $invoiceItem->saveQuietly();

        // Create the invoice items
        $invoiceItem = new InvoiceItem();
        $invoiceItem->forceFill([
            'ref' => Str::orderedUuid()->toString(),
            'invoice_id' => $invoice->id,
            'invoice_itemable_id' => $participant->id,
            'invoice_itemable_type' => Participant::class,
            'price' => $oldParticipantRegFee,
            'type' => InvoiceItemTypeEnum::ParticipantTransferOldEvent,
            'status' => InvoiceItemStatusEnum::Paid
        ]);

        $invoiceItem->saveQuietly();

        // Create the invoice items
        $invoiceItem = new InvoiceItem();
        $invoiceItem->forceFill([
            'ref' => Str::orderedUuid()->toString(),
            'invoice_id' => $invoice->id,
            'invoice_itemable_id' => $transactionDetail['setting_custom_field']->id,
            'invoice_itemable_type' => SettingCustomField::class,
            'type' => InvoiceItemTypeEnum::ParticipantTransferFee,
            'price' => $transferFee,
            'status' => InvoiceItemStatusEnum::Paid
        ]);

        $invoiceItem->saveQuietly();

        // Update the invoice
        Invoice::updatePoNumberField($invoice);
        Invoice::updatePriceField($invoice);

        $transaction = Transaction::create([
            'site_id' => clientSiteId(),
            'user_id' => $newParticipant->user->id,
            'ongoing_external_transaction_id' => $transactionDetail['ongoingExternalTransaction']?->id ?? null,
            'transactionable_id' => $invoice->id,
            'transactionable_type' => Invoice::class,
            'name' => $invoiceName,
            'amount' => $transactionAmount,
            'fee' => $transferFee,
            'status' => TransactionStatusEnum::Completed,
            'type' => TransactionTypeEnum::Transfer,
            'payment_method' => isset($transactionDetail['paymentMethod']) && $transactionDetail['paymentMethod'] ? TransactionPaymentMethodEnum::tryFrom($transactionDetail['paymentMethod']) : null,
            'description' => 'Payment for participant transfer'
        ]);

        if ($transaction->ongoing_external_transaction_id && isset($transactionDetail['charge'])) {
            $transaction->externalTransaction()->create([ // Create an external transaction for the payment
                'payment_intent_id' => $transactionDetail['ongoingExternalTransaction']?->payment_intent_id,
                'charge_id' => $transactionDetail['charge']->id,
                'payload' => $transactionDetail['ongoingExternalTransaction']->payload
            ]);
        }

        return $invoice;
    }
}
