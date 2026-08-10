<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Unique;

class UpdateInformationModeratorRequest extends StoreInformationModeratorRequest
{
    /** Left blank, the account keeps the password it already has. */
    protected function passwordPresence(): array
    {
        return ['nullable'];
    }

    protected function usernameIsUnique(): Unique
    {
        return parent::usernameIsUnique()->ignore($this->route('moderator'));
    }

    protected function emailIsUnique(): Unique
    {
        return parent::emailIsUnique()->ignore($this->route('moderator'));
    }
}
