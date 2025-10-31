<?php

namespace ReturnsPortal\Services;

use Plenty\Modules\Frontend\Services\SystemService;
use Plenty\Plugin\Templates\Twig;
use ReturnsPortal\Models\ReturnModel;

/**
 * Class EmailService
 * @package ReturnsPortal\Services
 */
class EmailService
{
    private $twig;
    private $systemService;

    public function __construct(Twig $twig, SystemService $systemService)
    {
        $this->twig = $twig;
        $this->systemService = $systemService;
    }

    /**
     * Send return created email
     */
    public function sendReturnCreatedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-created', [
            'returnRecord' => $returnRecord,
            'trackingUrl' => $this->getTrackingUrl($returnRecord->id)
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Return Request Received - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return approved email
     */
    public function sendReturnApprovedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-approved', [
            'returnRecord' => $returnRecord,
            'labelUrl' => $this->getLabelUrl($returnRecord->id),
            'trackingUrl' => $this->getTrackingUrl($returnRecord->id)
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Return Approved - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return rejected email
     */
    public function sendReturnRejectedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-rejected', [
            'returnRecord' => $returnRecord,
            'reason' => $returnRecord->rejection_reason
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Return Rejected - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return received email
     */
    public function sendReturnReceivedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-received', [
            'returnRecord' => $returnRecord
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Return Received - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return refunded email
     */
    public function sendReturnRefundedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-refunded', [
            'returnRecord' => $returnRecord,
            'refundAmount' => $returnRecord->refund_amount
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Refund Processed - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send email using PlentyMarkets mail service
     */
    private function sendEmail(string $to, string $subject, string $body): void
    {
        // Implementation would use PlentyMarkets email service
        // This is a placeholder - actual implementation depends on store's email configuration
    }

    /**
     * Get tracking URL for return
     */
    private function getTrackingUrl(int $returnId): string
    {
        return $this->systemService->getWebstoreConfig()->url . '/returns/track/' . $returnId;
    }

    /**
     * Get label URL for return
     */
    private function getLabelUrl(int $returnId): string
    {
        return $this->systemService->getWebstoreConfig()->url . '/returns/print-label/' . $returnId;
    }
}
