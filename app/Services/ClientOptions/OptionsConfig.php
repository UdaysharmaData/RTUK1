<?php

namespace App\Services\ClientOptions;

use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\UploadsListOrderByFieldsEnum;
use App\Services\ClientOptions\CharityCategoryOptions;
use App\Services\ClientOptions\CityOptions;
use App\Services\ClientOptions\VenueOptions;
use App\Services\ClientOptions\EntryOptions;
use App\Services\ClientOptions\EventOptions;
use App\Services\ClientOptions\RegionOptions;
use App\Services\ClientOptions\CharityOptions;
use App\Services\ClientOptions\InvoiceOptions;
use App\Services\ClientOptions\EnquiryOptions;
use App\Services\ClientOptions\Traits\Options;
use App\Services\ClientOptions\PartnerOptions;
use App\Services\ClientOptions\ExperienceOptions;
use App\Services\ClientOptions\ParticipantOptions;
use App\Services\ClientOptions\EventCategoryOptions;
use App\Services\ClientOptions\PartnerChannelOptions;
use App\Services\ClientOptions\ExternalEnquiryOptions;
use App\Services\ClientOptions\EventPropertyServiceOptions;
use App\Enums\ParticipantAddedViaEnum;

class OptionsConfig
{
    use Options;

    /**
     * Available options when dealing with user related fields [eg., in dropdowns, selects, etc.]
     *
     * @param string|null $type
     * @return array
     */
    public function all(string $type = null): array
    {
        switch ($type) {
            case 'users':
                $data = [
                    'roles' => $this->getRoleOptions(),
                    'tshirt_sizes' => $this->getTshirtOptions(),
                    'genders' => $this->getGenderOptions(),
                    'socials' => $this->getSocialsOptions(),
                    'reg_years' => $this->getStatsYearFilterOptions(),
                    'order_by' => $this->getUsersOrderByOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'time_periods' => $this->getTimePeriodReferenceOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'status' => $this->getUserSiteStatusOptions(),
                    'action' => $this->getUserSiteActionOptions(),
                    'verification' => $this->getUserVerificationStatusOptions(),
                ];
                break;
            case 'roles':
                $data = [
                    'order_by' => $this->getRolesOrderByOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                ];
                break;
            case 'analytics':
                $data = [
                    'time_periods' => $this->getTimePeriodReferenceOptions(),
                    'interaction_types' => $this->getInteractionTypes()
                ];
                break;
            case 'pages':
                $data =  [
                    'status' => $this->getPagesStatusOptions(),
                    'order_by' => $this->getPagesOrderByOptions(),
                    'year' => $this->getPagesYearFilterOptions(),
                ];
                break;
            case 'combinations':
                $data = [
                    'order_by' => $this->getCombinationsOrderByOptions(),
                    'year' => $this->getCombinationsYearFilterOptions(),
                    //'event_categories' => []
                ];
                break;
            case 'general':
                $data = [
                    'faqs' => $this->getListWithFaqsOptions(),
                    'period' => $this->getTimePeriodReferenceOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                ];
                break;
            case 'participants':
                $data =  [
                    'genders' => $this->getGenderOptions(),
                    'categories' => EventCategoryOptions::getRefOptions(),
                    'statuses' => ParticipantOptions::getStatusOptions(),
                    'channels' => ParticipantOptions::getChannelOptions(),
                    'states' => ParticipantOptions::getStateOptions(),
                    'years' => ParticipantOptions::getYearOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'time_periods' => $this->getTimePeriodReferenceOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_by' => ParticipantOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'payment_statuses' => ParticipantPaymentStatusEnum::_options(),
                    'waives' => ParticipantWaiveEnum::_options(),
                    'waivers' => ParticipantWaiverEnum::_options(),
                    'via' => ParticipantAddedViaEnum::_options(),
                ];
                break;
            case 'entries':
                $data = [
                    'categories' => EventCategoryOptions::getRefOptions('entries'),
                    'statuses' => ParticipantOptions::getStatusOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'years' => ParticipantOptions::getYearOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_by' => EntryOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'invoices':
                $data =  [
                    'statuses' => InvoiceOptions::getStatusOptions(),
                    'types' => InvoiceOptions::getInvoiceItemTypeOptions(),
                    'held' => InvoiceOptions::getHeldOptions(),
                    'years' => InvoiceOptions::getYearOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'invoice_items_years' => InvoiceOptions::getInvoiceItemYearOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_by' => InvoiceOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'event_categories':
                $data = [
                    'years' => EventCategoryOptions::getYearOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'visibilities' => EventCategoryOptions::getVisibilityOptions(),
                    'statuses' => EventCategoryOptions::getStatusOptions(),
                    'faqs' => $this->getListWithFaqsOptions(),
                    'medals' => $this->getListWithMedalsOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => EventCategoryOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'events':
                $data = [
                    'order_by' => EventOptions::getOrderByOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'time_periods' => $this->getTimePeriodReferenceOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'years' => EventOptions::getYearOptions(),
                    'faqs' => $this->getListWithFaqsOptions(),
                    'medals' => $this->getListWithMedalsOptions(),
                    'states' => EventOptions::getStateOptions(),
                    'types' => EventOptions::getTypeOptions(),
                    'partner_event' => $this->getYesNoOptions(),
                    'has_third_party_set_up' => $this->getYesNoOptions(),
                ];
                break;
            case 'enquiries':
                $data = [
                    'order_by' => EnquiryOptions::getOrderByOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'years' => EnquiryOptions::getYearOptions(),
                    'actions' => EnquiryOptions::getActionOptions(),
                    'statuses' => EnquiryOptions::getStatusOptions(),
                    'contacted' => EnquiryOptions::getContactedOptions(),
                    'converted' => EnquiryOptions::getConvertedOptions(),
                ];
                break;
            case 'external_enquiries':
                $data = [
                    'order_by' => ExternalEnquiryOptions::getOrderByOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'periods' => $this->getTimePeriodReferenceOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'years' => ExternalEnquiryOptions::getYearOptions(),
                    'statuses' => ExternalEnquiryOptions::getStatusOptions(),
                    'contacted' => ExternalEnquiryOptions::getContactedOptions(),
                    'converted' => ExternalEnquiryOptions::getConvertedOptions(),
                ];
                break;
            case 'partners':
                $data = [
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => PartnerOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'partner_channels':
                $data = [
                    'order_by' => PartnerChannelOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'regions':
                $data = [
                    'years' => RegionOptions::getYearOptions(),
                    'faqs' => $this->getListWithFaqsOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => RegionOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'cities':
                $data = [
                    'years' => CityOptions::getYearOptions(),
                    'faqs' => $this->getListWithFaqsOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => EventPropertyServiceOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'venues':
                $data = [
                    'years' => VenueOptions::getYearOptions(),
                    'faqs' => $this->getListWithFaqsOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'order_by' => EventPropertyServiceOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'series':
                $data = [
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => EventPropertyServiceOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'sponsors':
                $data = [
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => EventPropertyServiceOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'medals':
                $data = [
                    'years' => MedalOptions::getYearOptions(),
                    'types' => MedalOptions::getMedalTypeOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => MedalOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'partner_events':
                $data = [
                    'archived' => $this->getYesNoOptions(),
                    'months' => $this->getMonthFilterOptions(),
                    'years' => PartnerEventOptions::getYearOptions()
                ];
                break;
            case 'experiences':
                $data = [
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'order_by' => ExperienceOptions::getOrderByOptions(),
                    'order_direction' => $this->getOrderByDirectionOptions(),
                    'years' => ExperienceOptions::getYearOptions(),
                    'drafted' => $this->getListDraftedFilterOptions(),
                ];
                break;
            case 'charities':
                $data = [
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'years' => CharityOptions::getYearOptions(),
                ];
                break;
            case 'charity_categories':
                $data = [
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'categories' => CharityCategoryOptions::getRefOptions(),
                ];
            case 'redirects':
                $data = [
                    'type' => $this->getRedirectsTypeOptions(),
                    'soft_delete' => $this->getRedirectSoftDeleteStatusOptions(),
                    'hard_delete' => $this->getRedirectHardDeleteStatusOptions(),
                    'order_by' => $this->getRedirectOrderByOptions()
                ];
                break;
            case 'finances':
                $data = [
                    'accounts' => FinanceOptions::getAccountOptions(),
                    'deleted' => $this->getListDeletedFilterOptions(),
                    'account_statuses' => FinanceOptions::getAccountStatusOptions(),
                    'account_types' => FinanceOptions::getAccountTypeOptions(),
                    'internal_transactiFons_order_by' => FinanceOptions::getInternalTransactionsOrderByOptions(),
                    'internal_transactions_order_direction' => $this->getOrderByDirectionOptions(),
                ];
                break;
            case 'uploads':
                $data = [
                    'types' => $this->getUploadsTypeOptions(),
                    'years' => UploadOptions::getYearOptions(),
                    'order_by' => UploadsListOrderByFieldsEnum::_options(),
                    'order_direction' => $this->getOrderByDirectionOptions()
                ];
                break;
            case 'audiences':
                $data = [
                    'source' => AudienceOptions::getSourceOptions(),
                    'author' => AudienceOptions::getAuthorOptions(),
                    'order_by' => AudienceOptions::getOrderByOptions(),
                ];
                break;
            default:
                $data = [];
                break;
        }

        return $data;
    }

    /**
     * @param string|null $type
     * @param array $options
     * @return array
     */
    public function only(string $type = null, array $options = []): array
    {
        if ($type && $data = $this->all($type)) {
            $filtered = [];
            foreach ($data as $key => $value) {
                if (in_array($key, $options)) $filtered[$key] = $value;
            }
            return $filtered;
        }
        return [];
    }
}
