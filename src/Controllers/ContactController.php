<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Request\AddContactRequest;
use PayTest\DTOs\Response\ApiResponse;
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

            $addContactRequest = AddContactRequest::fromArray($data);

            $contact = $this->contactService->addContact(
                $ownerUserUniqueId,
                $addContactRequest->contactUserUniqueId
            );

            $contactUser = $this->userRepository->findByUniqueId($addContactRequest->contactUserUniqueId);

            Logger::info('Contact added', [
                'owner' => $ownerUserUniqueId,
                'contact' => $addContactRequest->contactUserUniqueId
            ]);

            return ApiResponse::success([
                'contact' => [
                    'contact_user_unique_id' => $contact->getContactUserId(),
                    'contact_name' => $contactUser?->getName() ?? ''
                ]
            ])->toArray();

        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (NotFoundException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Failed to add contact', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to add contact', 500)->toArray();
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
                        'contact_user_unique_id' => $contact->getContactUserId(),
                        'contact_name' => $contactUser?->getName() ?? ''
                    ];
                },
                $contacts
            );

            return ApiResponse::success([
                'contacts' => $contactsArray,
                'count' => count($contactsArray)
            ])->toArray();

        } catch (\Exception $e) {
            Logger::error('Failed to list contacts', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to retrieve contacts', 500)->toArray();
        }
    }
}
