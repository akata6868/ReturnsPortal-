<?php

namespace ReturnsPortal\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use ReturnsPortal\Services\ReturnService;
use ReturnsPortal\Services\ReturnValidationService;
use ReturnsPortal\Repositories\ReturnRepository;

/**
 * Class ReturnController
 * @package ReturnsPortal\Controllers
 */
class ReturnController extends Controller
{
    /**
     * @var ReturnService
     */
    private $returnService;

    /**
     * @var ReturnRepository
     */
    private $returnRepository;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var ReturnValidationService
     */
    private $validationService;

    /**
     * ReturnController constructor.
     * @param ReturnModelService $returnService
     * @param ReturnModelRepository $returnRepository
     * @param OrderRepositoryContract $orderRepository
     * @param ReturnModelValidationService $validationService
     */
    public function __construct(
        ReturnService $returnService,
        ReturnRepository $returnRepository,
        OrderRepositoryContract $orderRepository,
        ReturnValidationService $validationService
    ) {
        $this->returnService = $returnService;
        $this->returnRepository = $returnRepository;
        $this->orderRepository = $orderRepository;
        $this->validationService = $validationService;
    }

    /**
     * Show returns portal homepage
     * @param Twig $twig
     * @return string
     */
    public function index(Twig $twig): string
    {
        return $twig->render('ReturnsPortal::content.return-index', [
            'title' => 'Returns Portal'
        ]);
    }

    /**
     * Show return creation form for a specific order
     * @param Twig $twig
     * @param int $orderId
     * @param Request $request
     * @return string
     */
    public function create(Twig $twig, int $orderId, Request $request): string
    {
        try {
            // Get order details
            $order = $this->orderRepository->findOrderById($orderId);
            
            // Validate if order is eligible for return
            $validation = $this->validationService->validateOrderForReturn($order);
            
            if (!$validation['eligible']) {
                return $twig->render('ReturnsPortal::content.return-error', [
                    'error' => $validation['message']
                ]);
            }

            // Get order items
            $orderItems = $order->orderItems;
            
            // Get return reasons from config
            $returnReasons = $this->returnService->getReturnReasons();

            return $twig->render('ReturnsPortal::content.return-form', [
                'order' => $order,
                'orderItems' => $orderItems,
                'returnReasons' => $returnReasons,
                'validation' => $validation
            ]);
        } catch (\Exception $e) {
            return $twig->render('ReturnsPortal::content.return-error', [
                'error' => 'Order not found or not accessible'
            ]);
        }
    }

    /**
     * Store a new return request
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->all();
            
            // Validate return request
            $validation = $this->validationService->validateReturnRequest($data);
            
            if (!$validation['valid']) {
                return $response->json([
                    'success' => false,
                    'message' => $validation['message'],
                    'errors' => $validation['errors']
                ], 400);
            }

            // Create return
            $returnRecord = $this->returnService->createReturn($data);

            return $response->json([
                'success' => true,
                'message' => 'Return request created successfully',
                'returnId' => $returnRecord->id,
                'returnNumber' => $returnRecord->return_number,
                'trackingUrl' => '/returns/track/' . $returnRecord->id
            ], 201);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to create return request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track a return by ID
     * @param Twig $twig
     * @param int $returnId
     * @return string
     */
    public function track(Twig $twig, int $returnId): string
    {
        try {
            $returnRecord = $this->returnRepository->findById($returnId);
            
            if (!$returnRecord) {
                return $twig->render('ReturnsPortal::content.return-error', [
                    'error' => 'Return not found'
                ]);
            }

            // Get status history
            $statusHistory = $this->returnService->getReturnStatusHistory($returnId);

            return $twig->render('ReturnsPortal::content.return-tracking', [
                'return' => $returnRecord,
                'statusHistory' => $statusHistory,
                'currentStatus' => $this->returnService->getStatusLabel($returnRecord->status)
            ]);

        } catch (\Exception $e) {
            return $twig->render('ReturnsPortal::content.return-error', [
                'error' => 'Error loading return details'
            ]);
        }
    }

    /**
     * Get return status (JSON)
     * @param int $returnId
     * @param Response $response
     * @return Response
     */
    public function status(int $returnId, Response $response): Response
    {
        try {
            $returnRecord = $this->returnRepository->findById($returnId);
            
            if (!$returnRecord) {
                return $response->json([
                    'success' => false,
                    'message' => 'Return not found'
                ], 404);
            }

            return $response->json([
                'success' => true,
                'return' => [
                    'id' => $returnRecord->id,
                    'return_number' => $returnRecord->return_number,
                    'status' => $returnRecord->status,
                    'status_label' => $this->returnService->getStatusLabel($returnRecord->status),
                    'created_at' => $returnRecord->created_at,
                    'updated_at' => $returnRecord->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error retrieving return status'
            ], 500);
        }
    }

    /**
     * Upload return item image
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function uploadImage(Request $request, Response $response): Response
    {
        try {
            $file = $request->file('image');
            
            if (!$file) {
                return $response->json([
                    'success' => false,
                    'message' => 'No image provided'
                ], 400);
            }

            // Upload image
            $imageUrl = $this->returnService->uploadReturnImage($file);

            return $response->json([
                'success' => true,
                'imageUrl' => $imageUrl
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and print return label
     * @param Twig $twig
     * @param int $returnId
     * @return string
     */
    public function printLabel(Twig $twig, int $returnId): string
    {
        try {
            $returnRecord = $this->returnRepository->findById($returnId);
            
            if (!$returnRecord) {
                return $twig->render('ReturnsPortal::content.return-error', [
                    'error' => 'Return not found'
                ]);
            }

            // Generate return label PDF
            $labelData = $this->returnService->generateReturnLabel($returnRecord);

            return $twig->render('ReturnsPortal::content.return-label', [
                'return' => $returnRecord,
                'labelData' => $labelData,
                'barcode' => $this->returnService->generateBarcode($returnRecord->return_number)
            ]);

        } catch (\Exception $e) {
            return $twig->render('ReturnsPortal::content.return-error', [
                'error' => 'Error generating return label'
            ]);
        }
    }

    /**
     * Show customer's return history
     * @param Twig $twig
     * @param ContactRepositoryContract $contactRepository
     * @return string
     */
    public function myReturns(Twig $twig, ContactRepositoryContract $contactRepository): string
    {
        try {
            // Get current logged-in contact
            $contact = $contactRepository->getCurrentContact();
            
            if (!$contact) {
                return $twig->render('ReturnsPortal::content.return-error', [
                    'error' => 'Please log in to view your returns'
                ]);
            }

            // Get all returns for this contact
            $returns = $this->returnRepository->findByContactId($contact->id);

            return $twig->render('ReturnsPortal::content.my-returns', [
                'returns' => $returns,
                'contact' => $contact
            ]);

        } catch (\Exception $e) {
            return $twig->render('ReturnsPortal::content.return-error', [
                'error' => 'Error loading your returns'
            ]);
        }
    }
}
