<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'serie', 'fecha', 'sello', 'formapago', 'nocertificado', 'certificado', 'folio', 'uuid', 'fechatimbrado', 'sellocfd', 'subtotal', 'descuento', 'moneda', 'tipocambio', 'total', 'tipocombrobante', 'metodopago', 'lugarexpedicion', 'id_emisor', 'id_receptor', 'usocfdi', 'Claveproserv', 'cantidad', 'claveunidad', 'descripcion', 'noidentificacion', 'valorunitario', 'importe', 'base', 'iva', 'descuentoD', 'id_estacion', 'id_bomba', 'archivoxml', 'archivopdf', 'estatus', 'created_at', 'updated_at'];
}
