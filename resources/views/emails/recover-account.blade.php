@extends('layouts.app', ['activePage' => '', 'titlePage' => 'Recuperar Contraseña'])

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-category">Hola, a continuación te proporcionamos tu contraseña temporal. <br/>
                                Te recomendamos cambiarla lo antes posible en la sección de perfil para garantizar la seguridad de tu cuenta.
                            </p>

                            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;padding-bottom: 25px;">
                                <tr>
                                    <td>
                                        <div style="margin-left: 0; padding-top:5px;">
                                            <table class="button_block block-5" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="text-align:center; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                <tr>
                                                    <td align="left">
                                                        <span style="color:#000000;direction:ltr;font-family:'Lato', sans-serif;font-size:16px;font-weight:700;letter-spacing:0px;line-height:120%;text-align:left;mso-line-height-alt:15px; word-break: break-word; line-height: 22px;"
                                                            >Contraseña:
                                                        </span>
                                                    </td>
                                                    <td align="right">
                                                        <span
                                                            style="color:#000000;direction:ltr;font-family:'Lato', sans-serif;font-size:16px;font-weight:700;letter-spacing:0px;line-height:120%;text-align:right;mso-line-height-alt:15px;"
                                                            class="s-email">{{ $data['password'] }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
