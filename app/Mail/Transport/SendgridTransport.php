<?php

namespace App\Mail\Transport;

use GuzzleHttp\Client;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class SendgridTransport extends AbstractTransport
{
    public function __construct(
        private string $apiKey,
        private Client $client
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $toRecipients = array_map(
            fn($a) => array_filter([
                'email' => $a->getAddress(), 
                'name' => $this->sanitizeUtf8($a->getName())
            ]),
            $email->getTo()
        );

        $payload = [
            'personalizations' => [['to' => array_values($toRecipients)]],
            'from' => array_filter([
                'email' => $email->getFrom()[0]->getAddress(),
                'name'  => $this->sanitizeUtf8($email->getFrom()[0]->getName()),
            ]),
            'subject' => $this->sanitizeUtf8($email->getSubject()),
            'content' => [],
        ];

        if ($cc = $email->getCc()) {
            $payload['personalizations'][0]['cc'] = array_map(
                fn($a) => array_filter([
                    'email' => $a->getAddress(), 
                    'name' => $this->sanitizeUtf8($a->getName())
                ]),
                $cc
            );
        }

        if ($bcc = $email->getBcc()) {
            $payload['personalizations'][0]['bcc'] = array_map(
                fn($a) => array_filter([
                    'email' => $a->getAddress(), 
                    'name' => $this->sanitizeUtf8($a->getName())
                ]),
                $bcc
            );
        }

        if ($text = $email->getTextBody()) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => $this->sanitizeUtf8($text)];
        }
        if ($html = $email->getHtmlBody()) {
            $payload['content'][] = ['type' => 'text/html', 'value' => $this->sanitizeUtf8($html)];
        }

        if (empty($payload['content'])) {
            $payload['content'][] = ['type' => 'text/plain', 'value' => ' '];
        }

        $this->client->post('https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => $payload,
        ]);
    }

    /**
     * Sanitize string to ensure valid UTF-8 encoding
     */
    private function sanitizeUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        // Convert to UTF-8, replacing invalid characters
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    public function __toString(): string
    {
        return 'sendgrid';
    }
}
