<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | This value is your Gemini API key. This will be used to authenticate
    | with the Gemini API. You can find your API key in the Google AI Studio.
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Base URL
    |--------------------------------------------------------------------------
    |
    | If you need a specific base URL for the Gemini API, you can provide it here.
    | Otherwise, leave empty to use the default value.
    */
    'base_url' => env('GEMINI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | This is the default model that will be used when generating content.
    | You can override this by specifying a different model in your code.
    |
    */
    'default_model' => 'gemini-pro',

    /*
    |--------------------------------------------------------------------------
    | Default Generation Config
    |--------------------------------------------------------------------------
    |
    | These are the default generation parameters that will be used when
    | generating content. You can override these by specifying different
    | parameters in your code.
    |
    */
    'generation_config' => [
        'temperature' => 0.7,
        'top_p' => 0.8,
        'top_k' => 40,
        'max_output_tokens' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Safety Settings
    |--------------------------------------------------------------------------
    |
    | These are the default safety settings that will be used when
    | generating content. You can override these by specifying different
    | settings in your code.
    |
    */
    'safety_settings' => [
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
    ],
];
