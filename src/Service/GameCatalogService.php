<?php

namespace App\Service;

class GameCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function allGames(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Jeu des Emotions',
                'description' => 'Identifiez et nommez les emotions a travers des cartes illustrees et des scenarios du quotidien.',
                'category' => 'Emotions',
                'category_color' => 'secondary',
                'age' => '3-7 ans',
                'difficulty' => 1,
                'icon' => '😊',
                'gradient' => 'linear-gradient(135deg,#ff9a9e,#fecfef)',
                'color' => 'secondary',
                'target_levels' => ['FACILE', 'MOYEN'],
            ],
            [
                'id' => 2,
                'title' => 'Puzzle des Formes',
                'description' => 'Assemblez des pieces de puzzle interactives en progressant par niveaux de difficulte.',
                'category' => 'Logique',
                'category_color' => 'primary',
                'age' => '5-12 ans',
                'difficulty' => 2,
                'icon' => '🧩',
                'gradient' => 'linear-gradient(135deg,#a8edea,#fed6e3)',
                'color' => 'primary',
                'target_levels' => ['MOYEN', 'DIFFICILE'],
            ],
            [
                'id' => 3,
                'title' => 'Parole & Images PECS',
                'description' => 'Apprenez a communiquer via des pictogrammes et des cartes visuelles interactives.',
                'category' => 'Communication',
                'category_color' => 'success',
                'age' => '4-10 ans',
                'difficulty' => 1,
                'icon' => '🗣️',
                'gradient' => 'linear-gradient(135deg,#ffecd2,#fcb69f)',
                'color' => 'success',
                'target_levels' => ['FACILE', 'MOYEN'],
            ],
            [
                'id' => 4,
                'title' => 'Memory Colore',
                'description' => 'Entrainez la memoire visuelle et la concentration avec des cartes colorees adaptees TSA.',
                'category' => 'Memoire',
                'category_color' => 'warning',
                'age' => '6-15 ans',
                'difficulty' => 2,
                'icon' => '🧠',
                'gradient' => 'linear-gradient(135deg,#d4fc79,#96e6a1)',
                'color' => 'warning',
                'target_levels' => ['MOYEN', 'DIFFICILE'],
            ],
            [
                'id' => 5,
                'title' => 'Coloriage Sensoriel',
                'description' => 'Coloriage interactif avec retour sensoriel sonore pour developper la motricite fine.',
                'category' => 'Motricite',
                'category_color' => 'info',
                'age' => '3-8 ans',
                'difficulty' => 1,
                'icon' => '✋',
                'gradient' => 'linear-gradient(135deg,#89f7fe,#66a6ff)',
                'color' => 'info',
                'target_levels' => ['FACILE'],
            ],
            [
                'id' => 6,
                'title' => 'Scenarios Sociaux',
                'description' => 'Simulez des situations sociales et apprenez les comportements appropries dans chaque contexte.',
                'category' => 'Social',
                'category_color' => 'primary',
                'age' => '8-16 ans',
                'difficulty' => 3,
                'icon' => '👥',
                'gradient' => 'linear-gradient(135deg,#e0c3fc,#8ec5fc)',
                'color' => 'primary',
                'target_levels' => ['DIFFICILE'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function gamesForLevel(string $level): array
    {
        return array_values(array_filter(
            $this->allGames(),
            static fn (array $game): bool => in_array($level, $game['target_levels'], true)
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function gameById(int $id): ?array
    {
        foreach ($this->allGames() as $game) {
            if ((int) $game['id'] === $id) {
                return $game;
            }
        }

        return null;
    }
}
