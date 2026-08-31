<?php

namespace App\Enums;

enum Ability: string
{
    case ViewDashboard = 'view-dashboard';
    case ViewFinancials = 'view-financials';
    case ManageSettings = 'manage-settings';
    case ManageUsers = 'manage-users';
    case DeleteRecords = 'delete-records';
    case ManageCatalog = 'manage-catalog';
    case RecordStock = 'record-stock';
    case RecordSales = 'record-sales';
    case RecordReturns = 'record-returns';
}
