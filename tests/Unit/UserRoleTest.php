<?php

namespace Tests\Unit;

use App\Enums\Ability;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_superadmin_has_every_ability(): void
    {
        foreach (Ability::cases() as $ability) {
            $this->assertTrue(UserRole::Superadmin->allows($ability));
        }
    }

    public function test_owner_has_every_ability_except_manage_users(): void
    {
        foreach (Ability::cases() as $ability) {
            if ($ability === Ability::ManageUsers) {
                $this->assertFalse(UserRole::Owner->allows($ability));
                continue;
            }

            $this->assertTrue(UserRole::Owner->allows($ability));
        }
    }

    public function test_staff_can_run_operations_but_not_owner_tools(): void
    {
        $this->assertTrue(UserRole::Staff->allows(Ability::ManageCatalog));
        $this->assertTrue(UserRole::Staff->allows(Ability::RecordStock));
        $this->assertTrue(UserRole::Staff->allows(Ability::RecordSales));
        $this->assertTrue(UserRole::Staff->allows(Ability::RecordReturns));
        $this->assertFalse(UserRole::Staff->allows(Ability::ViewDashboard));
        $this->assertFalse(UserRole::Staff->allows(Ability::ViewFinancials));
        $this->assertFalse(UserRole::Staff->allows(Ability::ManageSettings));
        $this->assertFalse(UserRole::Staff->allows(Ability::ManageUsers));
        $this->assertFalse(UserRole::Staff->allows(Ability::DeleteRecords));
    }

    public function test_sales_can_only_record_sales_and_returns(): void
    {
        $this->assertTrue(UserRole::Sales->allows(Ability::RecordSales));
        $this->assertTrue(UserRole::Sales->allows(Ability::RecordReturns));
        $this->assertFalse(UserRole::Sales->allows(Ability::ManageCatalog));
        $this->assertFalse(UserRole::Sales->allows(Ability::RecordStock));
        $this->assertFalse(UserRole::Sales->allows(Ability::ViewDashboard));
        $this->assertFalse(UserRole::Sales->allows(Ability::ViewFinancials));
        $this->assertFalse(UserRole::Sales->allows(Ability::ManageUsers));
    }
}
