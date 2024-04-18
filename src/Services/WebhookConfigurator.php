<?php

namespace SceneApi\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WebhookConfigurator
{
    protected string $setUrl = 'https://api.telegram.org/bot{token}/setWebhook?url={webhook}';
    protected string $deleteUrl = 'https://api.telegram.org/bot{token}/setWebhook?url=';
    protected string $webhookUri = '/webhook/bot';
    protected ?string $token;
    protected ?string $webhookOrigin;

    protected Client $guzzle;

    public function __construct(?string $url = null, ?string $token = null)
    {
        $this->webhookOrigin = rtrim($url, '/');
        $this->token = $token;

        $this->guzzle = new Client();
    }

    /**
     * @throws GuzzleException
     */
    public function setWebhook(): void
    {
        $res = $this->guzzle->get($this->generateSetUrl());

        echo $res->getBody();
    }

    public function deleteWebhook(): void
    {
        $res = $this->guzzle->get($this->generateDeleteUrl());

        echo $res->getBody();
    }

    protected function generateSetUrl(): string
    {
        $webhook = $this->getWebhookUrl();
        return str_replace(['{token}', '{webhook}'], [$this->token, $webhook], $this->setUrl);
    }

    protected function generateDeleteUrl(): string
    {
        return str_replace('{token}', $this->token, $this->deleteUrl);
    }

    protected function getWebhookUrl(): string
    {
        return ($this->webhookOrigin . $this->webhookUri);
    }
}