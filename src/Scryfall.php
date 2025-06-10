<?php declare(strict_types=1);

class Scryfall
{
    private const string BASE_URL = 'https://api.scryfall.com/cards/';
    private const string RANDOM_CARD_URL = self::BASE_URL.'random';

    private array $headers;

    public function __construct(array $headers = null) {
        if (isset($headers)) {
            $this->headers = $headers;
        } else {
            $this->headers = ['Accept: application/json', 'User-Agent: parallel-php-workshop'];
        }
    }

    public function fetch_random(): array
    {
        $ch = curl_init(self::RANDOM_CARD_URL);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers); // Set headers
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error);
        }

        // Close cURL session
        curl_close($ch);

        // Decode the JSON response and return as an array
        return json_decode($response, true);
    }
}