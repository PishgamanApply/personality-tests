<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستندات تعاملی API - سامانه ارزیابی و آزمون‌های روان‌شناختی</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5/favicon-32x32.png" sizes="32x32" />
    <style>
        html {
            box-sizing: border-box;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #f8fafc;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .custom-banner {
            background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
            color: #ffffff;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        @if(request()->boolean('embedded'))
        .custom-banner {
            display: none !important;
        }
        body {
            background: #ffffff;
        }
        #swagger-ui {
            padding: 0 10px;
        }
        @endif

        .custom-banner h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 600;
        }
        .custom-banner .banner-links {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .custom-banner a.btn {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .custom-banner a.btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        /* Swagger UI container is LTR because endpoints and JSON schemas are Latin */
        #swagger-ui {
            direction: ltr;
            max-width: 1300px;
            margin: 0 auto;
            padding: 10px;
        }
        .swagger-ui .topbar {
            display: none; /* Hide default swagger topbar in favor of our custom banner */
        }
        .swagger-ui .info {
            margin: 20px 0;
        }
        .swagger-ui .info .title {
            font-size: 1.8rem;
            color: #1e1b4b;
        }
    </style>
</head>
<body>
    <div class="custom-banner">
        <div>
            <h1>مستندات رسمی وب‌سرویس‌ها (Swagger UI - OpenAPI 3.0)</h1>
        </div>
        <div class="banner-links">
            <a href="{{ route('docs.openapi') }}" download="openapi.yaml" class="btn">📥 دانلود فایل openapi.yaml</a>
            <a href="{{ route('docs.guide') }}" class="btn">📖 راهنمای ادغام (Integration Guide)</a>
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
    window.onload = function() {
        window.ui = SwaggerUIBundle({
            url: "{{ route('docs.openapi') }}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "BaseLayout",
            persistAuthorization: true,
            displayRequestDuration: true,
            filter: true,
            docExpansion: "list",
            defaultModelsExpandDepth: 2,
            defaultModelExpandDepth: 2
        });
    };
    </script>
</body>
</html>

