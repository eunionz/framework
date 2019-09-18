@extends('layout.web')
@section('title')
@getLang('app_404_error_title')
@endsection
@section('content')
{{$APP_VERSION}}<br/>
{{APP_PATH}}<br/>
{{$APP_THEME}}<br/>
{{$APP_THEME_PATH}}<br/>
{{$APP_THEME_PATH_NO_CDN}}<br/>
{{$APP_THEME_REALPATH}}
@include("mobile.home.a" , [])
<script type="text/javascript">
    var all_langs = {!! $js_all_langs !!};
</script>

@endsection

