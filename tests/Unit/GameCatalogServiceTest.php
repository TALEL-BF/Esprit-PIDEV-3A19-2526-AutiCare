<?php

namespace App\Tests\Unit;

use App\Service\GameCatalogService;
use PHPUnit\Framework\TestCase;

class GameCatalogServiceTest extends TestCase
{
    public function testAllGamesReturnsExpectedCatalog(): void
    {
        $service = new GameCatalogService();

        $games = $service->allGames();

        $this->assertCount(6, $games);
        $this->assertSame(1, $games[0]['id']);
        $this->assertArrayHasKey('title', $games[0]);
        $this->assertArrayHasKey('target_levels', $games[0]);
    }

    public function testGamesForLevelReturnsOnlyCompatibleGames(): void
    {
        $service = new GameCatalogService();

        $facileGames = $service->gamesForLevel('FACILE');
        $ids = array_map(static fn (array $game): int => (int) $game['id'], $facileGames);

        sort($ids);

        $this->assertSame([1, 3, 5], $ids);
    }

    public function testGameByIdReturnsNullWhenGameDoesNotExist(): void
    {
        $service = new GameCatalogService();

        $game = $service->gameById(9999);

        $this->assertNull($game);
    }
}
