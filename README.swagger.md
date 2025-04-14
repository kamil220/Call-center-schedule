# Swagger/OpenAPI Documentation

This project uses Swagger/OpenAPI for API documentation and interactive testing.

## Accessing Swagger UI

When the application is running, you can access the Swagger UI at:

```
http://localhost:8080/api/doc
```

The JSON representation of the API documentation is available at:

```
http://localhost:8080/api/doc.json
```

## Features

- Interactive documentation of all API endpoints
- Test API calls directly from the browser
- Detailed request/response schemas
- Automatic generation of documentation from code annotations

## Adding Documentation to Your Controllers

To add documentation to your controllers, use the OpenAPI PHP attributes. Here's an example:

```php
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Your API Group')]
class YourController extends AbstractController
{
    #[Route('/api/resource', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Success response',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'))
            ]
        )
    )]
    #[OA\Parameter(
        name: 'param',
        in: 'query',
        description: 'Query parameter',
        schema: new OA\Schema(type: 'string')
    )]
    public function yourAction(): JsonResponse
    {
        // Your implementation
    }
}
```

## Configuration

The Swagger configuration can be found in `config/packages/nelmio_api_doc.yaml`.

If you need to customize the documentation or add additional information, modify this configuration file.

## Authentication

The API documentation is configured to support Bearer token authentication. If your API endpoints require authentication, you'll see a button to authorize in the Swagger UI.

## Further Resources

- [NelmioApiDocBundle Documentation](https://github.com/nelmio/NelmioApiDocBundle)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger UI](https://swagger.io/tools/swagger-ui/) 