<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $article->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }
        h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .metadata {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .content {
            font-size: 16px;
        }
        .content h2 {
            font-size: 20px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .content p {
            margin-bottom: 10px;
        }
        .content ul, .content ol {
            margin-bottom: 10px;
            padding-left: 20px;
        }
        .content img {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
        }
        .content a {
            color: #2563eb;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>{{ $article->title }}</h1>
    
    <div class="metadata">
        <p>Department: {{ $article->department?->name ?? 'General' }}</p>
        <p>Author: {{ $article->author->name }}</p>
        <p>Created: {{ $article->created_at->format('Y-m-d') }}</p>
    </div>

    <div class="content">
        {!! $article->content !!}
    </div>
</body>
</html>
