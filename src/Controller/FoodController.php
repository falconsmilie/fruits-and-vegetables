<?php

namespace App\Controller;

use App\DTO\AddFoodRequest;
use App\DTO\ListFoodRequest;
use App\Domain\Model\Food;
use App\Domain\Model\Fruit;
use App\Domain\Model\Vegetable;
use App\Exception\FoodServiceException;
use App\Service\FoodService;
use App\Util\DbalHelper;
use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/food')]
class FoodController extends AbstractController
{
    private const GRAMS_PER_KILOGRAM = 1000;

    public function __construct(
        private readonly FoodService $foodService,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly Connection $connection,
        private readonly DbalHelper $dbalHelper,
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

        if (count($errors) > 0) {
            return $this->json($this->formatErrors($errors), 400);
        }

        try {
            $foods = $this->foodService->listFoodByType($dto->type, $dto->filter);
        } catch (FoodServiceException $e) {
            return $this->json(
                ['status' => 'failed', 'message' => $e->getMessage()],
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $result = array_map(fn($food) => [
            'name' => $food->getName(),
            'quantity' => $this->convertQuantity($food->getQuantityInGrams(), $dto->unit),
            'unit' => $dto->unit,
            'type' => $food->getType(),
        ], $foods);

        return $this->json($result);
    }

    #[Route('/add', name: 'food_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dtos = [];

        try {
            foreach ($data as $item) {
                $dtos[] = $this->serializer->denormalize($item, AddFoodRequest::class);
            }
        } catch (Exception $e) {
            throw new BadRequestHttpException('Invalid JSON payload: ' . $e->getMessage());
        }

        $allErrors = [];
        foreach ($dtos as $index => $dto) {
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $allErrors[$index] = $this->formatErrors($errors);
            }
        }

        if (!empty($allErrors)) {
            return $this->json(['errors' => $allErrors], Response::HTTP_BAD_REQUEST);
        }

        $rows = [];
        foreach ($dtos as $dto) {
            $quantityInGrams = $dto->unit === Food::UNIT_KILOGRAM
                ? (int) ($dto->quantity * 1000)
                : (int) $dto->quantity;

            $rows[] = [
                'name' => $dto->name,
                'type' => $dto->type,
                'quantity_in_grams' => $quantityInGrams,
            ];
        }

        try {
            $this->dbalHelper->insertBatch($this->connection, 'food', $rows);
        } catch (Exception $e) {
            return $this->json([
                'status' => 'failed',
                'message' => 'Database error: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['status' => 'success']);
    }


    private function convertQuantity(int $quantity, string $unit): float|int
    {
        return $unit === Food::UNIT_KILOGRAM ? $quantity / self::GRAMS_PER_KILOGRAM : $quantity;
    }

    private function formatErrors($errors): array
    {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = [
                'property' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
            ];
        }

        return ['errors' => $errorMessages];
    }
}
