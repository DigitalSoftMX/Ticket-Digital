<?php

use App\Dispatcher as AppDispatcher;
use App\Eucomb\Award;
use App\Eucomb\Canje;
use App\Eucomb\CatBomba;
use App\Eucomb\CatExchange;
use App\Eucomb\CatPrecio;
use App\Eucomb\CatState;
use App\Eucomb\CatStatus;
use App\Eucomb\ChangeMembership;
use App\Eucomb\ConjuntoMembership;
use App\Eucomb\CountVoucher;
use App\Eucomb\Dispatcher;
use App\Eucomb\DoublePoint;
use App\Eucomb\Factura;
use App\Eucomb\FacturaEmisor;
use App\Eucomb\FacturaReceptor;
use App\Eucomb\History;
use App\Eucomb\Key;
use App\Eucomb\Permission;
use App\Eucomb\Tarjeta;
use App\Eucomb\UserEstacion;
use App\Eucomb\Voucher;
use App\ToCopy\Award as ToCopyAward;
use App\ToCopy\Canje as ToCopyCanje;
use App\ToCopy\CatBomba as ToCopyCatBomba;
use App\ToCopy\CatExchange as ToCopyCatExchange;
use App\ToCopy\CatPrecio as ToCopyCatPrecio;
use App\ToCopy\CatState as ToCopyCatState;
use App\ToCopy\CatStatus as ToCopyCatStatus;
use App\ToCopy\ChangeMembership as ToCopyChangeMembership;
use App\ToCopy\ConjuntoMembership as ToCopyConjuntoMembership;
use App\ToCopy\CountVoucher as ToCopyCountVoucher;
use App\ToCopy\Dispatcher as ToCopyDispatcher;
use App\ToCopy\DoublePoint as ToCopyDoublePoint;
use App\ToCopy\Factura as ToCopyFactura;
use App\ToCopy\FacturaEmisor as ToCopyFacturaEmisor;
use App\ToCopy\FacturaReceptor as ToCopyFacturaReceptor;
use App\ToCopy\History as ToCopyHistory;
use App\ToCopy\Key as ToCopyKey;
use App\ToCopy\Permission as ToCopyPermission;
use App\ToCopy\Tarjeta as ToCopyTarjeta;
use App\ToCopy\UserEstacion as ToCopyUserEstacion;
use App\ToCopy\Voucher as ToCopyVoucher;
use Illuminate\Database\Seeder;

class EucombTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (CatState::all() as $eucombCatState) {
            $catState = new ToCopyCatState();
            $catState->create(json_decode($eucombCatState, true));
        }
        foreach (CatExchange::all() as $eucombCatExchange) {
            $catExchange = new ToCopyCatExchange();
            $catExchange->create(json_decode($eucombCatExchange, true));
        }
        foreach (CatPrecio::all() as $eucombCatPrecio) {
            $catPrecio = new ToCopyCatPrecio();
            $catPrecio->create(json_decode($eucombCatPrecio, true));
        }
        foreach (DoublePoint::all() as $eucombDoublePoint) {
            $doublePoint = new ToCopyDoublePoint();
            $doublePoint->create(json_decode($eucombDoublePoint, true));
        }
        foreach (Key::all() as $eucombKey) {
            $key = new ToCopyKey();
            $key->create(json_decode($eucombKey, true));
        }
        foreach (Permission::all() as $eucombPermission) {
            $permission = new ToCopyPermission();
            $p = ($permission->create(json_decode($eucombPermission, true)));
            $p->roles()->attach($eucombPermission->roles);
        }
        foreach (CatStatus::all() as $eucombCatStatus) {
            $catStatus = new ToCopyCatStatus();
            $catStatus->create(json_decode($eucombCatStatus, true));
        }
        foreach (UserEstacion::all() as $eucombUserEstacion) {
            $userEstacion = new ToCopyUserEstacion();
            $userEstacion->create(json_decode($eucombUserEstacion, true));
        }
        foreach (Award::all() as $eucombAward) {
            $award = new ToCopyAward();
            $award->create(json_decode($eucombAward, true));
        }
        foreach (Canje::all() as $eucombCanje) {
            $canje = new ToCopyCanje();
            $canje->create(json_decode($eucombCanje, true));
        }
        foreach (CatBomba::all() as $eucombCatBomba) {
            $catBomba = new ToCopyCatBomba();
            $catBomba->create(json_decode($eucombCatBomba, true));
        }
        foreach (ChangeMembership::all() as $eucombChangeMembership) {
            $changeMembership = new ToCopyChangeMembership();
            $changeMembership->create(json_decode($eucombChangeMembership, true));
        }
        foreach (ConjuntoMembership::all() as $eucombConjuntoMembership) {
            $conjuntoMembreship = new ToCopyConjuntoMembership();
            $conjuntoMembreship->create(json_decode($eucombConjuntoMembership, true));
        }
        foreach (CountVoucher::all() as $eucombCountVoucher) {
            $countVoucher = new ToCopyCountVoucher();
            $countVoucher->create(json_decode($eucombCountVoucher, true));
        }
        foreach (Dispatcher::all() as $eucombDispatcher) {
            $dispatcher = new ToCopyDispatcher();
            $dispatcher->create(json_decode($eucombDispatcher, true));
            $data['id'] = $eucombDispatcher->id;
            $data['user_id'] = $eucombDispatcher->id_users;
            $data['station_id'] = $eucombDispatcher->id_station;
            $data['created_at'] = $eucombDispatcher->created_at;
            $data['updated_at'] = $eucombDispatcher->updated_at;
            $dispatcherTicket = new AppDispatcher();
            $dispatcherTicket->create($data);
        }
        foreach (Factura::all() as $eucombFactura) {
            $factura = new ToCopyFactura();
            $factura->create(json_decode($eucombFactura, true));
        }
        foreach (FacturaEmisor::all() as $eucombFacturaEmisor) {
            $facturaEmisor = new ToCopyFacturaEmisor();
            $facturaEmisor->create(json_decode($eucombFacturaEmisor, true));
        }
        foreach (FacturaReceptor::all() as $eucombFacturaReceptor) {
            $facturaReceptor = new ToCopyFacturaReceptor();
            $facturaReceptor->create(json_decode($eucombFacturaReceptor, true));
        }
        foreach (History::all() as $eucombHistory) {
            $history = new ToCopyHistory();
            $history->create(json_decode($eucombHistory, true));
        }
        foreach (Tarjeta::all() as $eucombTarjeta) {
            $tarjeta = new ToCopyTarjeta();
            $tarjeta->create(json_decode($eucombTarjeta, true));
        }
        foreach (Voucher::all() as $eucombVoucher) {
            $voucher = new ToCopyVoucher();
            $voucher->create(json_decode($eucombVoucher, true));
        }
    }
}
