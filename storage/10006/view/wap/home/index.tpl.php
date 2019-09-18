@extends('layout.web')
@section('title')
@getLang('app_404_error_title')
@endsection
@section('content')
fddf<hr/>
{{$APP_THEME_PATH}}<hr/>
{{$APP_THEME_REALPATH}}
<hr/>
{{$page}}
<hr/>
{{$all_langs['test']}}

=======<br/>
{{$APP_VERSION}}<br/>
{{APP_PATH}}<br/>
{{$APP_THEME}}<br/>
{{$APP_THEME_PATH}}<br/>
{{$APP_THEME_PATH_NO_CDN}}<br/>
{{$APP_THEME_REALPATH}}
=======<br/>
<script type="text/javascript">
    var all_langs = {!! $js_all_langs !!};
    // alert(all_langs.test);
</script>

@endsection

