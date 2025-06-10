<?php declare(strict_types=1);

class Scryfall
{
    private const string BASE_URL = 'https://api.scryfall.com/cards/';
    private const string RANDOM_CARD_URL = self::BASE_URL.'random';

    private array $headers;
    private int $sleepTime;

    public function __construct(int $sleepTime = 100000, array $headers = null) {
        if (isset($headers)) {
            $this->headers = $headers;
        } else {
            $this->headers = ['Accept: application/json', 'User-Agent: parallel-php-workshop'];
        }

        // From Scryfall API docs
        // We kindly ask that you insert 50 – 100 milliseconds of delay between the requests
        if ($sleepTime <= 100000) {
            $this->sleepTime = 100000;
        } else {
            $this->sleepTime = $sleepTime;
        }

    }

    private function fetch(string $url): string
    {
        usleep($this->sleepTime);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers); // Set headers
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error);
        }

        return $response;
    }

    private static function parse_card(array $card): array
    {
        $parsed_card = [];
        $parsed_card['scryfall_id'] =  $card['id'] ?? '';
        $parsed_card['name'] =  $card['name'] ?? '';
        $parsed_card['set_code'] =  $card['set'] ?? '';
        $parsed_card['collector_number'] =  $card['collector_number'] ?? '';

        return $parsed_card;
    }

    public function fetch_random(): array
    {
        $response = $this->fetch(self::RANDOM_CARD_URL);
        $raw_card = json_decode($response, true);

        return self::parse_card($raw_card);
    }
}