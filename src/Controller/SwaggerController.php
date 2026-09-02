<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to serve Swagger UI and OpenAPI documentation JSON.
 */
#[OA\Tag(name: 'Documentation')]
final class SwaggerController
{
    /**
     * SwaggerController constructor.
     */
    public function __construct(
        #[Autowire(service: 'nelmio_api_doc.controller.swagger_ui')]
        private readonly mixed $swaggerUiController,
        #[Autowire(service: 'nelmio_api_doc.controller.swagger')]
        private readonly mixed $documentationController,
    ) {
    }

    /**
     * Render the Swagger UI page.
     */
    #[Route('/api/doc', name: 'app_swagger_ui', methods: ['GET'])]
    #[Route('/api/v1/doc', name: 'app_swagger_ui_v1', methods: ['GET'])]
    public function ui(Request $request): Response
    {
        return ($this->swaggerUiController)($request, 'default');
    }

    /**
     * Render the OpenAPI JSON specification.
     */
    #[Route('/api/doc.json', name: 'app_swagger_json', methods: ['GET'])]
    #[Route('/api/v1/doc.json', name: 'app_swagger_json_v1', methods: ['GET'])]
    public function json(Request $request): Response
    {
        return ($this->documentationController)($request, 'default');
    }
}
