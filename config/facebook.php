<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facebook Pixel ID
    |--------------------------------------------------------------------------
    |
    | Your Facebook Pixel ID from Events Manager
    |
    */
    'pixel_id' => env('FACEBOOK_PIXEL_ID', '1146830717394931'),

    /*
    |--------------------------------------------------------------------------
    | Facebook Conversions API Access Token
    |--------------------------------------------------------------------------
    |
    | Your Facebook Conversions API access token
    |
    */
    'access_token' => env('FACEBOOK_CAPI_ACCESS_TOKEN', 'EAAZAgRTwhiNYBPnDaoeqRGxa54skl1obL7RMS1JzhuTg7ISMPsa4zQoFxOtYaZB2O57lekXKgSWJWi6YLTwddeAlmkm5ZAvmKyJv34U7ZCvjcj8LpZCZCeUPrEVNg14WxQdgavxCMxTUZCpk2U6tickz0WZAgQulTDYH3e1e6woNZCoyc8BP5CZAzJp9oKJUWZAkBbpSgZDZD'),

    /*
    |--------------------------------------------------------------------------
    | Facebook API Version
    |--------------------------------------------------------------------------
    |
    | Facebook Graph API version to use
    |
    */
    'api_version' => env('FACEBOOK_API_VERSION', 'v21.0'),

    /*
    |--------------------------------------------------------------------------
    | Test Event Code
    |--------------------------------------------------------------------------
    |
    | Optional test event code for testing events in Facebook Events Manager
    | Remove or leave empty for production
    |
    */
    'test_event_code' => env('FACEBOOK_TEST_EVENT_CODE', null),

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Facebook Conversions API
    |--------------------------------------------------------------------------
    |
    | Set to false to disable all Facebook tracking
    |
    */
    'enabled' => env('FACEBOOK_CAPI_ENABLED', true),

];
