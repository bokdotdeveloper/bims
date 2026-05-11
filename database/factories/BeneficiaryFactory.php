<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        $barangays = [
            'Poblacion', 'Cabugao', 'Malamhati', 'Badiang',
            'Tigbauan', 'San Roque', 'Binirayan', 'Igbaras',
        ];

        return [
            'beneficiary_code' => 'BEN-' . strtoupper($this->faker->unique()->bothify('??####')),
            'last_name'        => $this->faker->lastName(),
            'first_name'       => $this->faker->firstName(),
            'middle_name'      => $this->faker->optional()->lastName(),
            'date_of_birth'    => $this->faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'sex'              => $this->faker->randomElement(['Male', 'Female']),
            'civil_status'     => $this->faker->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'address'          => $this->faker->streetAddress(),
            'barangay'         => $this->faker->randomElement($barangays),
            'municipality'     => 'Culasi',
            'province'         => 'Antique',
            'contact_number'   => $this->faker->optional()->numerify('09#########'),
            'is_active'        => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
