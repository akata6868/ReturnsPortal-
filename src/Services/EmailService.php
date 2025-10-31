<?php

namespace ReturnsPortal\Services;

use Plenty\Modules\Frontend\Services\SystemService;
use Plenty\Plugin\Mail\Contracts\MailerContract;
use Plenty\Plugin\Templates\Twig;
use ReturnsPortal\Models\ReturnModel;

/**
 * Class EmailService
 * @package ReturnsPortal\Services
 */
class EmailService
{
    /**
     * @var MailerContract
     */
    private $mailer;

    /**
     * @var Twig
     */
    private $twig;

    /**
     * @var SystemService
     */
    private $systemService;

    /**
     * EmailService constructor.
     */
    public function __construct(
        MailerContract $mailer,
        Twig $twig,
        SystemService $systemService
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->systemService = $systemService;
    }

    /**
     * Send return created email
     * @param ReturnModel $returnRecord
     */
    public function sendReturnCreatedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-created', [
            eturnRecord' => $returnRecord,
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
     * @param ReturnModel $returnRecord
     */
    public function sendReturnApprovedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-approved', [
            eturnRecord' => $returnRecord,
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
     * @param ReturnModel $returnRecord
     * @param string $reason
     */
    public function sendReturnRejectedEmail(ReturnModel $returnRecord, string $reason): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-rejected', [
            eturnRecord' => $returnRecord,
            'reason' => $reason
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Return Rejected - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return received email
     * @param ReturnModel $returnRecord
     */
    public function sendReturnReceivedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-received', [
            eturnRecord' => $returnRecord,
            'trackingUrl' => $this->getTrackingUrl($returnRecord->id)
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Return Received - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send refund processed email
     * @param ReturnModel $returnRecord
     */
    public function sendRefundProcessedEmail(ReturnModel $returnRecord): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.refund-processed', [
            eturnRecord' => $returnRecord
        ]);

        $this->sendEmail(
            $returnRecord->customer_email,
            'Refund Processed - ' . $returnRecord->return_number,
            $emailTemplate
        );
    }

    /**
     * Send email
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    private function sendEmail(string $to, string $subject, string $body): void
    {
        try {
            $this->mailer->sendHtml(
                $body,
                $to,
                $subject,
                [],
                [],
                $this->getFromEmail(),
                $this->getFromName()
            );
        } catch (\Exception $e) {
            // Log error but don't throw
            error_log('Email sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Get tracking URL
     * @param int $returnId
     * @return string
     */
    private function getTrackingUrl(int $returnId): string
    {
        $domain = $this->systemService->getWebstoreConfig()->domain;
        return 'https://' . $domain . '/returns/track/' . $returnId;
    }

    /**
     * Get label URL
     * @param int $returnId
     * @return string
     */
    private function getLabelUrl(int $returnId): string
    {
        $domain = $this->systemService->getWebstoreConfig()->domain;
        return 'https://' . $domain . '/returns/print-label/' . $returnId;
    }

    /**
     * Get from email
     * @return string
     */
    private function getFromEmail(): string
    {
        return $this->systemService->getWebstoreConfig()->defaultEmailAddress ?? 'noreply@example.com';
    }

    /**
     * Get from name
     * @return string
     */
    private function getFromName(): string
    {
        return $this->systemService->getWebstoreConfig()->name ?? 'Returns Portal';
    }
}
