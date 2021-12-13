<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TwitterApiService
{
    private HttpClientInterface $client;
    private string $bearerToken;

    public function __construct(HttpClientInterface $client, string $bearerToken)
    {
        $this->client = $client;
        $this->bearerToken = $bearerToken;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getTweets(string $id, array $options = []): array
    {
        return $this->request('/2/users/'.$id.'/tweets', 'GET', [
            'expansions' => 'attachments.media_keys',
            'media.fields' => 'height,media_key,preview_image_url,type,url,width',
            'exclude' => 'retweets,replies',
            'max_results' => '30',
        ] + $options);
    }

    /**
     * @throws ExceptionInterface
     */
    public function findUser(string $username): array
    {
        $response = $this->request('/2/users/by/username/'.$username);

        return $response['data'];
    }

    /**
     * @throws ExceptionInterface
     */
    private function request(string $url, string $method = 'GET', array $query = [])
    {
        $response = $this->client->request(
            $method,
            'https://api.twitter.com'.$url,
            [
                'headers' => ['Authorization' => 'Bearer '.$this->bearerToken],
                'query' => $query,
            ]
        );

        $content = $response->getContent(false);
        $json = json_decode($content, true);
        if (isset($json['errors'])) {
            var_dump($json['errors']);
            exit;
        }

        return $json;
    }
}
