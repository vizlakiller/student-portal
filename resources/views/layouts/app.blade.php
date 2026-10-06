<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Student Portal')</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; }
        .card { max-width: 380px; margin: 60px auto; background: #fff; padding: 28px;
                border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        label { display: block; margin-top: 14px; font-size: 14px; }
        input { width: 100%; padding: 9px; margin-top: 4px; border: 1px solid #ccc;
                border-radius: 6px; box-sizing: border-box; }
        input[type=checkbox] { width: auto; margin-right: 6px; }
        button { width: 100%; margin-top: 20px; padding: 10px; background: #1d4ed8;
                 color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        .error { color: #b91c1c; font-size: 13px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="card">
        @yield('content')
    </div>
</body>
</html>