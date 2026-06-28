<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Policies\ConversationPolicy;

class AuthServiceProvider
{
    protected $policies = [
        Conversation::class => ConversationPolicy::class,
    ];
}
