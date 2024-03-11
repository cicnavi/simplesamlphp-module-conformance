<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Factories;

use PHPMailer\PHPMailer\Exception;
use SimpleSAML\Module\conformance\Errors\ConformanceException;
use SimpleSAML\Utils\EMail;

class EmailFactory
{
    /**
     * @throws ConformanceException
     */
    public function build(
        string $subject,
        string $from = null,
        string $to = null,
        string $txtTemplate = 'mailtxt.twig',
        string $htmlTemplate = 'mailhtml.twig'
    ): EMail {
        try {
            return new EMail($subject, $from, $to, $txtTemplate, $htmlTemplate);
        } catch (Exception $e) {
            throw new ConformanceException('Unable to create Mail instance. Error was: ' . $e->getMessage());
        }
    }
}
