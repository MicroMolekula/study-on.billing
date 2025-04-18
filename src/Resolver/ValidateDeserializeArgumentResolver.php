<?php

namespace App\Resolver;

use App\Attribute\ValidateDeserialize;
use App\Exception\DeserializeException;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateDeserializeArgumentResolver implements  ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return (bool)$argument->getAttributes(ValidateDeserialize::class, ArgumentMetadata::IS_INSTANCEOF);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        try {
            $data = $this->serializer->deserialize(
                $request->getContent(),
                $argument->getType(),
                'json'
            );
        } catch (\Exception $exception) {
            throw new DeserializeException(message: 'Ошибка десериализации тела запроса', code: 400);
        }


        $validationResult = $this->validator->validate($data);

        if ($validationResult->count() > 0) {
            throw new ValidationException(message: 'Ошибка валидации', code: 400, validationResult: $validationResult);
        }

        yield $data;
    }

}