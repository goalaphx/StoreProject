<?php
namespace App\Service;

use Twilio\Rest\Client;

class TwilioService
{
    public function sendWhatsappOTP($recipientPhoneNumbers, $WhatsappOTPCode)
    {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'];
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'];
        $twilioWhatsappNumber = $_ENV['TWILIO_WHATSAPP_NUMBER'];
        $client = new Client($accountSid, $authToken);

        if (is_array($recipientPhoneNumbers)) {
            foreach ($recipientPhoneNumbers as $recipientPhoneNumber) {
                $this->sendMessageToRecipient($client, $recipientPhoneNumber, $twilioWhatsappNumber, $WhatsappOTPCode);
            }
        } else {
            $this->sendMessageToRecipient($client, $recipientPhoneNumbers, $twilioWhatsappNumber, $WhatsappOTPCode);
        }
    }

    private function sendMessageToRecipient($client, $recipientPhoneNumber, $twilioWhatsappNumber, $WhatsappOTPCode)
    {
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
