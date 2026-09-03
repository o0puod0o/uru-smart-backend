<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'expo_push' => [
        'endpoint' => env('EXPO_PUSH_ENDPOINT', 'https://exp.host/--/api/v2/push/send'),
        'access_token' => env('EXPO_PUSH_ACCESS_TOKEN'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'ai' => [
        'base_url' => env('AI_BASE_URL', 'https://gen.ai.kku.ac.th/uruacth/api/v1'),
        'api_key' => env('AI_API_KEY', env('GEMINI_API_KEY')),
        'model' => env('AI_MODEL', 'claude-sonnet-5'),
        'models' => [
            ['id' => 'claude-sonnet-5', 'display_name' => 'Claude Sonnet 5', 'provider' => 'Claude', 'description' => 'Default model for URU Smart Chatbot.'],
            ['id' => 'claude-sonnet-4.6', 'display_name' => 'Claude Sonnet 4.6', 'provider' => 'Claude', 'description' => 'Claude model via URU AI Space API.'],
            ['id' => 'deepseek-v4-pro', 'display_name' => 'Deepseek V4 Pro', 'provider' => 'Deepseek', 'description' => 'Deepseek model via URU AI Space API.'],
            ['id' => 'deepseek-v4-flash', 'display_name' => 'Deepseek V4 Flash', 'provider' => 'Deepseek', 'description' => 'Deepseek fast model via URU AI Space API.'],
            ['id' => 'gemini-3.7-flash', 'display_name' => 'Gemini 3.7 Flash', 'provider' => 'Gemini', 'description' => 'Gemini model via URU AI Space API.'],
            ['id' => 'gemini-3.6-flash', 'display_name' => 'Gemini 3.6 Flash', 'provider' => 'Gemini', 'description' => 'Gemini model via URU AI Space API.'],
            ['id' => 'gemini-3.5-flash', 'display_name' => 'Gemini 3.5 Flash', 'provider' => 'Gemini', 'description' => 'Gemini model via URU AI Space API.'],
            ['id' => 'gemini-3.1-pro-preview', 'display_name' => 'Gemini 3.1 Pro Preview', 'provider' => 'Gemini', 'description' => 'Gemini preview model via URU AI Space API.'],
            ['id' => 'gemini-3.1-flash-lite', 'display_name' => 'Gemini 3.1 Flash Lite', 'provider' => 'Gemini', 'description' => 'Gemini lightweight model via URU AI Space API.'],
            ['id' => 'gemini-3.1-flash-lite-preview', 'display_name' => 'Gemini 3.1 Flash Lite Preview', 'provider' => 'Gemini', 'description' => 'Gemini lightweight preview model via URU AI Space API.'],
            ['id' => 'gemini-2.5-flash-lite', 'display_name' => 'Gemini 2.5 Flash Lite', 'provider' => 'Gemini', 'description' => 'Gemini lightweight model via URU AI Space API.'],
            ['id' => 'llama-4-maverick', 'display_name' => 'Llama 4 Maverick', 'provider' => 'Meta AI', 'description' => 'Meta AI model via URU AI Space API.'],
            ['id' => 'llama-4-scout', 'display_name' => 'Llama 4 Scout', 'provider' => 'Meta AI', 'description' => 'Meta AI model via URU AI Space API.'],
            ['id' => 'mistral-medium-3', 'display_name' => 'Mistral Medium 3', 'provider' => 'Mistral', 'description' => 'Mistral model via URU AI Space API.'],
            ['id' => 'nova-2-lite-v1', 'display_name' => 'Nova 2 Lite v1', 'provider' => 'Nova (AWS)', 'description' => 'AWS Nova model via URU AI Space API.'],
            ['id' => 'sonar-pro', 'display_name' => 'Perplexity Sonar Pro', 'provider' => 'Perplexity', 'description' => 'Perplexity Sonar Pro model.'],
            ['id' => 'gpt-5.6-terra-pro', 'display_name' => 'GPT-5.6 Terra Pro', 'provider' => 'OpenAI', 'description' => 'OpenAI model via URU AI Space API.'],
            ['id' => 'gpt-5.6-luna-pro', 'display_name' => 'GPT-5.6 Luna Pro', 'provider' => 'OpenAI', 'description' => 'OpenAI model via URU AI Space API.'],
            ['id' => 'gpt-5.4', 'display_name' => 'GPT-5.4', 'provider' => 'OpenAI', 'description' => 'OpenAI model via URU AI Space API.'],
            ['id' => 'gpt-5.4-mini', 'display_name' => 'GPT-5.4 Mini', 'provider' => 'OpenAI', 'description' => 'OpenAI compact model via URU AI Space API.'],
            ['id' => 'gpt-5.4-nano', 'display_name' => 'GPT-5.4 Nano', 'provider' => 'OpenAI', 'description' => 'OpenAI small model via URU AI Space API.'],
            ['id' => 'qwen3.7-plus', 'display_name' => 'Qwen 3.7 Plus', 'provider' => 'Qwen', 'description' => 'Qwen model via URU AI Space API.'],
            ['id' => 'qwen3.7-max', 'display_name' => 'Qwen 3.7 Max', 'provider' => 'Qwen', 'description' => 'Qwen model via URU AI Space API.'],
            ['id' => 'qwen3.5-9b', 'display_name' => 'Qwen 3.5 9B', 'provider' => 'Qwen', 'description' => 'Qwen model via URU AI Space API.'],
            ['id' => 'qwen3.6-flash', 'display_name' => 'Qwen 3.6 Flash', 'provider' => 'Qwen', 'description' => 'Qwen fast model via URU AI Space API.'],
            ['id' => 'grok-4.5', 'display_name' => 'Grok 4.5', 'provider' => 'xAI', 'description' => 'xAI model via URU AI Space API.'],
            ['id' => 'grok-4.3', 'display_name' => 'Grok 4.3', 'provider' => 'xAI', 'description' => 'xAI model via URU AI Space API.'],
        ],
    ],

    'module_routes' => [
        'legacy_urusmart' => env('URUSMART_LEGACY_MODULE_ROUTES', true),
    ],

];
