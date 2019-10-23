@extends('layout.web')
@section('title')
@getLang('app_404_error_title')
@endsection
@section('content')

<hr/>
页面执行时间：__PAGE_EXECUTE_SECONDS__ 秒
数据库查询次数：__PAGE_EXECUTE_QUERYS__
<hr/>
fddf - test_var: {{$test_var}}<hr/>
{{$APP_THEME_PATH}}<hr/>
{{$APP_THEME_REALPATH}}<hr/>
{{$page}}
<hr/>
{{$all_langs['test']}}
<hr/>
{{$SESSION['login_user'][0]}}
<hr/>
{{--
clientversion={{$GET['clientversion'] or 0}}

--}}
<hr/>

<hr/>
@if(isset($GET['clientversion']) && $GET['clientversion']) {{$GET['clientversion']}} @else 0 @endif
<hr/>
@foreach($GET as $key => $val)
    {{$key}} = {{$val}} <br/>
@endforeach
<hr/>
<hr/>
@foreach($arr as $key => $val)
    {{$key}} =>
    @foreach($val as $k => $v)
        {{$k}} = {{$v}}
    @endforeach
    <br/>
@endforeach
<hr/>
@for($i = 0; $i < count($arr1); $i++)
    {{$i}} =>
    @for($j = 0; $j < count($arr1[$i]); $j++)
        {{$j}} => {{$arr1[$i][$j]}}
    @endfor
    <br/>
@endfor
<hr/>
<?php $i=0;?>
@while($i<count($arr1))
    <?php $j=0;?>
    @while($j<count($arr1[$i]))
        {{$i}} => {{$arr1[$i][$j]}}
        <?php $j++;?>
    @endwhile
    <?php $i++;?>
    <br/>
@endwhile
<hr/>
APP_PACKAGE_BASE_PATH = {{APP_PACKAGE_BASE_PATH}}
<?PHP
    print_r($SESSION);
    print_r($COOKIE);
print_r($SERVER);
print_r($HEADER);
print_r($GET);
print_r($POST);
print_r($REQUEST);
print_r($FILES);

?>


<hr/>
@getLang('error_webservice_operation','333')
<hr/>
<script type="text/javascript">
    var js_headers = {!! $js_headers !!};
    var all_langs = {!! $js_all_langs !!};
    // alert(all_langs.test);
</script>
<script type="text/javascript" src="{{APP_PATH}}www/js/websocket.js"></script>

<script type="text/javascript">
    var wsuri = "wss://www.ihltx.com/websocket/home/index/44/6.shtml?a=44&b=44";
    EunionzWebsocket.onmessage=function (e) {
        alert('接收到的数据为：' + e.data);
    };
    EunionzWebsocket.init(wsuri);
    function send() {
        var msg = document.getElementById('message').value;
        EunionzWebsocket.send(msg);
    };

</script>
<div id="sse">
    <input type="text" id="message">
    <a href="javascript:send()">发送数据</a>
</div>
<hr/>====================================================<hr/>
@loadConfig('conf/1')
<hr/><hr/><hr/><hr/>

@for($i = 0; $i < $b; $i++)
<h1>{{$i}}</h1>
@include('home/home/a', ['aaa'=>APP_PACKAGE_BASE_PATH])
@endfor
<hr/><hr/><hr/><hr/>

@endsection

