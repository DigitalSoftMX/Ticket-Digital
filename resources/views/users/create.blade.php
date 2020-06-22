@extends('layouts.app', ['activePage' => 'Usuarios', 'titlePage' => __('Gestión de usuarios')])

@section('content')
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-10 mx-auto d-block mt-3">
          
          <form method="post" action="{{ route('user.store') }}" autocomplete="off" class="form-horizontal">
            @csrf
            @method('post')

            <div class="card">
              <div class="card-header card-header-primary">
                <h4 class="card-title">{{ __('Agregar usuario') }}</h4>
              </div>

              <div class="card-body">
                <div class="row mb-4">
                  <div class="col-12 text-left">
                    <a href="{{ route('user.index') }}" class="btn btn-social btn-just-icon btn-primary" title="Regresar a la lista">
                        <i class="material-icons">arrow_back_ios</i>
                    </a>
                  </div>
                </div>

                <div class="row">
                  <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }} col-sm-4">
                    <label for="name">{{ __('Nombre') }}</label>
                    <input type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" id="input-name" aria-describedby="nameHelp" placeholder="Escribe un nombre" value="{{ old('name')}}" aria-required="true" name="name">
                    @if ($errors->has('name'))
                      <span id="name-error" class="error text-danger" for="input-name">
                        {{ $errors->first('name') }}
                      </span>
                    @endif
                  </div>

                  <div class="form-group{{ $errors->has('app_name') ? ' has-danger' : '' }} col-sm-4">
                    <label for="app_name">{{ __('Apellido Paterno') }}</label>
                    <input type="text" class="form-control{{ $errors->has('app_name') ? ' is-invalid' : '' }}" name="app_name" id="input-app_name" value="{{ old('app_name') }}" aria-required="true" aria-describedby="app_nameHelp" placeholder="Escribe el primer apellido" aria-required="true">
                    @if ($errors->has('app_name'))
                      <span id="app_name-error" class="error text-danger" for="input-app_name">
                        {{ $errors->first('app_name') }}
                      </span>
                    @endif
                  </div>

                  <div class="form-group{{ $errors->has('apm_name') ? ' has-danger' : '' }} col-sm-4">
                    <label for="app_name">{{ __('Apellido Materno') }}</label>
                    <input type="text" class="form-control{{ $errors->has('apm_name') ? ' is-invalid' : '' }}" name="apm_name" id="input-apm_name" value="{{ old('apm_name') }}" aria-required="true" aria-describedby="apm_nameHelp" placeholder="Escribe el segundo apellido" aria-required="true">
                    @if ($errors->has('apm_name'))
                        <span id="apm_name-error" class="error text-danger" for="input-apm_name">{{ $errors->first('apm_name') }}</span>
                      @endif
                  </div>
                </div>

                <div class="row mt-3">
                  <div class="form-group{{ $errors->has('username') ? ' has-danger' : '' }} col-sm-4">
                    <label for="username">{{ __('Nombre de usuario') }}</label>
                    <input type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" id="input-username" value="{{ old('username') }}" aria-required="true" aria-describedby="usernameHelp" placeholder="Escribe el nombre usuario" >
                    @if ($errors->has('username'))
                      <span id="username-error" class="error text-danger" for="input-username">
                        {{ $errors->first('username') }}
                       </span>
                    @endif
                  </div>

                  <div class="form-group{{ $errors->has('phone') ? ' has-danger' : '' }} col-sm-4">
                    <label for="phone">{{ __('Teléfono') }}</label>
                    <input type="tel" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" id="input-phone" aria-describedby="phoneHelp" placeholder="Escribe un numero de telefono" value="{{ old('phone') }}" aria-required="true" name="phone">
                    @if ($errors->has('phone'))
                      <span id="phone-error" class="error text-danger" for="input-phone">
                        {{ $errors->first('phone') }}
                      </span>
                    @endif
                  </div>

                  <div class="form-group{{ $errors->has('direccion') ? ' has-danger' : '' }} col-sm-4">
                    <label for="direccion">{{ __('Dirección') }}</label>
                    <input type="text" class="form-control{{ $errors->has('direccion') ? ' is-invalid' : '' }}" name="direccion" id="input-direccion" value="{{ old('direccion') }}" aria-required="true" aria-describedby="direccionHelp" placeholder="Escribe la direccion"  aria-required="true">
                    @if ($errors->has('direccion'))
                      <span id="direccion-error" class="error text-danger" for="input-direccion">
                        {{ $errors->first('direccion') }}
                      </span>
                    @endif
                  </div>
                </div>

                <div class="row mt-3">
                  <div class="form-group{{ $errors->has('rol') ? ' has-danger' : '' }} col-sm-3">
                    <label for="input-rol">Rol</label>
                    <select id="input-rol" name="rol" class="selectpicker show-menu-arrow{{ $errors->has('rol') ? ' is-invalid' : '' }}" data-style="btn-primary" data-live-search="true" data-width="100%">
                      @foreach($roles as $rol)
                        @if($rol->id == 1 )
                          <option value="{{ $rol->id }}" selected>{{ $rol->name }}</option>
                        @else
                          <option value="{{ $rol->id }}">{{ $rol->name }}</option>
                        @endif
                      @endforeach
                    </select>
                  </div>

                  <div class="form-group{{ $errors->has('sex') ? ' has-danger' : '' }} col-sm-3">
                    <label for="input-sex">Genero</label>
                      <select id="input-sex" name="sex" class="selectpicker show-menu-arrow {{ $errors->has('sex') ? ' is-invalid' : '' }}" data-style="btn-primary" data-width="100%" data-live-search="true">
                          <option value="1" selected>Femenino</option>
                          <option value="0">Masculino</option>
                      </select>
                  </div>

                  <div class="form-group{{ $errors->has('active') ? ' has-danger' : '' }} col-sm-3">
                    <label for="input-active">Estatus</label>
                      <select id="input-active" name="active" class="selectpicker show-menu-arrow{{ $errors->has('active') ? ' is-invalid' : '' }}" data-style="btn-primary" data-width="100%" data-live-search="true">
                          <option value="0" selected>Inactivo</option>
                          <option value="1">Activo</option>
                      </select>
                  </div>
                  
                </div>
                <div class="row mt-3">

                  <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }} col-sm-4">
                    <label for="email">{{ __('Email') }}</label>
                    <input class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" id="input-email" type="email" value="{{ old('email') }}" aria-required="true" aria-describedby="emailHelp" placeholder="Escribe el email del usuario"  aria-required="true">
                    @if ($errors->has('email'))
                        <span id="email-error" class="error text-danger" for="input-email">
                          {{ $errors->first('email') }}
                        </span>
                      @endif
                  </div>

                  <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }} col-sm-4">
                    <label for="password">{{ __('Contraseña') }}</label>
                    <input class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" id="input-password" type="password" aria-describedby="passwordHelp" placeholder="Escribe la nueva contraseña">
                    @if ($errors->has('password'))
                      <span id="password-error" class="error text-danger" for="input-password">
                        {{ $errors->first('password') }}
                      </span>
                    @endif
                  </div>

                  <div class="form-group col-sm-4">
                    <label for="password_confirmation">{{ __('Confirmar contraseña') }}</label>
                    <input type="password" class="form-control" id="input-password_confirmation" aria-describedby="passwordHelp" placeholder="Confirmar contraseña" name="password_confirmation">
                  </div>

              </div>
              <div class="card-footer ml-auto mr-auto mt-5">
                <button type="submit" class="btn btn-primary">{{ __('Guardar') }}</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection