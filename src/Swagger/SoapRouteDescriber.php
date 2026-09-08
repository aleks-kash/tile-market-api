<?php

namespace App\Swagger;

use App\Command\SeedOrdersCommand;
use App\Controller\SoapController;
use App\Enum\CurrencySid;
use App\Enum\PayTypeSid;
use App\Enum\VatTypeSid;
use Faker\Factory;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Annotations\Operation;
use OpenApi\Context;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Custom OpenAPI RouteDescriber for SoapController endpoints.
 */
final class SoapRouteDescriber extends AbstractRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    /**
     * @inheritdoc
     */
    protected string $tagName = 'SOAP Service';

    /**
     * @param SerializerInterface $serializer Symfony serializer for building XML SOAP examples.
     */
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {}

    /**
     * Describes the SOAP controller route for OpenAPI documentation.
     *
     * Generates OpenAPI schema for SOAP endpoints including:
     * - GET operation: WSDL schema download with ?wsdl query parameter
     * - POST operation: Multiple SOAP method examples (createEmptyOrder, updateOrder, addArticleToOrder)
     *
     * Uses Faker to generate realistic example data in SOAP request bodies.
     *
     * @param OA\OpenApi $api The OpenAPI specification instance.
     * @param Route $route The Symfony route being described.
     * @param \ReflectionMethod $reflectionMethod Reflection of the controller method.
     * @return void
     *
     * @throws ExceptionInterface If XML serialization of SOAP examples fails.
     */
    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if ($reflectionMethod->getDeclaringClass()->getName() !== SoapController::class) {
            return;
        }

        $this->addTagDescription($api, 'Requests to save, create or update data.');
        $path = Util::getPath($api, $this->normalizePath($route->getPath()));

        $faker = Factory::create();
        $faker->seed(12345);

        // Request to get WSDL.
        $this->prepareWSDLRequest($path);

        // POST operation for SOAP web service.
        $operation = Util::getOperation($path, 'post');
        $operation->summary = 'Execute SOAP Order Actions';
        $operation->description = 'Processes SOAP XML requests for Order management (createOrder, updateOrder, addArticleToOrder).';

        $this->preparePostSOAPRequest($operation, [
            [
                'action' => 'createEmptyOrder',
                'summary' => 'Create a new order',
                'description' => 'Creating a new order with a given name.',
                'body' => [
                    'name' => 'Order #' . $faker->numberBetween(1000, 9999),
                ],
            ],
            [
                'action' => 'createEmptyOrder',
                'summary' => 'Create a new order and empty name',
                'description' => 'If the name is missing, a default "Draft Order" will be created.',
                'body' => [
                    'name' => '',
                ],
            ],
            [
                'action' => 'updateOrder',
                'summary' => 'Update order details & delivery data',
                'description' => 'Updating an order details and delivery data.',
                'body' => [
                    'data' => [
                        'orderHash' => SeedOrdersCommand::PREDEFINED_HASH,
                        'clientName' => $faker->firstName(),
                        'clientSurname' => $faker->lastName(),
                        'companyName' => $faker->company(),
                        'taxNumber' => $faker->numerify('1##########'),
                        'email' => $faker->safeEmail(),
                        'description' => 'Updated order notes and details',
                        'payType' => PayTypeSid::randomId(),
                        'currency' => CurrencySid::randomSid(),
                        'personalDataAgree' => '1',
                        'delivery' => [
                            'country' => $faker->numberBetween(100, 899),
                            'index' => $faker->postcode(),
                            'region' => $faker->state(),
                            'city' => $faker->city(),
                            'street' => $faker->streetName(),
                            'building' => (string) $faker->buildingNumber(),
                            'apartmentOffice' => (string) $faker->numberBetween(1, 50),
                            'kladrId' => '1000000000000',
                            'okatoId' => '45000000000',
                            'phone' => $faker->phoneNumber(),
                            'phoneCode' => '1',
                        ],
                        'vat' => [
                            'type' => VatTypeSid::randomId(),
                        ],
                    ],
                ],
            ],
            [
                'action' => 'addArticleToOrder',
                'summary' => 'Add article item to order',
                'description' => 'Adding an article to an existing order.',
                'body' => [
                    'data' => [
                        'orderHash' => SeedOrdersCommand::PREDEFINED_HASH,
                        'articleId' => $faker->numberBetween(100, 999),
                        'price' => $faker->randomFloat(2, 10, 200),
                        'amount' => $faker->randomFloat(1, 1, 50),
                    ],
                ],
            ],
            [
                'action' => 'addArticleToOrder',
                'summary' => 'Add article item to NEW order',
                'description' => 'If orderHash is not passed, the following actions will occur:' .
                    "an attempt is made to find an order by the user's token and return their order;" .
                    "otherwise, a new order will be created;" .
                    "if several orders were found for a given user, we will take the first one;",
                'body' => [
                    'data' => [
                        'orderHash' => '',
                        'articleId' => $faker->numberBetween(100, 999),
                        'price' => $faker->randomFloat(2, 10, 200),
                        'amount' => $faker->randomFloat(1, 1, 50),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Prepares the WSDL request operation for the SOAP service.
     *
     * @param OA\PathItem $path OpenAPI path item to configure.
     * @return void
     */
    private function prepareWSDLRequest(OA\PathItem $path): void
    {
        $operation = Util::getOperation($path, 'get');
        $operation->summary = 'Download SOAP WSDL XML Definition';
        $operation->description = 'Returns the dynamically generated WSDL XML schema definition for SOAP order web service when requested with ?wsdl query param.';
        $operation->tags = [$this->tagName];

        $wsdlParam = new OA\Parameter([
            'name' => 'wsdl',
            'in' => 'query',
            'description' => 'Query flag to request WSDL schema definition',
            'required' => false,
            '_context' => new Context(['nested' => $operation]),
        ]);
        $wsdlParam->schema = new OA\Schema([
            'type' => 'string',
            'example' => 'wsdl',
            '_context' => new Context(['nested' => $wsdlParam]),
        ]);

        $operation->parameters = [$wsdlParam];

        $exampleWsdlXml = '<?xml version="1.0" encoding="UTF-8"?><definitions xmlns="http://schemas.xmlsoap.org/wsdl/" name="OrderService" targetNamespace="http://localhost:8080/soap/orders"/>';

        $mediaType = new OA\MediaType([
            'mediaType' => 'text/xml',
            'example' => $exampleWsdlXml,
        ]);
        $mediaType->schema = new OA\Schema([
            'type' => 'string',
            'example' => $exampleWsdlXml,
            '_context' => new Context(['nested' => $mediaType]),
        ]);

        $response = new OA\Response([
            'response' => '200',
            'description' => 'WSDL XML document',
            '_context' => new Context(['nested' => $operation]),
        ]);
        $response->content = [
            'text/xml' => $mediaType,
        ];

        $operation->responses = [$response];
    }

    /**
     * Prepares the POST SOAP request operation for the SOAP service with multiple examples.
     *
     * @param Operation $operation OpenAPI operation to configure.
     * @param array<int|string, array{
     *     action: string,
     *     body: array,
     *     summary?: string,
     *     description?: string
     * }> $examplesConfig Configuration for generating request examples, where each array item contains:
     *   - 'action' (string): The SOAP action/method name (used as element name inside SOAP-ENV:Body).
     *   - 'body' (array): Payload data for the SOAP action to be serialized into XML body content.
     *   - 'summary' (string, optional): Short summary title for the OpenAPI request example.
     *   - 'description' (string, optional): Detailed explanation of the scenario covered by the example.
     *
     * @throws ExceptionInterface If XML serialization fails.
     */
    private function preparePostSOAPRequest(Operation $operation, array $examplesConfig): void
    {
        $operation->tags = [$this->tagName];

        $oaExamples = [];
        $firstXml = null;

        foreach ($examplesConfig as $key => $config) {
            $requestData = [
                '@xmlns:SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/',
                'SOAP-ENV:Body' => [$config['action'] => $config['body']],
            ];

            $xml = $this->serializer->serialize(
                $requestData,
                'xml',
                [
                    'xml_root_node_name' => 'SOAP-ENV:Envelope',
                    'xml_format_output' => true,
                    'xml_encoding' => 'UTF-8',
                    'xml_version' => '1.0',
                ]
            );

            if ($firstXml === null) {
                $firstXml = $xml;
            }

            $actionKey = $config['action'] . $key;
            $oaExamples[$actionKey] = new OA\Examples([
                'example' => $actionKey,
                'summary' => $config['summary'] ?? $actionKey,
                'description' => $config['description'] ?? '',
                'value' => $xml,
            ]);
        }

        $requestBodyMediaType = new OA\MediaType([
            'mediaType' => 'text/xml',
            'examples' => $oaExamples,
        ]);
        $requestBodyMediaType->schema = new OA\Schema([
            'type' => 'object',
            'xml' => new OA\Xml([
                'name' => 'Envelope',
                'namespace' => 'http://schemas.xmlsoap.org/soap/envelope/',
                'prefix' => 'SOAP-ENV',
            ]),
            '_context' => new Context(['nested' => $requestBodyMediaType]),
        ]);

        $requestBody = new OA\RequestBody([
            'description' => 'SOAP 1.0 XML Request envelope',
            'required' => true,
            '_context' => new Context(['nested' => $operation]),
        ]);
        $requestBody->content = [
            'text/xml' => $requestBodyMediaType,
        ];
        $operation->requestBody = $requestBody;

        $response = new OA\Response([
            'response' => '200',
            'description' => 'SOAP 1.0 XML Response envelope or SOAP Fault.',
            '_context' => new Context(['nested' => $operation]),
        ]);
        $operation->responses = [$response];
    }
}
