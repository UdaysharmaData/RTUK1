<?php

namespace App\Services\ExportManager\Formatters;

use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class UserExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $user) {
            $temp['first_name'] = $user->first_name;
            $temp['last_name'] = $user->last_name;
            $temp['email'] = $user->email;
            $temp['phone'] = $user->phone;
            $temp['username'] = $user->profile?->username;
            $temp['gender'] = $user->profile?->gender?->name;
            $temp['dob'] = $user->profile?->dob;
            $temp['address'] = $user->profile?->address;
            $temp['city'] = $user->profile?->city;
            $temp['region'] = $user->profile?->region;
            $temp['state'] = $user->profile?->state;
            $temp['postcode'] = $user->profile?->postcode;
            $temp['country'] = $user->profile?->country;
            $temp['nationality'] = $user->profile?->nationality;
            $temp['occupation'] = $user->profile?->occupation;
            $temp['passport_number'] = $user->profile?->passport_number;
            $temp['ethnicity'] = $user->profile?->ethnicity?->name;
            $temp['is_public'] = $user->profile?->is_public;
            $temp['temp_pass'] = $user->temp_pass;
            $temp['verification_token'] = $user->verification_token;
            $temp['email_verified_at'] = $user->email_verified_at;
            $temp['phone_verified_at'] = $user->phone_verified_at;
            $temp['remember_token'] = $user->remember_token;

            $data[] = $temp;
        }

        return $data;
    }
}
