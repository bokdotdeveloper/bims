<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use DateTimeImmutable;
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

        $streets = ['Rizal St', 'Mabini St', 'Bonifacio Ave', 'Luna St', 'Aguinaldo St'];

        $firstNames = ['Maria', 'Juan', 'Ana', 'Pedro', 'Rosa', 'Carlos', 'Liza', 'Miguel', 'Elena', 'Jose'];
        $lastNames = ['Santos', 'Cruz', 'Reyes', 'Garcia', 'Lopez', 'Torres', 'Flores', 'Ramos', 'Mendoza', 'Diaz'];
        $middleNames = ['Reyes', 'Garcia', 'Lopez', 'Torres', null, null];

        $dobTs = random_int(strtotime('-70 years'), strtotime('-18 years'));
        $dob = (new DateTimeImmutable)->setTimestamp($dobTs);

        $codeSuffix = strtoupper(bin2hex(random_bytes(3)));

        $middle = $middleNames[array_rand($middleNames)];
        $contact = random_int(1, 10) <= 6
            ? '09'.str_pad((string) random_int(0, 999_999_999), 9, '0', STR_PAD_LEFT)
            : null;

        return [
            'beneficiary_code' => 'BEN-'.$codeSuffix,
            'last_name' => $lastNames[array_rand($lastNames)],
            'first_name' => $firstNames[array_rand($firstNames)],
            'middle_name' => $middle,
            'date_of_birth' => $dob->format('Y-m-d'),
            'sex' => random_int(0, 1) === 1 ? 'Male' : 'Female',
            'civil_status' => ['Single', 'Married', 'Widowed', 'Separated'][random_int(0, 3)],
            'address' => random_int(1, 999).' '.$streets[array_rand($streets)],
            'barangay' => $barangays[array_rand($barangays)],
            'municipality' => 'Culasi',
            'province' => 'Antique',
            'contact_number' => $contact,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
