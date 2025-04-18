<?php

namespace App\Controller;

use App\Exception\DeserializeException;
use App\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function __invoke(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $this->json([
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }
        if ($exception instanceof DeserializeException) {
            return $this->json([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ], $exception->getCode());
        }
        if ($exception instanceof ValidationException) {
            $data = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];
            foreach ($exception->validationResult as $error) {
                $data['errors'][] = [
                    'property' => (string)$error->getPropertyPath(),
                    'message' => (string)$error->getMessage(),
                ];
            }
            return $this->json($data, $exception->getCode());
        }
        return $this->json(['message' => $exception->getMessage()], 500);
    }
}
