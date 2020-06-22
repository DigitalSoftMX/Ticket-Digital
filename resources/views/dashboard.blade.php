@extends('layouts.app', ['activePage' => 'dashboard', 'titlePage' => __('Dashboard')])

@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="container-fluid mt-5">
          @if(auth()->user()->roles[0]->name == 'admin_master')
            <div class="row">
              
            </div>
          @endif
        </div>
    </div>
</div>
@endsection
