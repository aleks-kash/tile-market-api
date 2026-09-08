<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;
use OpenApi\Context;

abstract class AbstractRouteDescriber
{
    /**
     * The name of the tag.
     *
     * @var string
     */
    protected string $tagName = 'Default';

    /**
     * Adds a tag to the OpenAPI specification if it doesn't already exist.
     *
     * @param OA\OpenApi $api The OpenAPI specification
     * @param string $tagDescription The description of the tag
     */
    protected function addTagDescription(OA\OpenApi $api, string $tagDescription): void
    {
        if (!is_array($api->tags)) {
            $api->tags = [];
        }

        $tagExists = false;
        foreach ($api->tags as $tag) {
            if ($tag instanceof OA\Tag && $tag->name === $this->tagName) {
                $tagExists = true;
                break;
            }
        }

        if (!$tagExists) {
            $api->tags[] = new OA\Tag([
                'name' => $this->tagName,
                'description' => $tagDescription,
                '_context' => new Context(['nested' => $api]),
            ]);
        }
    }
}
