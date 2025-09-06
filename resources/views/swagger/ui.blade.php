<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.10.3/favicon-32x32.png" sizes="32x32" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *, *:before, *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        .swagger-ui .topbar {
            background-color: #2c3e50;
        }

        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }

        .swagger-ui .info {
            margin: 50px 0;
        }

        .swagger-ui .info .title {
            color: #2c3e50;
        }

        .custom-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 0;
        }

        .custom-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }

        .custom-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>SU'UD API Documentation</h1>
        <p>Comprehensive API documentation for the SU'UD project backend</p>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
    <script>
        window.onload = function() {
            // Build a system
            const ui = SwaggerUIBundle({
                spec: @json($swaggerJson),
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                displayOperationId: false,
                displayRequestDuration: true,
                docExpansion: 'list',
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
                syntaxHighlight: {
                    activated: true,
                    theme: "agate"
                },
                tryItOutEnabled: true,
                requestInterceptor: function(request) {
                    // Add any custom headers or modify requests here
                    return request;
                },
                responseInterceptor: function(response) {
                    // Handle responses here
                    return response;
                }
            });
        };
    </script>
</body>
</html>
