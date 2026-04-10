<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/todolist')]
class TodoListController extends AbstractController
{
    private string $storageFile;
    
    public function __construct(string $projectDir)
    {
        // Stocker les données dans un fichier JSON dans var/data/
        $this->storageFile = $projectDir . '/var/data/todolist.json';
    }
    
    private function loadTodos(): array
    {
        // Créer le dossier si nécessaire
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Si le fichier n'existe pas, créer un tableau vide
        if (!file_exists($this->storageFile)) {
            return [];
        }
        
        $content = file_get_contents($this->storageFile);
        $data = json_decode($content, true);
        
        return $data['todos'] ?? [];
    }
    
    private function saveTodos(array $todos): void
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'todos' => $todos,
            'lastUpdated' => date('Y-m-d H:i:s'),
            'nextId' => $this->getNextId($todos)
        ];
        
        file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    private function getNextId(array $todos): int
    {
        if (empty($todos)) {
            return 1;
        }
        
        $maxId = 0;
        foreach ($todos as $todo) {
            if ($todo['id'] > $maxId) {
                $maxId = $todo['id'];
            }
        }
        return $maxId + 1;
    }
    
    private function calculateStats(array $todos): array
    {
        $aFaire = count(array_filter($todos, fn($t) => $t['statut'] === 'À faire'));
        $enCours = count(array_filter($todos, fn($t) => $t['statut'] === 'En cours'));
        $termine = count(array_filter($todos, fn($t) => $t['statut'] === 'Terminé'));
        
        return [
            'aFaire' => $aFaire,
            'enCours' => $enCours,
            'termine' => $termine,
            'total' => count($todos)
        ];
    }

    #[Route('/', name: 'admin_todolist')]
    public function index(): Response
    {
        $todos = $this->loadTodos();
        $stats = $this->calculateStats($todos);

        return $this->render('admin/todolist/index.html.twig', [
            'todos' => $todos,
            'stats' => $stats,
        ]);
    }

    #[Route('/data', name: 'admin_todolist_data', methods: ['GET'])]
    public function getData(): JsonResponse
    {
        $todos = $this->loadTodos();
        $stats = $this->calculateStats($todos);

        return $this->json([
            'todos' => $todos,
            'stats' => $stats
        ]);
    }

    #[Route('/add', name: 'admin_todolist_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $titre = trim($data['titre'] ?? '');
        
        if (empty($titre)) {
            return $this->json(['error' => 'Le titre est requis'], 400);
        }

        $todos = $this->loadTodos();
        $nextId = $this->getNextId($todos);

        $newTodo = [
            'id' => $nextId,
            'titre' => $titre,
            'description' => $data['description'] ?? '',
            'statut' => $data['statut'] ?? 'À faire',
            'date' => date('Y-m-d'),
            'createdAt' => time()
        ];

        $todos[] = $newTodo;
        $this->saveTodos($todos);

        return $this->json([
            'success' => true,
            'todo' => $newTodo,
            'todos' => $todos,
            'stats' => $this->calculateStats($todos)
        ]);
    }

    #[Route('/update/{id}', name: 'admin_todolist_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $todos = $this->loadTodos();
        
        $found = false;
        foreach ($todos as &$todo) {
            if ($todo['id'] === $id) {
                if (isset($data['titre'])) $todo['titre'] = trim($data['titre']);
                if (isset($data['description'])) $todo['description'] = $data['description'];
                if (isset($data['statut'])) $todo['statut'] = $data['statut'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        $this->saveTodos($todos);
        
        return $this->json([
            'success' => true,
            'todos' => $todos,
            'stats' => $this->calculateStats($todos)
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_todolist_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $todos = $this->loadTodos();
        $newTodos = array_filter($todos, function($todo) use ($id) {
            return $todo['id'] !== $id;
        });
        
        if (count($todos) === count($newTodos)) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        $this->saveTodos(array_values($newTodos));
        
        return $this->json([
            'success' => true,
            'todos' => array_values($newTodos),
            'stats' => $this->calculateStats($newTodos)
        ]);
    }

    #[Route('/move/{id}/{statut}', name: 'admin_todolist_move', methods: ['PATCH'])]
    public function move(int $id, string $statut): JsonResponse
    {
        $validStatuts = ['À faire', 'En cours', 'Terminé'];
        if (!in_array($statut, $validStatuts)) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }
        
        $todos = $this->loadTodos();
        $found = false;
        
        foreach ($todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['statut'] = $statut;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        
        $this->saveTodos($todos);
        
        return $this->json([
            'success' => true,
            'todos' => $todos,
            'stats' => $this->calculateStats($todos)
        ]);
    }

    #[Route('/clear-completed', name: 'admin_todolist_clear_completed', methods: ['DELETE'])]
    public function clearCompleted(): JsonResponse
    {
        $todos = $this->loadTodos();
        $newTodos = array_filter($todos, function($todo) {
            return $todo['statut'] !== 'Terminé';
        });
        
        $this->saveTodos(array_values($newTodos));
        
        return $this->json([
            'success' => true,
            'todos' => array_values($newTodos),
            'stats' => $this->calculateStats($newTodos)
        ]);
    }
}