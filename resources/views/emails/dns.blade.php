@extends('layouts.app', ['activePage' => '', 'titlePage' => 'Error DNS'])

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header card-header-primary">
                            <h4 class="card-title text-center">{{ __('Error de conexión') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="card-category"> Buen día, se reporta un error de conexión con la estación
                                <strong>{{ $stn->name }}</strong>
                            </p>
                            <p>con el servidor DNS: <strong>"{{ $stn->dns }}"</strong></p>
                            <p>El error se registró a las <strong>
                                    {{ Carbon\Carbon::parse($stn->fail)->format('H:i:s') }}
                                </strong>
                                hrs del día
                                <strong>{{ Carbon\Carbon::parse($stn->fail)->format('Y-m-d') }}</strong>
                            </p>
                            <p>Saludos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
