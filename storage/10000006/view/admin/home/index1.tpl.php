@extends('layout.web')
@section('title')
@getLang('app_404_error_title')
@endsection
@section('content')
fddf - test_var: {{$test_var}}<hr/>
{{$APP_THEME_PATH}}<hr/>
{{$APP_THEME_REALPATH}}
@endsection

