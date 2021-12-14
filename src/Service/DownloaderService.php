<?php

namespace App\Service;

use App\Exception\DownloadException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class DownloaderService
{
    private string $basePath;
    private HttpClientInterface $client;

    public function __construct(string $basePath, HttpClientInterface $client)
    {
        $this->basePath = $basePath;
        $this->client = $client;
    }

    /**
     * @throws DownloadException
     */
    public function download(string $url, string $path): void
    {
        $fullPath = $this->basePath.$path;

        $dirPath = dirname($fullPath);
        if (!file_exists($dirPath)) {
            if (!mkdir($dirPath, 0755, true)) {
                throw new DownloadException(sprintf('Cannot create directory', $dirPath));
            }
        }

        $fileHandler = fopen($fullPath, 'w');
        if (!$fileHandler) {
            throw new DownloadException(sprintf('The path %s is not writeable', $path));
        }

        try {
            $response = $this->client->request('GET', $url);

            if (200 !== $response->getStatusCode()) {
                throw new DownloadException('Invalid status code '.$response->getStatusCode());
            }

            foreach ($this->client->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
            }
        } catch (DownloadException $e) {
            throw $e;
        } catch (Throwable $t) {
            throw new DownloadException($t->getMessage());
        } finally {
            fclose($fileHandler);
        }
    }
}
