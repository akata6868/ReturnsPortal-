<?php

namespace ReturnsPortal\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Templates\Twig;
use ReturnsPortal\Services\ReturnService;
use ReturnsPortal\Services\RefundService;
use ReturnsPortal\Repositories\ReturnRepository;

/**
 * Class AdminReturnController
 * @package ReturnsPortal\Controllers
 */
class AdminReturnController extends Controller
{
    /**
     * @var ReturnService
     */
    private $returnService;

    /**
     * @var RefundService
     */
    private $refundService;

    /**
     * @var ReturnRepository
     */
    private $returnRepository;

    /**
     * AdminReturnController constructor.
     */
    public function __construct(
        ReturnService $returnService,
        RefundService $refundService,
        ReturnRepository $returnRepository
    ) {
        $this->returnService = $returnService;
        $this->refundService = $refundService;
        $this->returnRepository = $returnRepository;
    }

    /**
     * Show admin returns dashboard
     * @param Twig $twig
     * @param Request $request
     * @return string
     */
    public function index(Twig $twig, Request $request): string
    {
        $filters = [
            'status' => $request->get('status'),
            'dateFrom' => $request->get('dateFrom'),
            'dateTo' => $request->get('dateTo'),
            'searchTerm' => $request->get('search')
        ];

        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 50);

        // Get returns with filters and pagination
        $returns = $this->returnRepository->search($filters, $page, $perPage);
        
        // Get statistics
        $stats = $this->returnService->getStatistics();

        return $twig->render('ReturnsPortal::admin.returns-index', [
            'returns' => $returns,
            'stats' => $stats,
            'filters' => $filters,
            'statuses' => $this->returnService->getAvailableStatuses()
        ]);
    }

    /**
     * Show single return details
     * @param Twig $twig
     * @param int $returnId
     * @return string
     */
    public function show(Twig $twig, int $returnId): string
    {
        try {
            $returnRecord = $this->returnRepository->findByIdWithRelations($returnId);
            
            if (!$returnRecord) {
                return $twig->render('ReturnsPortal::admin.error', [
                    'error' => 'Return not found'
                ]);
            }

            // Get status history
            $statusHistory = $this->returnService->getReturnStatusHistory($returnId);
            
            // Get related order
            $order = $returnRecord->order;
            
            // Get available actions
            $availableActions = $this->returnService->getAvailableActions($returnRecord);

            return $twig->render('ReturnsPortal::admin.return-detail', [
                'return' => $returnRecord,
                'order' => $order,
                'statusHistory' => $statusHistory,
                'availableActions' => $availableActions
            ]);

        } catch (\Exception $e) {
            return $twig->render('ReturnsPortal::admin.error', [
                'error' => 'Error loading return details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Approve a return request
     * @param int $returnId
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function approve(int $returnId, Request $request, Response $response): Response
    {
        try {
            $adminNote = $request->get('admin_note', '');
            
            $result = $this->returnService->approveReturn($returnId, $adminNote);

            if ($result['success']) {
                return $response->json([
                    'success' => true,
                    'message' => 'Return approved successfully',
                    'return' => $result['return']
                ]);
            } else {
                return $response->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error approving return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a return request
     * @param int $returnId
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function reject(int $returnId, Request $request, Response $response): Response
    {
        try {
            $rejectionReason = $request->get('rejection_reason', '');
            $adminNote = $request->get('admin_note', '');
            
            if (empty($rejectionReason)) {
                return $response->json([
                    'success' => false,
                    'message' => 'Rejection reason is required'
                ], 400);
            }

            $result = $this->returnService->rejectReturn($returnId, $rejectionReason, $adminNote);

            if ($result['success']) {
                return $response->json([
                    'success' => true,
                    'message' => 'Return rejected successfully'
                ]);
            } else {
                return $response->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error rejecting return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark return as received
     * @param int $returnId
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function receive(int $returnId, Request $request, Response $response): Response
    {
        try {
            $itemConditions = $request->get('item_conditions', []);
            $qualityNotes = $request->get('quality_notes', '');
            
            $result = $this->returnService->markAsReceived($returnId, $itemConditions, $qualityNotes);

            if ($result['success']) {
                return $response->json([
                    'success' => true,
                    'message' => 'Return marked as received',
                    'nextStep' => 'refund'
                ]);
            } else {
                return $response->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error marking return as received: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process refund for a return
     * @param int $returnId
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function refund(int $returnId, Request $request, Response $response): Response
    {
        try {
            $refundMethod = $request->get('refund_method', 'original_payment');
            $refundAmount = $request->get('refund_amount');
            $refundNote = $request->get('refund_note', '');
            
            $result = $this->refundService->processRefund($returnId, [
                'method' => $refundMethod,
                'amount' => $refundAmount,
                'note' => $refundNote
            ]);

            if ($result['success']) {
                return $response->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refundId' => $result['refundId']
                ]);
            } else {
                return $response->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error processing refund: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export returns data
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function export(Request $request, Response $response): Response
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'dateFrom' => $request->get('dateFrom'),
                'dateTo' => $request->get('dateTo')
            ];
            
            $format = $request->get('format', 'csv');
            
            $exportData = $this->returnService->exportReturns($filters, $format);

            return $response->download(
                $exportData['path'],
                $exportData['filename'],
                ['Content-Type' => $exportData['mimeType']]
            );

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error exporting returns: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get return statistics
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function statistics(Request $request, Response $response): Response
    {
        try {
            $dateFrom = $request->get('dateFrom');
            $dateTo = $request->get('dateTo');
            
            $stats = $this->returnService->getDetailedStatistics($dateFrom, $dateTo);

            return $response->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
