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
use Kopokopo\SDK\PollingService;

class PollingServiceTest extends TestCase
{
    public function setup(): void
    {
        $options = [
            'clientId' => 'your_client_id',
            'clientSecret' => 'your_client_secret',
            'apiKey' => 'your_api_key',
            'baseUrl' => 'https://9284bede-d6e9f8d86aff.mock.pstmn.io'
        ];

        /*
        *    pollTransactions() setup
        */

        // pollTransactions() response headers
        $pollingRequestHeaders = file_get_contents(__DIR__.'/Mocks/pollingHeaders.json');

        // Create an instance of MockHandler for returning responses for pollTransactions()
        $pollingRequestMock = new MockHandler([
            new Response(200, json_decode($pollingRequestHeaders, true)),
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ]);

        // Assign the instance of MockHandler to a HandlerStack
        $pollingRequestHandler = HandlerStack::create($pollingRequestMock);

        // Create a new instance of client using the pollTransactions() handler
        $pollingRequestClient = new Client(['handler' => $pollingRequestHandler]);

        // Use $pollingRequestClient to create an instance of the PollingService() class
        $this->pollingRequestClient = new PollingService($pollingRequestClient, $options);

        /*
        *    getStatus() setup
        */

        // json response to be returned
        $statusBody = file_get_contents(__DIR__.'/Mocks/pollingStatus.json');

        // Create an instance of MockHandler for returning responses for getStatus()
        $statusMock = new MockHandler([
            new Response(200, [], $statusBody),
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ]);

        // Assign the instance of MockHandler to a HandlerStack
        $statusHandler = HandlerStack::create($statusMock);

        // Create a new instance of client using the getStatus() handler
        $statusClient = new Client(['handler' => $statusHandler]);

        // Use the $statusClient to create an instance of the PollingService() class
        $this->statusClient = new PollingService($statusClient, $options);
    }

    /*
    *   Polling Request tests
    */

    public function testPollTransactionSucceeds()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scope' => 'company',
            'scopeReference' => '9597',
            'callbackUrl' => 'http://localhost:8000/test',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);
        
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testPollTransactionWithNoFromTimeFails()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scope' => 'company',
            'scopeReference' => '9597',
            'callbackUrl' => 'http://localhost:8000/test',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the fromTime', $response['data']);
    }

    public function testPollTransactionWithNoToTimeFails()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'scope' => 'company',
            'scopeReference' => '9597',
            'callbackUrl' => 'http://localhost:8000/test',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the toTime', $response['data']);
    }

    public function testPollTransactionWithNoScopeFails()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scopeReference' => '9597',
            'callbackUrl' => 'http://localhost:8000/test',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the scope', $response['data']);
    }

    public function testPollTransactionWithNoScopeReferenceForCompanyScopeSucceeds()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scope' => 'company',
            'scopeReference' => null,
            'callbackUrl' => 'http://localhost:8000/test',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testPollTransactionWithNoScopeReferenceForTillScopeFails()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scope' => 'till',
            'scopeReference' => null,
            'callbackUrl' => 'http://localhost:8000/test',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the scopeReference', $response['data']);
    }

    public function testPollTransactionWithNoCallbackUrlFails()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scope' => 'company',
            'scopeReference' => '9597',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the callbackUrl', $response['data']);
    }

    public function testPollTransactionWithNoAccessTokenFails()
    {
        $response = $this->pollingRequestClient->pollTransactions([
            'fromTime' => '2021-03-28T08:50:22+03:00',
            'toTime' => '2021-04-01T08:50:22+03:00',
            'scope' => 'company',
            'scopeReference' => '9597',
            'callbackUrl' => 'http://localhost:8000/test',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the accessToken', $response['data']);
    }

    /*
    *   Polling Request status tests
    */

    public function testGetStatusSucceeds()
    {
        $response = $this->statusClient->getStatus([
            'location' => 'http://localhost:3000/polling/c7f300c0-f1ef-4151-9bbe-005005aa3747',
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testGetStatusWithNoLocationFails()
    {
        $response = $this->statusClient->getStatus([
            'accessToken' => 'myRand0mAcc3ssT0k3n',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the location', $response['data']);
    }

    public function testGetStatusWithNoAccessTokenFails()
    {
        $response = $this->statusClient->getStatus([
            'location' => 'http://localhost:3000/polling/c7f300c0-f1ef-4151-9bbe-005005aa3747',
        ]);

        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('You have to provide the accessToken', $response['data']);
    }
}
