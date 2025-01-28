<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address & Other Addreses
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    | Addresses to whom some special emails are to be sent are defined here too.
    |
    */

    'from' => [
        'address' => env('RUNTHROUGH_MAIL_FROM_ADDRESS', 'mailer@runthrough.co.uk'),
        'name' => env('RUNTHROUGH_MAIL_FROM_NAME', 'RunThrough'),
    ],

    'noreply' => [
        'address' => env('RUNTHROUGH_MAIL_NOREPLY_FROM_ADDRESS', 'noreply@runthrough.co.uk'),
        'name' => env('RUNTHROUGH_MAIL_NOREPLY_FROM_NAME', 'RunThrough'),
    ],

    'administrator' => [
        'address' => env('RUNTHROUGH_MAIL_ADMINISTRATOR_FROM_ADDRESS', 'matt@runthrough.co.uk'),
        'name' => env('RUNTHROUGH_MAIL_ADMINISTRATOR_FROM_NAME', 'Matt Wood'),
    ],
    'devloperGroup' => [
        'address' =>env('RUNTHROUGH_MAIL_DEVELOPER_ADDRESS', 'hello@example.com'),
    ],

    /*'cp_email' => [
        'address' => env('RUNTHROUGH_MAIL_CP_FROM_ADDRESS', 'charities@sportforcharity.com'),
        'name' => env('RUNTHROUGH_MAIL_CP_FROM_NAME', 'Charities - Sport for Charity'),
    ],

    'bfc_email' => [
        'address' => env('RUNTHROUGH_MAIL_BFC_FROM_ADDRESS', 'business@sportforcharity.com'),
        'name' => env('RUNTHROUGH_MAIL_BFC_FROM_NAME', 'Business - Sport for Charity'),
    ],

    'mu_email' => [
        'address' => env('RUNTHROUGH_MAIL_MU_FROM_ADDRESS', 'monthlyupdate@sportforcharity.com'),
        'name' => env('RUNTHROUGH_MAIL_MU_FROM_NAME', 'Monthly Update - Sport for Charity'),
    ],

    'rr_email' => [
        'address' => env('RUNTHROUGH_MAIL_RR_FROM_ADDRESS', 'registrationreminder@sportforcharity.com'),
        'name' => env('RUNTHROUGH_MAIL_RR_FROM_NAME', 'Registration Reminder - Sport for Charity'),
    ],

    'rrs_email' => [
        'address' => env('RUNTHROUGH_MAIL_RRs_FROM_ADDRESS', 'portal@runningrankings.com'),
        'name' => env('RUNTHROUGH_MAIL_RRs_FROM_NAME', 'Running Rankings'),
    ],

    'rrs_support_email' => [
        'address' => env('RUNTHROUGH_MAIL_RRs_SUPPORT_ADDRESS', 'support@runningrankings.com'),
        'name' => env('RUNTHROUGH_MAIL_RRs_SUPPORT_NAME', 'Running Rankings Support Name'),
    ],

    'rrs_admin_email' => [
        'address' => env('RUNTHROUGH_MAIL_RRs_ADMIN_ADDRESS', 'admin@runningrankings.com'),
        'name' => env('RUNTHROUGH_MAIL_RRs_ADMIN_NAME', 'Running Rankings'),
    ],

    'vmm_email' => [
        'address' => env('RUNTHROUGH_MAIL_VMM_ADDRESS', 'portal@virtualmarathonseries.com'),
        'name' => env('RUNTHROUGH_MAIL_VMM_NAME', 'Virtual Marathon Series'),
    ],

    'vmm_reg_email' => [
        'address' => env('RUNTHROUGH_MAIL_VMM_REG_FROM_ADDRESS', 'registrations@virtualmarathonseries.com'),
        'name' => env('RUNTHROUGH_MAIL_VMM_REG_FROM_NAME', 'Virtual Marathon Series Registrations'),
    ],

    'ldt_email' => [
        'address' => env('RUNTHROUGH_MAIL_LDT_FROM_ADDRESS', 'archie@letsdothis.com'),
        'name' => env('LDT_NAME', 'Let\'s Do This Name'),
    ],

    'ldt_em_email' => [
        'address' => env('RUNTHROUGH_MAIL_LDT_EM_FROM_ADDRESS', 'constantine@letsdothis.com'),
        'name' => env('RUNTHROUGH_MAIL_LDT_EM_FROM_NAME', 'Let\'s Do This Event Manager'),
    ],

    'info_email' => [
        'address' => env('RUNTHROUGH_MAIL_INFO_ADDRESS', 'info@runthrough.com'),
        'name' => env('RUNTHROUGH_MAIL_INFO_NAME', 'Running Rankings'),
    ],

    'vmm_email_2' => [
        'address' => env('RUNTHROUGH_MAIL_VMM_ADDRESS', 'sfc@virtualmarathonseries.com'),
        'name' => env('RUNTHROUGH_MAIL_VMM_NAME', 'Virtual Marathon Series'),
    ],

    'default_charity_email' => [
        'address' => env('RUNTHROUGH_MAIL_DEFAULT_CHARITY_FROM_ADDRESS', 'default@charity.com'),
        'name' => env('RUNTHROUGH_MAIL_DEFAULT_CHARITY_FROM_NAME', 'Default Charity'),
    ],

    'vmm_support_email' => [
        'address' => env('RUNTHROUGH_MAIL_VMM_SUPPORT_FROM_ADDRESS', 'support@virtualmarathonseries.com'),
        'name' => env('RUNTHROUGH_MAIL_VMM_SUPPORT_FROM_NAME', 'Virtual Marathon Series'),
    ],*/

];
