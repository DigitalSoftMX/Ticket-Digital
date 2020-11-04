<?php

use App\Eucomb\CatType;
use App\Eucomb\Empresa;
use App\ToCopy\CatType as ToCopyCatType;
use App\ToCopy\Empresa as ToCopyEmpresa;
use Illuminate\Database\Seeder;

class CatTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (CatType::all() as $eucombCatType) {
            $catType = new ToCopyCatType();
            $catType->create(json_decode($eucombCatType, true));
        }
        foreach (Empresa::all() as $eucombEmpresa) {
            $empresa = new ToCopyEmpresa();
            $empresa->create(json_decode($eucombEmpresa, true));
        }
    }
}
