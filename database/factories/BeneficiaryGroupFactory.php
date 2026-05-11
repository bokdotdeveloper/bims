<?php

namespace Database\Factories;

use App\Models\BeneficiaryGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryGroup>
 */
class BeneficiaryGroupFactory extends Factory
{
    protected $model = BeneficiaryGroup::class;

    public function definition(): array
    {
        $groupTypes = ['Farmers Association', 'Fisherfolk', 'Women\'s Group', 'Youth Group', 'Senior Citizens', 'PWD Organization', 'Indigenous People', 'Urban Poor'];
        $barangays   = ['Poblacion', 'San Isidro', 'Santo Niño', 'Bagong Silang', 'Maligaya', 'Masagana', 'Pag-asa', 'Bagumbayan'];

        $male   = $this->faker->numberBetween(5, 60);
        $female = $this->faker->numberBetween(5, 60);

        return [
            'group_name'     => $this->faker->randomElement($barangays) . ' ' . $this->faker->randomElement($groupTypes),
            'group_type'     => $this->faker->randomElement($groupTypes),
            'total_members'  => $male + $female,
            'male_members'   => $male,
            'female_members' => $female,
            'date_organized' => $this->faker->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
        ];
    }
}

