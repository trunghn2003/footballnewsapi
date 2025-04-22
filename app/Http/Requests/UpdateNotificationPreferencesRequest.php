<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'team_news' => 'boolean',
            'match_reminders' => 'boolean',
            'competition_news' => 'boolean',
            'match_score' => 'boolean'
        ];
    }
}
