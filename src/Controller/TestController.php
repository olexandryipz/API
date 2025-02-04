<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1')]
class TestController extends AbstractController
{
    private const SESSION_KEY = 'users_data';

    private function getUsersFromSession(Request $request): array
    {
        $session = $request->getSession();
        if (!$session->has(self::SESSION_KEY)) {
            $session->set(self::SESSION_KEY, [
                ['id' => '1', 'email' => 'ipz231_soyu@student.ztu.edu.ua', 'name' => 'Oleksandr'],
                ['id' => '2', 'email' => 'ipz231_soyu1@student.ztu.edu.ua', 'name' => 'Oleksandr1'],
                ['id' => '3', 'email' => 'ipz231_soyu2@student.ztu.edu.ua', 'name' => 'Oleksandr2'],
                ['id' => '4', 'email' => 'ipz231_soyu3@student.ztu.edu.ua', 'name' => 'Oleksandr3'],
                ['id' => '5', 'email' => 'ipz231_soyu4@student.ztu.edu.ua', 'name' => 'Oleksandr4'],
                ['id' => '6', 'email' => 'ipz231_soyu5@student.ztu.edu.ua', 'name' => 'Oleksandr5'],
                ['id' => '7', 'email' => 'ipz231_soyu6@student.ztu.edu.ua', 'name' => 'Oleksandr6'],
            ]);
        }
        return $session->get(self::SESSION_KEY);
    }

    private function saveUsersToSession(Request $request, array $users): void
    {
        $session = $request->getSession();
        $session->set(self::SESSION_KEY, $users);
    }

    #[Route('/users', name: 'app_collection_users', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    public function getCollection(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => $this->getUsersFromSession($request)], Response::HTTP_OK);
    }

    #[Route('/users/{id}', name: 'app_item_users', methods: ['GET'])]
    public function getItem(string $id, Request $request): JsonResponse
    {
        $userData = $this->findUserById($id, $request);
        return new JsonResponse(['data' => $userData], Response::HTTP_OK);
    }

    #[Route('/users', name: 'app_create_users', methods: ['POST'])]
    public function createItem(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        if (!isset($requestData['email'], $requestData['name'])) {
            throw new UnprocessableEntityHttpException("name and email are required");
        }

        $users = $this->getUsersFromSession($request);
        $newUser = [
            'id'    => strval(count($users) + 1),
            'name'  => $requestData['name'],
            'email' => $requestData['email']
        ];

        $users[] = $newUser;
        $this->saveUsersToSession($request, $users);

        return new JsonResponse(['data' => $newUser], Response::HTTP_CREATED);
    }

    #[Route('/users/{id}', name: 'app_delete_users', methods: ['DELETE'])]
    public function deleteItem(string $id, Request $request): JsonResponse
    {
        $users = $this->getUsersFromSession($request);

        $filteredUsers = array_filter($users, fn($user) => $user['id'] !== $id);
        if (count($filteredUsers) === count($users)) {
            throw new NotFoundHttpException("User with id " . $id . " not found");
        }

        $this->saveUsersToSession($request, array_values($filteredUsers));

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    #[Route('/users/{id}', name: 'app_update_users', methods: ['PATCH'])]
    public function updateItem(string $id, Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        $users = $this->getUsersFromSession($request);
        foreach ($users as &$user) {
            if ($user['id'] === $id) {
                if(isset($requestData['name'])) {
                    $user['name'] = $requestData['name'];
                }
                if(isset($requestData['email'])) {
                    $user['email'] = $requestData['email'];
                }
                $this->saveUsersToSession($request, $users);
                return new JsonResponse(['data' => $user], Response::HTTP_OK);
            }
        }

        throw new NotFoundHttpException("User with id " . $id . " not found");
    }

    private function findUserById(string $id, Request $request): array
    {
        $users = $this->getUsersFromSession($request);

        foreach ($users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }

        throw new NotFoundHttpException("User with id " . $id . " not found");
    }
}
