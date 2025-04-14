<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "API Documentation",
    description: "API documentation for the application"
)]
#[OA\Server(
    url: "/",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "Bearer",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class ApiDocConfig
{
    // Ta klasa nie wymaga kodu, służy tylko do definicji globalnych atrybutów OpenAPI
} 