<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function __invoke(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }
        return $this->json(['message' => 'Unknown error'], 500);
    }
}
