<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ApiDocsController
{
    #[Route('/docs', name: 'api_docs_ui', methods: ['GET'])]
    public function ui(): Response
    {
        return new Response(
            <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>GWO Recruitment API Docs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
  <style>
    body { margin: 0; background: #fafafa; }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    window.ui = SwaggerUIBundle({
      url: '/openapi.yaml',
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [SwaggerUIBundle.presets.apis],
    });
  </script>
</body>
</html>
HTML,
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    #[Route('/openapi.yaml', name: 'api_docs_spec', methods: ['GET'])]
    public function spec(): Response
    {
        $path = dirname(__DIR__, 2).'/.misc/openapi/openapi.yaml';
        $content = file_get_contents($path);

        if ($content === false) {
            return new Response('OpenAPI file not found.', Response::HTTP_NOT_FOUND);
        }

        return new Response(
            $content,
            Response::HTTP_OK,
            ['Content-Type' => 'application/yaml; charset=UTF-8'],
        );
    }
}
