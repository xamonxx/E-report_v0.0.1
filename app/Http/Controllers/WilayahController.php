<?php

namespace App\Http\Controllers;

class WilayahController extends Controller
{
    public function cities()
    {
        return response()->json(config('wilayah_kota.mapping'));
    }

    public function districts()
    {
        return response()->json(config('wilayah_kecamatan.mapping'));
    }
}
