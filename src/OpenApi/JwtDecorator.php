<?php
declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ArrayObject;

final class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();
        $securitySchemes = $components->getSecuritySchemes() ?: new ArrayObject();

        // Define only one security scheme
        $securitySchemes['bearerAuth'] = new SecurityScheme('http', 'bearer', 'JWT');

        // Apply it globally
        $openApi = $openApi->withComponents(
            $components->withSecuritySchemes($securitySchemes)
        )->withSecurity([['bearerAuth' => []]]);

        return $openApi;
    }
}
