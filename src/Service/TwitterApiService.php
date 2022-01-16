<?php

namespace App\Service;

use App\Exception\TwitterApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class TwitterApiService
{
    private string $bearerToken;

    public function __construct(private HttpClientInterface $client, string $bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @throws TwitterApiException
     */
    public function getUserTweets(string $userId, ?string $paginationToken = null): array
    {
        return $this->request('/2/users/'.$userId.'/tweets', 'GET', [
            'expansions' => 'attachments.media_keys',
            'media.fields' => 'type,url',
            'max_results' => '40',
            'pagination_token' => $paginationToken,
        ]);
    }

    /**
     * @throws TwitterApiException
     */
    public function getTweetsByIds(array $ids): array
    {
        return $this->request('/2/tweets', 'GET', [
            'expansions' => 'attachments.media_keys',
            'media.fields' => 'type,url',
            'ids' => implode(',', $ids),
        ]);
    }

    /**
     * @throws TwitterApiException
     */
    public function getTweetDetails(string $id): array
    {
        return $this->request('/2/tweets/'.$id, 'GET', [
            'expansions' => 'attachments.media_keys,referenced_tweets.id.author_id',
            'media.fields' => 'height,media_key,preview_image_url,type,url,width',
        ]);
    }

    /**
     * @throws TwitterApiException
     */
    public function findUser(string $username): array
    {
        $response = $this->request('/2/users/by/username/'.$username);

        return $response['data'];
    }

    /**
     * @throws TwitterApiException
     */
    private function request(string $path, string $method = 'GET', array $query = []): array
    {
        try {
            $response = $this->client->request(
                $method,
                'https://api.twitter.com'.$path,
                [
                    'headers' => ['Authorization' => 'Bearer '.$this->bearerToken],
                    'query' => $query,
                ]
            );
            $content = $response->getContent(false);

            $json = json_decode($content, true);
            if (!$json) {
                throw new TwitterApiException('Twitter response is not a valid json');
            }

            if (isset($json['errors'])) {
                $error = $json['errors'][0];

                if (isset($error['message'])) {
                    throw new TwitterApiException($error['message']);
                }
                if (isset($error['detail'])) {
                    throw new TwitterApiException($error['detail']);
                }
                throw new TwitterApiException($content);
            }

            return $json;
        } catch (TwitterApiException $e) {
            throw $e;
        } catch (Throwable $t) {
            throw new TwitterApiException($t->getMessage());
        }
    }
}
