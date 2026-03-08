<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Province;

return new class extends Migration
{
    public function up()
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // Kode provinsi
            $table->string('name'); // Nama provinsi
            $table->timestamps();
        });

        // Insert data provinsi Indonesia
        $this->seedProvinces();
    }

    public function down()
    {
        Schema::dropIfExists('provinces');
    }

    private function seedProvinces()
    {
        $provinces = [
            // Pulau Sumatera (10 provinsi)
            ['code' => 'AC', 'name' => 'Aceh'],
            ['code' => 'SU', 'name' => 'Sumatera Utara'],
            ['code' => 'SB', 'name' => 'Sumatera Barat'],
            ['code' => 'RI', 'name' => 'Riau'],
            ['code' => 'KR', 'name' => 'Kepulauan Riau'],
            ['code' => 'JA', 'name' => 'Jambi'],
            ['code' => 'SS', 'name' => 'Sumatera Selatan'],
            ['code' => 'BE', 'name' => 'Bengkulu'],
            ['code' => 'LA', 'name' => 'Lampung'],
            ['code' => 'BB', 'name' => 'Kepulauan Bangka Belitung'],
            
            // Pulau Jawa (6 provinsi)
            ['code' => 'BT', 'name' => 'Banten'],
            ['code' => 'JK', 'name' => 'DKI Jakarta'],
            ['code' => 'JB', 'name' => 'Jawa Barat'],
            ['code' => 'JT', 'name' => 'Jawa Tengah'],
            ['code' => 'JI', 'name' => 'Jawa Timur'],
            ['code' => 'YO', 'name' => 'Daerah Istimewa Yogyakarta'],
            
            // Bali & Nusa Tenggara (3 provinsi)
            ['code' => 'BA', 'name' => 'Bali'],
            ['code' => 'NB', 'name' => 'Nusa Tenggara Barat'],
            ['code' => 'NT', 'name' => 'Nusa Tenggara Timur'],
            
            // Pulau Kalimantan (5 provinsi)
            ['code' => 'KB', 'name' => 'Kalimantan Barat'],
            ['code' => 'KT', 'name' => 'Kalimantan Tengah'],
            ['code' => 'KS', 'name' => 'Kalimantan Selatan'],
            ['code' => 'KI', 'name' => 'Kalimantan Timur'],
            ['code' => 'KU', 'name' => 'Kalimantan Utara'],
            
            // Pulau Sulawesi (6 provinsi)
            ['code' => 'SR', 'name' => 'Sulawesi Barat'],
            ['code' => 'SN', 'name' => 'Sulawesi Selatan'],
            ['code' => 'ST', 'name' => 'Sulawesi Tengah'],
            ['code' => 'SG', 'name' => 'Sulawesi Tenggara'],
            ['code' => 'SA', 'name' => 'Sulawesi Utara'],
            ['code' => 'GO', 'name' => 'Gorontalo'],
            
            // Kepulauan Maluku (2 provinsi)
            ['code' => 'MA', 'name' => 'Maluku'],
            ['code' => 'MU', 'name' => 'Maluku Utara'],
            
            // Pulau Papua (6 provinsi)
            ['code' => 'PA', 'name' => 'Papua'],
            ['code' => 'PB', 'name' => 'Papua Barat'],
            ['code' => 'PS', 'name' => 'Papua Selatan'],
            ['code' => 'PT', 'name' => 'Papua Tengah'],
            ['code' => 'PP', 'name' => 'Papua Pegunungan'],
            ['code' => 'PBD', 'name' => 'Papua Barat Daya'],
        ];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']],
                ['name' => $province['name']]
            );
        }
    }
};
