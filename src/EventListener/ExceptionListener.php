<?php

namespace App\EventListener;

use App\Exception\SoapValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

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
        } elseif ($this->isValidHttpStatusCode((int) $exception->getCode())) {
            $statusCode = (int) $exception->getCode();
        }

        // Format REST errors list
        /** @var list<array{field: string|null, message: string}> $errors */
        $errors = [];
        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
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

        // For SOAP endpoints, build and return SOAP Fault XML response.
        if ($this->isSoapRequest($request)) {
            $s_soap_fault_xml = $this->buildSoapFaultXml($exception, $errors);
            $o_response = new Response(
                $s_soap_fault_xml,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            );
            $event->setResponse($o_response);
            return;
        }

        // For REST/other endpoints, return a JSON response.
        $response = new JsonResponse([
            'status' => 'error',
            'code' => $statusCode,
            'errors' => $errors,
        ], $statusCode);

        $event->setResponse($response);
    }

    /**
     * Detect if the request should be treated as SOAP / XML.
     *
     * @param Request $request Current HTTP request.
     *
     * @return bool True when request is SOAP/XML-like.
     */
    private function isSoapRequest(Request $request): bool
    {
        $contentType = (string) $request->headers->get('Content-Type');
        $path = $request->getPathInfo();

        return str_starts_with($path, '/api/v1/soap')
            || $request->headers->has('SOAPAction')
            || str_contains($contentType, 'xml');
    }

    /**
     * Builds SOAP fault XML payload from exception and validation errors.
     *
     * @param \Throwable $exception Current thrown exception.
     * @param list<array{field: string|null, message: string}> $a_errors Validation errors array.
     * @return string SOAP fault XML payload.
     */
    private function buildSoapFaultXml(\Throwable $exception, array $a_errors): string
    {
        $s_fault_code = 'Server';
        $s_fault_message = $exception->getMessage();
        /** @var list<array{field: string, message: string}> $a_error_list */
        $a_error_list = [];

        if ($exception instanceof SoapValidationException) {
            $s_fault_code = 'Client';
            $s_fault_message = 'Validation failed';
            $a_error_list = $exception->getErrorList();
        } elseif ($exception instanceof \SoapFault) {
            $s_fault_code = (string) ($exception->faultcode ?? 'Server');
            $s_fault_message = $exception->getMessage();

            if (is_array($exception->detail) && isset($exception->detail['error_list']) && is_array($exception->detail['error_list'])) {
                foreach ($exception->detail['error_list'] as $x_error) {
                    if (is_array($x_error) && isset($x_error['field'], $x_error['message'])) {
                        $a_error_list[] = [
                            'field' => (string) $x_error['field'],
                            'message' => (string) $x_error['message'],
                        ];
                        continue;
                    }

                    if (is_string($x_error)) {
                        $a_error_list[] = [
                            'field' => '',
                            'message' => $x_error,
                        ];
                        continue;
                    }
                }
            }
        } else {
            if (!empty($a_errors)) {
                foreach ($a_errors as $a_error) {
                    $a_error_list[] = [
                        'field' => $a_error['field'] ?? '',
                        'message' => $a_error['message'],
                    ];
                }
            }
        }

        $s_detail_xml = '';

        if ($a_error_list) {
            $s_error_item_xml = '';

            foreach ($a_error_list as $a_error) {
                $s_error_item_xml .= sprintf(
                    '<item><field>%s</field><message>%s</message></item>',
                    htmlspecialchars($a_error['field'], ENT_XML1, 'UTF-8'),
                    htmlspecialchars($a_error['message'], ENT_XML1, 'UTF-8'),
                );
            }

            $s_detail_xml = sprintf('<detail><error_list>%s</error_list></detail>', $s_error_item_xml);
        }

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body><soap:Fault><faultcode>soap:%s</faultcode><faultstring>%s</faultstring>%s</soap:Fault></soap:Body>'
            . '</soap:Envelope>',
            htmlspecialchars($s_fault_code, ENT_XML1, 'UTF-8'),
            htmlspecialchars($s_fault_message, ENT_XML1, 'UTF-8'),
            $s_detail_xml,
        );
    }

    /**
     * Writes the exception details and stack trace to the log file.
     *
     * @param \Throwable $exception Thrown exception.
     * @param int $statusCode HTTP status code.
     *
     * @return void
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

    /**
     * Checks that HTTP status code is within valid Response range.
     *
     * @param int $statusCode HTTP status code to validate.
     * @return bool True if valid HTTP status code, false otherwise.
     */
    private function isValidHttpStatusCode(int $statusCode): bool
    {
        return $statusCode >= 100 && $statusCode < 600;
    }
}
