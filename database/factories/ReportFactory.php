<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'reporter_id' => User::factory(),
            'alasan' => fake()->randomElement([
                Report::REASON_PENIPUAN,
                Report::REASON_BARANG_PALSU,
                Report::REASON_KONTEN_TIDAK_PANTAS,
                Report::REASON_HARGA_TIDAK_WAJAR,
                Report::REASON_LAINNYA,
            ]),
            'keterangan' => fake()->paragraph(),
            'status' => Report::STATUS_PENDING,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (): array => [
            'status' => Report::STATUS_REVIEWED,
            'reviewed_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => ['status' => Report::STATUS_RESOLVED]);
    }
}
