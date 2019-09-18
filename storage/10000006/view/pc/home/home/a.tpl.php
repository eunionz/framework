a.tpl.php

<hr/>
@if(isset($GET['clientversion']) && $GET['clientversion']) {{$GET['clientversion']}} @else 0 @endif
<hr/>
{{$aaa}}
=======<br/>
{{$APP_VERSION}}<br/>
{{APP_PATH}}<br/>
{{$APP_THEME}}<br/>
{{$APP_THEME_PATH}}<br/>
{{$APP_THEME_PATH_NO_CDN}}<br/>
{{$APP_THEME_REALPATH}}
=======<br/>