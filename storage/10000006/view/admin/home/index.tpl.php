@extends('layout.web')
@section('title')
@getLang('app_404_error_title')
@endsection
@section('content')
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
<hr/>
@include('home/a', ['aaa'=>APP_PACKAGE_BASE_PATH])
<hr/>
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
    var all_langs = {!! $js_all_langs !!};
    // alert(all_langs.test);
</script>

@endsection

