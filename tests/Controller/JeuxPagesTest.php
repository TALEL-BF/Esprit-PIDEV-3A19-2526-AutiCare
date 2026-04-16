<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JeuxPagesTest extends WebTestCase
{
    public function testFrontJeuxPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/jeux');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Jeux Éducatifs', $crawler->filter('body')->text());
    }

    public function testAdminJeuPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/jeu');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Jeux & Niveaux', $crawler->filter('body')->text());
    }
}
