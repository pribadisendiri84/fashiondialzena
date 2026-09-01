<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Owner = 'owner';
    case Staff = 'staff';
    case Sales = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Owner => 'Owner',
            self::Staff => 'Staf',
            self::Sales => 'Penjualan',
        };
    }

    public function allows(Ability $ability): bool
    {
        return match ($this) {
            self::Superadmin => true,
            self::Owner => $ability !== Ability::ManageUsers,
            self::Staff => match ($ability) {
                Ability::ViewDashboard,
                Ability::ViewFinancials,
                Ability::ManageSettings,
                Ability::ManageUsers,
                Ability::DeleteRecords => false,
                Ability::ManageCatalog,
                Ability::RecordStock,
                Ability::RecordSales,
                Ability::RecordReturns => true,
            },
            self::Sales => match ($ability) {
                Ability::RecordSales,
                Ability::RecordReturns => true,
                Ability::ViewDashboard,
                Ability::ViewFinancials,
                Ability::ManageSettings,
                Ability::ManageUsers,
                Ability::DeleteRecords,
                Ability::ManageCatalog,
                Ability::RecordStock => false,
            },
        };
    }
}
