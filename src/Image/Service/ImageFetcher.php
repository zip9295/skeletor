<?php
namespace Skeletor\Image\Service;

use GuzzleHttp\Client;

class ImageFetcher
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function fetch($url, $savePath, $imageName = null)
    {
        if (strlen($url) === 0) {
            return '';
        }
        $imageData = '';
        $parsedUrl = parse_url($url);
        try {
            if (!isset($parsedUrl['scheme'])) {
                throw new \Exception(sprintf('Invalid url provided for image fetch: %s.', $url));
            }

            switch ($parsedUrl['scheme']) {
                case 'https':
                case 'http':
                    $imageData = $this->handleHttp($url);
                    break;

                case 'default':
                    return '';
            }
        } catch (\Exception $e) {
            throw $e;
        }
        if($imageData === '') {
            return '';
        }

        $imageFileName = $imageData['name'];
        if ($imageName) {
            $imageFileName = $imageName;
        }
        $imagePath = sprintf('%s/%s', $savePath, $imageFileName);
        file_put_contents($imagePath, $imageData['data']);

        return $imagePath;
    }

    private function handleHttp($url)
    {
        $config = [];
        $response = $this->client->get($url, $config);
        if ($response->getHeader('Content-Type')[0] === 'image/png') {
            $extension = '.png';
        } elseif ($response->getHeader('Content-Type')[0] === 'image/jpg' || $response->getHeader('Content-Type')[0] === 'image/jpeg') {
            $extension = '.jpg';
        }
        $data = $response->getBody()->getContents();
//        $allowedTypes = ['image/jpeg', 'image/png']; // 'image/webp' does not work well
//        if (!in_array($response->getHeader('Content-Type')[0], $allowedTypes)) {
//            $response = $this->client->get($url);
//        }

        return ['data' => $data, 'name' => md5($data) . $extension];
    }
}