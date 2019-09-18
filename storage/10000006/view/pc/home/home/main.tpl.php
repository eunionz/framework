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
<hr/><hr/>
{!! $html !!}
<hr/><hr/>
{{$APP_THEME_REALPATH}}
<script type="text/javascript">
    var all_langs = {!! $js_all_langs !!};
</script>

@endsection

