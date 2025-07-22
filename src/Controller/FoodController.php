<?php

namespace App\Controller;

use App\DTO\AddFoodRequest;
use App\DTO\ListFoodRequest;
use App\Domain\Model\Food;
use App\DTO\ListFoodResponse;
use App\DTO\ValidationError;
use App\Exception\FoodServiceException;
use App\Service\FoodService;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/food')]
class FoodController extends AbstractController
{
    private const GRAMS_PER_KILOGRAM = 1000;

    public function __construct(
        private readonly FoodService $foodService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/list', name: 'food_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $dto = new ListFoodRequest();
        $dto->type = $request->query->get('type');
        $dto->filter = $request->query->get('filter');
        $dto->unit = $request->query->get('unit', Food::UNIT_GRAM);

        $errors = $this->validator->validate($dto);

        if ($errors->count() > 0) {
            return $this->json(['errors'=> $this->formatErrors($errors)], Response::HTTP_BAD_REQUEST);
        }

        try {
            $foods = $this->foodService->listFoodByType($dto->type, $dto->filter);
        } catch (FoodServiceException $e) {
            return $this->json(
                ['status' => 'failed', 'message' => $e->getMessage()],
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $result = array_map(fn(Food $food) => new ListFoodResponse(
            $food->getName(),
            $this->convertQuantity($food->getQuantityInGrams(), $dto->unit),
            $dto->unit,
            $food->getType()
        ), $foods);

        return $this->json($result);
    }

    #[Route('/add', name: 'food_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $foods = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $this->json(['errors' => [$e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($foods)) {
            throw new BadRequestHttpException('Expected a list of food items.');
        }

        $dtos = [];

        try {
            foreach ($foods as $food) {
                $dtos[] = $this->serializer->denormalize($food, AddFoodRequest::class);
            }
        } catch (Exception $e) {
            return $this->json(['errors' => [$e->getMessage()]], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        foreach ($dtos as $index => $dto) {
            $violations = $this->validator->validate($dto);
            if ($violations->count() > 0) {
                $errors[$index] = $this->formatErrors($violations);
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->foodService->bulkInsert($dtos);
        } catch (FoodServiceException $e) {
            return $this->json([
                'status' => 'failed',
                'message' => 'Unable to save food: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['status' => 'success']);
    }

    private function convertQuantity(int $quantity, string $unit): float|int
    {
        return $unit === Food::UNIT_KILOGRAM ? $quantity / self::GRAMS_PER_KILOGRAM : $quantity;
    }

    private function formatErrors(ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = new ValidationError(
                $error->getPropertyPath(),
                $error->getMessage(),
                $error->getCode()
            );
        }

        return $errorMessages;
    }
}
