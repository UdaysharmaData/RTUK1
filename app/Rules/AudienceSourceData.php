<?php

namespace App\Rules;

use App\Enums\AudienceSourceEnum;
use Illuminate\Contracts\Validation\Rule;

class AudienceSourceData implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        protected string $source,
        protected string $message = 'Invalid source specified'
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return match ($this->source) {
            AudienceSourceEnum::Emails->value => $this->checkEmailRule($value),
            AudienceSourceEnum::MailingList->value => $this->checkMailingListRule($value),
            default => false,
        };
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @param array $value
     * @return bool
     */
    private function checkEmailRule(array $value): bool
    {
        if (! isset($value['emails'])) {
            $this->message = 'Invalid data provided for the selected source.';

            return false;
        }

        $badEmails = [];

        foreach ($value['emails'] as $key => $email) {
            if (
                (! is_string($email))
                || (! filter_var($email, FILTER_VALIDATE_EMAIL))
            ) {
                $badEmails[] = $email;
            }
        }

        if (count($badEmails) > 0) {
            $this->message = 'Invalid email(s) provided: ' . implode(', ', $badEmails);

            return false;
        }

        return true;
    }

    /**
     * @param array $value
     * @return bool
     */
    private function checkMailingListRule(array $value): bool
    {
        if (! isset($value['mailing_list'])) {
            $this->message = 'Invalid data provided for the selected source.';

            return false;
        }

        return true;
    }
}
