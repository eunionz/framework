<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@getLang('application_name') - @yield('title')</title>
</head>
<body>
<div class="header">
    @yield('header')
</div>
<div class="container">
    @yield('content')
</div>
<div class="footer">
    @yield('footer')
</div>
</body>
</html>