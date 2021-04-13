@extends('layouts.app')

@section('content')
    <!--div class="container"-->
    <!--div class="row pt-0 pb-0 mt-0 bg-success">
        <div class="col-sm-4 mt-0 pl-0 pr-0" >
            <div class="card m-0 p-0">
                <div class="card-header">{{ __('Restablecer contraseña') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="form-group row">
                            <label for="email"
                                class="col-md-4 col-form-label text-md-right">{{ __('Correo electrónico') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ $email ?? old('email') }}" required autocomplete="email"
                                    autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password"
                                class="col-md-4 col-form-label text-md-right">{{ __('Constraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password-confirm"
                                class="col-md-4 col-form-label text-md-right">{{ __('Confirmar contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control"
                                    name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Restablecer contraseña') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8 m-0 p-0">
            
        </div>
    </div-->

    <div class="row pt-0 pb-0 bg-success">
        <div class="col-sm-4 mt-0 pl-0 pr-0" >
            <form class="form " method="post" action="{{ route('password.update') }}">
                @csrf
                <div class="card bg-success p-0 m-0" style="height: 100vh !important">
                    <div class="mt-5 ml-5 mr-5">
                        <div class="mt-5">
                            <div class="mx-auto d-block col-sm-8 mt-5">
                                <!--img class="mx-auto d-block mt-5" src="{{ asset('white') }}/img/svg/logo.svg" alt="" style="width: 30% !important"-->
                                <h3  class="text-light">RESTABLECER CONTRASEÑA</h3>
                            </div>
                        </div>
                    </div>
                    <div class="m-5">
                        <div class="col-sm-8 mx-auto d-block">
                            
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="form-group">
                                <label for="email" class="text-light">{{ __('Correo electrónico') }}</label>
                                <div >
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                        name="email" value="{{ $email ?? old('email') }}" required autocomplete="email"
                                        autofocus>

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password" class="text-light">{{ __('Constraseña') }}</label>
                                <div>
                                    <input id="password" type="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required autocomplete="new-password">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password-confirm" class="text-light">{{ __('Confirmar contraseña') }}</label>
                                <div>
                                    <input id="password-confirm" type="password" class="form-control"
                                        name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>

                            <div class="form-group">
                                <div>
                                    <button type="submit"  class="btn btn-outline-light">
                                        {{ __('Restablecer contraseña') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-sm-8 mt-0 p-0 bg-white">
        <lottie-player src="https://assets7.lottiefiles.com/packages/lf20_mdbdc5l7.json"   background="transparent" class="mx-auto d-block"  speed="1"  style="width:90%;" loop  autoplay></lottie-player>
            <!--img class="mx-auto d-block p-0 m-0" src="{{ asset('white') }}/img/svg/segunda.svg" alt="" style="height: 100vh !important"-->
        </div>
    </div>
    <!--/div-->
@endsection
