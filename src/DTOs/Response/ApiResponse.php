<?php

namespace PayTest\DTOs\Response;

class ApiResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?array $data = null,
        public readonly ?string $error = null,
        public readonly ?int $statusCode = 200
    ) {}

    public static function success(array $data = [], int $statusCode = 200): self
    {
        return new self(true, $data, null, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400): self
    {
        return new self(false, null, $message, $statusCode);
    }

    public function toArray(): array
    {
        $response = ['success' => $this->success];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->error !== null) {
            $response['error'] = $this->error;
        }

        return $response;
    }
}
