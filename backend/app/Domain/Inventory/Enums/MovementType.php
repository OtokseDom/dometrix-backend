<?php

namespace App\Domain\Inventory\Enums;

enum MovementType: string
{
    case PURCHASE_RECEIPT = 'PURCHASE_RECEIPT';
    case PRODUCTION_CONSUMPTION = 'PRODUCTION_CONSUMPTION';
    case PRODUCTION_OUTPUT = 'PRODUCTION_OUTPUT';
    case SALES_ISSUE = 'SALES_ISSUE';
    case ADJUSTMENT_IN = 'ADJUSTMENT_IN';
    case ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
    case TRANSFER_IN = 'TRANSFER_IN';
    case TRANSFER_OUT = 'TRANSFER_OUT';
    case RETURN_IN = 'RETURN_IN';
    case RETURN_OUT = 'RETURN_OUT';
    case SCRAP_OUT = 'SCRAP_OUT';

    public function isInbound(): bool
    {
        return in_array($this, [
            self::PURCHASE_RECEIPT,
            self::PRODUCTION_OUTPUT,
            self::ADJUSTMENT_IN,
            self::TRANSFER_IN,
            self::RETURN_IN,
        ]);
    }

    public function isOutbound(): bool
    {
        return !$this->isInbound();
    }

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_RECEIPT => 'Purchase Receipt',
            self::PRODUCTION_CONSUMPTION => 'Production Consumption',
            self::PRODUCTION_OUTPUT => 'Production Output',
            self::SALES_ISSUE => 'Sales Issue',
            self::ADJUSTMENT_IN => 'Inventory Adjustment (In)',
            self::ADJUSTMENT_OUT => 'Inventory Adjustment (Out)',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::RETURN_IN => 'Return (Inbound)',
            self::RETURN_OUT => 'Return (Outbound)',
            self::SCRAP_OUT => 'Scrap (Out)',
        };
    }
}
