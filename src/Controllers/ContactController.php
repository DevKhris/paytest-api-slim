<?php

namespace PayTest\Controllers;

use PayTest\Services\ContactService;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ContactController
{
    public function __construct(
        private ContactService $contactService,
        private UserRepositoryInterface $userRepository
    ) {}

    public function addContact(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $ownerUserUniqueId = $request->getAttribute('user_unique_id');
            $data = $request->getParsedBody();

            $contactUserId = $data['contactUserId'] ?? '';

            if (empty($contactUserId)) {
                return $this->jsonResponse($response, 400, ['error' => 'contactUserId is required']);
            }

            if ($ownerUserUniqueId === $contactUserId) {
                return $this->jsonResponse($response, 400, ['error' => 'Cannot add yourself as contact']);
            }

            $contact = $this->contactService->addContact($ownerUserUniqueId, $contactUserId);

            $contactUser = $this->userRepository->findByUniqueId($contactUserId);

            Logger::info('Contact added', [
                'owner' => $ownerUserUniqueId,
                'contact' => $contactUserId
            ]);

            return $this->jsonResponse($response, 201, [
                'id' => $contact->getId(),
                'owner_id' => $ownerUserUniqueId,
                'contact_user_id' => $contactUserId,
                'contact' => [
                    'id' => $contactUserId,
                    'name' => $contactUser?->getName() ?? ''
                ],
                'created_at' => (new \DateTime())->format('Y-m-d\TH:i:s\Z')
            ]);

        } catch (ValidationException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Failed to add contact', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Failed to add contact']);
        }
    }

    public function listContacts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $ownerUserUniqueId = $request->getAttribute('user_unique_id');

            $contacts = $this->contactService->getContacts($ownerUserUniqueId);

            $contactsArray = array_map(
                function ($contact) {
                    $contactUser = $this->userRepository->findByUniqueId($contact->getContactUserId());
                    return [
                        'id' => $contact->getId(),
                        'owner_id' => $contact->getOwnerId(),
                        'contact_user_id' => $contact->getContactUserId(),
                        'contact' => [
                            'id' => $contact->getContactUserId(),
                            'name' => $contactUser?->getName() ?? ''
                        ],
                        'created_at' => $contact->getCreatedAt()?->format('Y-m-d\TH:i:s\Z') ?? (new \DateTime())->format('Y-m-d\TH:i:s\Z')
                    ];
                },
                $contacts
            );

            return $this->jsonResponse($response, 200, [
                'contacts' => $contactsArray,
                'total' => count($contactsArray)
            ]);

        } catch (\Exception $e) {
            Logger::error('Failed to list contacts', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Failed to retrieve contacts']);
        }
    }

    public function deleteContact(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $ownerUserUniqueId = $request->getAttribute('user_unique_id');
            $contactId = $request->getAttribute('id');

            $this->contactService->removeContact($ownerUserUniqueId, $contactId);

            Logger::info('Contact deleted', ['owner' => $ownerUserUniqueId, 'contact_id' => $contactId]);

            return $this->jsonResponse($response, 200, [
                'message' => 'Contact deleted'
            ]);

        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Failed to delete contact', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Failed to delete contact']);
        }
    }

    private function jsonResponse(ResponseInterface $response, int $status, array $data): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
