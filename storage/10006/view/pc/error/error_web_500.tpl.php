@extends('layout.error_web')@section('content')
<!--
@getLang('error_exception_title'){{$Exception['title']}}<br/>
@getLang('error_exception_message'){{$Exception['message']}}<br/>
@getLang('error_exception_code'){{$Exception['code']}}<br/>
@getLang('error_exception_file'){{$Exception['file']}}<br/>
@getLang('error_exception_line'){{$Exception['line']}}<br/>
@getLang('error_exception_trace'){{$Exception['trace']}}<br/>
-->
<style type="text/css">
    .error_wen{color:#adadad;line-height:32px;font-weight:bold;}
    .error_icon{padding-top:100px;}
    .error_icon .iconfont{font-size:80px;color:#fcab2b;}
    .error_wen a{color:#4972ff;}
</style>
<p class="error_icon text-center"><i class="icon iconfont icon-iconjinggao01"></i></p>
<div class="error_wen text-center">
    @getLang('app_500_error_content') <br/>
    @getLang('error_exception_message'){{$Exception['message']}}<br/>
    <a href="/admin/home/index.html">>>@getLang('app_500_error_title',#28; 2+4 #29;,'web')</a>
</div>
@endsection

