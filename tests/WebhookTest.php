<?php

namespace Kopokopo\SDK\Tests;

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Kopokopo\SDK\K2;
use Kopokopo\SDK\Webhooks;

class WebhookTest extends TestCase
{
    public function setup(): void
    {
        $options = [
            'clientId' => 'your_client_id',
            'clientSecret' => 'your_client_secret',
            'apiKey' => 'your_api_key',
            'baseUrl' => 'https://9284bede-d6e9f8d86aff.mock.pstmn.io'
        ];

        $k2 = new K2($options);
        $this->client = $k2->Webhooks();

        /*
        *    subscribe() setup
        */

        // subscribe() response headers
        $subscribeHeaders = file_get_contents(__DIR__.'/Mocks/subscribeHeaders.json');

        // Create an instance of MockHandler for returning responses for subscribe()
        $subscribeMock = new MockHandler([
            new Response(200, json_decode($subscribeHeaders, true)),
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ]);

        // Assign the instance of MockHandler to a HandlerStack
        $subscribeHandler = HandlerStack::create($subscribeMock);

        // Create a new instance of client using the subscribe() handler
        $subscribeClient = new Client(['handler' => $subscribeHandler]);

        // Use $subscribeClient to create an instance of the Webhooks() class
        $this->subscribeClient = new Webhooks($subscribeClient, $options);
    }

    /*
    *   Webhook subscribe tests
    */

    public function testWebhookSubscribeSucceeds()
    {
        $response = $this->subscribeClient->subscribe([
            'eventType' => 'buygoods_transaction_received',
            'url' => 'http://localhost:8000/webhook',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
            'scope' => 'Company',
            'scopeReference' => null
        ]);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testWebhookSubscribeWithNoEventTypeFails()
    {
        $response = $this->subscribeClient->subscribe([
            'url' => 'http://localhost:8000/webhook',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
            'scope' => 'company',
            'scopeReference' => '1'
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the eventType', $response['data']);
    }

    public function testWebhookSubscribeWithNoScopeFails()
    {
        $response = $this->subscribeClient->subscribe([
            'eventType' => 'buygoods_transaction_received',
            'url' => 'http://localhost:8000/webhook',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
            'scopeReference' => '1',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the scope', $response['data']);
    }

    public function testWebhookSubscribeWithNoScopeReferenceFails()
    {
        $response = $this->subscribeClient->subscribe([
            'eventType' => 'buygoods_transaction_received',
            'url' => 'http://localhost:8000/webhook',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
            'scope' => 'till',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the scopeReference', $response['data']);
    }

    public function testWebhookSubscribeWithNoUrlFails()
    {
         $response = $this->subscribeClient->subscribe([
            'eventType' => 'buygoods_transaction_received',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
            'scope' => 'company',
            'scopeReference' => '1'
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the url', $response['data']);
    }

    public function testWebhookSubscribeWithNoAccessTokenFails()
    {
        $response = $this->subscribeClient->subscribe([
            'eventType' => 'buygoods_transaction_received',
            'url' => 'http://localhost:8000/webhook',
            'scope' => 'company',
            'scopeReference' => '1'
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the accessToken', $response['data']);
    }

    /*
    *   Webhook handler tests
    */

    /**
     * @expectedException \ArgumentCountError
     */
    public function testWebhookHandlerWithNoDataFails()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->client->webhookHandler();
    }

    public function testCustomerCreatedWebhookHandler()
    {
        $k2Sig = '4dc26548d9a8a5ad7b1b31d56146bdaec28038bbfa4e20bf57fed39e975c9aaa';

        $reqBody = file_get_contents(__DIR__.'/Mocks/hooks/customercreated.json');
        $response = $this->client->webhookHandler($reqBody, $k2Sig, 'my_webhook_secret');

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }
}
