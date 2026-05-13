<?php

namespace Database\Factories;

use App\Models\BeneficiaryGroup;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryGroup>
 */
class BeneficiaryGroupFactory extends Factory
{
    protected $model = BeneficiaryGroup::class;

    public function configure(): static
    {
        return $this->afterCreating(fn (BeneficiaryGroup $group) => $group->refreshMemberCounts());
    }

    public function definition(): array
    {
        $groupTypes = ['Farmers Association', 'Fisherfolk', 'Women\'s Group', 'Youth Group', 'Senior Citizens', 'PWD Organization', 'Indigenous People', 'Urban Poor'];
        $barangays = ['Poblacion', 'San Isidro', 'Santo Niño', 'Bagong Silang', 'Maligaya', 'Masagana', 'Pag-asa', 'Bagumbayan'];

        $organizedTs = random_int(strtotime('-10 years'), strtotime('-1 year'));
        $organized = (new DateTimeImmutable)->setTimestamp($organizedTs);

        $type = $groupTypes[array_rand($groupTypes)];

        return [
            'group_name' => $barangays[array_rand($barangays)].' '.$type,
            'group_type' => $type,
            'date_organized' => $organized->format('Y-m-d'),
        ];
    }
}
