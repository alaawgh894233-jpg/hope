<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseNotificationService
{
    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        $messaging = Firebase::messaging();

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        return $messaging->send($message);
    }

    public function sendToMultiple(array $tokens, string $title, string $body, array $data = [])
    {
        $messaging = Firebase::messaging();

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        return $messaging->sendMulticast($message, $tokens);
    }
}
