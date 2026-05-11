<?php

use App\Models\BeneficiaryGroup;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Replace manually entered totals with counts derived from linked members.
     */
    public function up(): void
    {
        BeneficiaryGroup::query()->each(fn (BeneficiaryGroup $group) => $group->refreshMemberCounts());
    }

    public function down(): void
    {
        //
    }
};
