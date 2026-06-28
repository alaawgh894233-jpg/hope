<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->applicant_id
            || $user->id === $conversation->company_user_id;
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->applicant_id
            || $user->id === $conversation->company_user_id;
    }

    public function close(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->company_user_id;
    }
}
