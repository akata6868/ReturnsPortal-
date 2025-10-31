<?php

namespace ReturnsPortal\Services;

use Plenty\Modules\Frontend\Services\SystemService;
use Plenty\Plugin\Mail\Contracts\MailerContract;
use Plenty\Plugin\Templates\Twig;
use ReturnsPortal\Models\Return;

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
     * @param Return $return
     */
    public function sendReturnCreatedEmail(Return $return): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-created', [
            'return' => $return,
            'trackingUrl' => $this->getTrackingUrl($return->id)
        ]);

        $this->sendEmail(
            $return->customer_email,
            'Return Request Received - ' . $return->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return approved email
     * @param Return $return
     */
    public function sendReturnApprovedEmail(Return $return): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-approved', [
            'return' => $return,
            'labelUrl' => $this->getLabelUrl($return->id),
            'trackingUrl' => $this->getTrackingUrl($return->id)
        ]);

        $this->sendEmail(
            $return->customer_email,
            'Return Approved - ' . $return->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return rejected email
     * @param Return $return
     * @param string $reason
     */
    public function sendReturnRejectedEmail(Return $return, string $reason): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-rejected', [
            'return' => $return,
            'reason' => $reason
        ]);

        $this->sendEmail(
            $return->customer_email,
            'Return Rejected - ' . $return->return_number,
            $emailTemplate
        );
    }

    /**
     * Send return received email
     * @param Return $return
     */
    public function sendReturnReceivedEmail(Return $return): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.return-received', [
            'return' => $return,
            'trackingUrl' => $this->getTrackingUrl($return->id)
        ]);

        $this->sendEmail(
            $return->customer_email,
            'Return Received - ' . $return->return_number,
            $emailTemplate
        );
    }

    /**
     * Send refund processed email
     * @param Return $return
     */
    public function sendRefundProcessedEmail(Return $return): void
    {
        $emailTemplate = $this->twig->render('ReturnsPortal::emails.refund-processed', [
            'return' => $return
        ]);

        $this->sendEmail(
            $return->customer_email,
            'Refund Processed - ' . $return->return_number,
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
