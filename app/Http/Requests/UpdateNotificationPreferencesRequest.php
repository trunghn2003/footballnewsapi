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
            'global_settings' => 'sometimes|array',
            'global_settings.team_news' => 'boolean',
            'global_settings.match_reminders' => 'boolean',
            'global_settings.competition_news' => 'boolean',
            'global_settings.match_score' => 'boolean',

            'team_settings' => 'sometimes|array',
            'team_settings.*.team_id' => 'required|integer|exists:teams,id',
            'team_settings.*.team_news' => 'boolean',
            'team_settings.*.match_reminders' => 'boolean',
            'team_settings.*.match_score' => 'boolean',

            'competition_settings' => 'sometimes|array',
            'competition_settings.*.competition_id' => 'required|integer|exists:competitions,id',
            'competition_settings.*.competition_news' => 'boolean',
            'competition_settings.*.match_reminders' => 'boolean',
            'competition_settings.*.match_score' => 'boolean',
        ];
    }
}
