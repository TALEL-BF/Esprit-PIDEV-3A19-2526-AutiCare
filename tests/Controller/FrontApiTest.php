<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FrontApiTest extends WebTestCase
{
    public function testRecommendedGamesRequiresEnfantId(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/front/recommended-games');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testChatbotRequiresMessage(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/chatbot/ask',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}
