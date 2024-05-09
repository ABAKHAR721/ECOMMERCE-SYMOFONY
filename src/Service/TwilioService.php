<?php

namespace App\Service;

use Twilio\Rest\Client;

class TwilioService
{
    public function sendWhatsappOTP($recipientPhoneNumber, $WhatsappOTPCode)
    {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'];
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'];
        $twilioWhatsappNumber = $_ENV['TWILIO_WHATSAPP_NUMBER'];
        $client = new Client($accountSid, $authToken);
        $message = $client->messages->create(
            "whatsapp:{$recipientPhoneNumber}",
            [
                'from' => "whatsapp:{$twilioWhatsappNumber}",
                'body' => "Your OTP code is: {$WhatsappOTPCode}.",
            ]
        );
        return $message->sid;
    }
}