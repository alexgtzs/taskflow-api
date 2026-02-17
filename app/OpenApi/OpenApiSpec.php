<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'TaskFlow API',
    description: 'RESTful API for task and project management. Built with Laravel, Sanctum authentication, and role-based access control.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@taskflow.dev'
    )
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API V1'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    bearerFormat: 'token',
    scheme: 'bearer'
)]
class OpenApiSpec {}