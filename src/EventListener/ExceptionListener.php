<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Listener to intercept exceptions in non-dev environments and format them as JSON or SOAP XML responses.
 */
#[AsEventListener(event: 'kernel.exception', priority: 10)]
final class ExceptionListener
{
    /**
     * Intercepts exceptions to convert them into JSON or XML SOAP Faults.
     *
     * @param ExceptionEvent $event The exception event.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Determine the HTTP status code
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An unexpected error occurred.';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }

        // Format REST errors list
        $errors = [];
        $previous = $exception->getPrevious();
        if ($previous instanceof \Symfony\Component\Validator\Exception\ValidationFailedException) {
            foreach ($previous->getViolations() as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            $message = 'Validation Failed';
        } else {
            $errors[] = [
                'field' => null,
                'message' => $message,
            ];
        }

        // Log the exception to var/log/error.log.
        $this->logException($exception, $statusCode);

        // For REST/other endpoints, return a JSON response.
        $response = new JsonResponse([
            'status' => 'error',
            'code' => $statusCode,
            'errors' => $errors,
        ], $statusCode);

        $event->setResponse($response);
    }

    /**
     * Writes the exception details and stack trace to the log file.
     */
    private function logException(\Throwable $exception, int $statusCode): void
    {
        $logDir = dirname(__DIR__, 2) . '/var/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logMessage = sprintf(
            "[%s] [%d] %s: %s\nStack trace:\n%s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            $statusCode,
            get_class($exception),
            $exception->getMessage(),
            $exception->getTraceAsString()
        );
        $logFileName = sprintf('error-%s.log', date('Y-m-d'));
        @file_put_contents($logDir . '/' . $logFileName, $logMessage, FILE_APPEND);
    }
}
